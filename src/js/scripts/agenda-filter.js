import Alpine from "alpinejs";

// Powers acf/agenda's filter sidebar/results/pagination — same global-store
// shape as ./vacatures-filter.js (blocks/agenda/render.php's own
// "data-own-agenda-filters" attribute is where this reads its initial
// config), restricted the same way to its one template (blocks/agenda/
// hooks/restrict-to-archive-agenda-template.php).
//
// Two differences from vacaturesFilters, both reflecting this CPT's own
// shape rather than vacancy's: a "periode" facet (aankomend/afgelopen/alle)
// that ISN'T a taxonomy — single-select (a plain string, not an array like
// the taxonomy `filters`) — and its own `periodeCounts` (mirrors
// `taxonomyFilters[x].options[].count`, refreshed from the REST response's
// own "periode_counts"), plus a chronological ("eerstvolgende eerst")
// default sort instead of "nieuwste eerst": this is a calendar of upcoming
// moments, not a news feed.
//
// "month" is a 3rd, later addition — NOT single-select like "periode",
// despite also not being a real taxonomy: a moment is grouped by the
// calendar month its OWN "agenda_sort_start" falls in, ANY year (blocks/
// agenda/hooks/query.php's own ownframework_agenda_build_month_meta_query()),
// and multiple months can be picked at once ("August OR September"), so it's
// an ARRAY of month codes ("01".."12"), same shape as a taxonomy's own
// `filters[slug]` rather than "periode"'s plain string — with its own
// "monthCounts" refreshed the same way "periodeCounts" already is. Sent to
// the REST endpoint/URL as "month[]=08&month[]=09" (bracketed), not the bare
// repeated-key style the taxonomy filters use below — see updateUrl()/
// apply()'s own comments for why that distinction actually matters here.

const SORT_PARAMS = {
  date_asc: { orderby: "date", order: "ASC" },
  date_desc: { orderby: "date", order: "DESC" },
  title_asc: { orderby: "title", order: "ASC" },
  title_desc: { orderby: "title", order: "DESC" },
};

// Mirrors render.php's own $sort_options labels — same trade-off as
// ./vacatures-filter.js's own SORT_LABELS.
const SORT_LABELS = {
  date_asc: "Datum (eerstvolgende eerst)",
  date_desc: "Datum (laatste eerst)",
  title_asc: "Titel (A-Z)",
  title_desc: "Titel (Z-A)",
};

const configEl = document.querySelector("[data-own-agenda-filters]");
const config = configEl ? JSON.parse(configEl.dataset.ownAgendaFilters || "{}") : {};
const taxonomyFilters = config.taxonomies || {};
const activeTerms = config.activeTerms || {};

const initialFilters = {};
Object.keys(taxonomyFilters).forEach((key) => {
  initialFilters[key] = Array.isArray(activeTerms[key]) ? [...activeTerms[key]] : [];
});

Alpine.store("agendaFilters", {
  taxonomyFilters,
  filters: initialFilters,
  periode: config.periode || "aankomend",
  periodeCounts: {},
  month: Array.isArray(config.month) ? [...config.month] : [],
  monthCounts: {},
  searchQuery: config.search || "",
  sortBy: "date_asc",
  currentPage: parseInt(new URLSearchParams(window.location.search).get("paged"), 10) || 1,
  loading: false,

  get sortLabel() {
    return SORT_LABELS[this.sortBy] || SORT_LABELS.date_asc;
  },

  init() {
    // Event delegation, same reason as ./vacatures-filter.js's own init():
    // apply() replaces the pagination markup wholesale via innerHTML.
    document.addEventListener("click", (event) => {
      const button = event.target.closest("[data-own-agenda-page]");
      if (!button) return;
      this.currentPage = parseInt(button.dataset.ownAgendaPage, 10) || 1;
      this.apply();
      document.querySelector("[data-own-agenda-results]")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  },

  getSelectedCount(taxonomy) {
    return (this.filters[taxonomy] || []).length;
  },

  // Also covers "month" now that it's a multi-select array just like a
  // taxonomy's own `filters[slug]` — "Wis filters" (clearTaxonomyFilters()
  // below) should reset every optional multi-select facet back to "nothing
  // picked", not just the real taxonomies. "periode" is deliberately never
  // part of either check: it always has a value (no "empty" state to clear).
  hasTaxonomyFilters() {
    return this.month.length > 0 || Object.values(this.filters).some((terms) => terms.length > 0);
  },

  getTaxonomyFilterCount() {
    return Object.values(this.filters).reduce((sum, terms) => sum + terms.length, 0);
  },

  clearTaxonomyFilters() {
    Object.keys(this.filters).forEach((key) => {
      this.filters[key] = [];
    });
    this.month = [];
    this.currentPage = 1;
    this.apply();
  },

  onFilterChange() {
    this.currentPage = 1;
    this.apply();
  },

  updateUrl() {
    const params = new URLSearchParams();
    if (this.periode !== "aankomend") {
      params.set("periode", this.periode);
    }
    // Bracketed "month[]", NOT a bare repeated "month=..." like the taxonomy
    // filters below: PHP only keeps the LAST value for a repeated
    // non-bracketed key once it populates $_GET (confirmed directly —
    // parse_str("month=08&month=09") gives "09", not an array), so a
    // multi-select facet genuinely needs the "[]" to survive as more than
    // one value once this URL is loaded server-side (pre_get_posts) or a
    // REST fetch parses its own query string the same way.
    this.month.forEach((month) => params.append("month[]", month));
    if (this.currentPage > 1) {
      params.set("paged", this.currentPage);
    }
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });
    const url = window.location.pathname + (params.toString() ? `?${params.toString()}` : "");
    window.history.replaceState({}, "", url);
  },

  async apply() {
    if (!window.OwnAgenda) return;
    this.loading = true;
    this.updateUrl();

    const sort = SORT_PARAMS[this.sortBy] || SORT_PARAMS.date_asc;
    const params = new URLSearchParams({
      search: this.searchQuery,
      periode: this.periode,
      paged: this.currentPage,
      orderby: sort.orderby,
      order: sort.order,
      per_page: config.perPage || 10,
    });
    this.month.forEach((month) => params.append("month[]", month));
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });

    const results = document.querySelector("[data-own-agenda-results]");
    if (results) results.style.opacity = "0.4";

    try {
      const response = await fetch(`${window.OwnAgenda.endpoint}?${params.toString()}`);
      const data = await response.json();
      if (!data.success) return;

      const pagination = document.querySelector("[data-own-agenda-pagination]");
      const count = document.querySelector("[data-own-agenda-count]");

      if (results) results.innerHTML = data.html;
      if (pagination) pagination.innerHTML = data.pagination_html || "";
      if (count) count.textContent = `${data.count} ${data.count === 1 ? "moment" : "momenten"} gevonden`;

      Object.entries(data.taxonomy_counts || {}).forEach(([taxonomy, counts]) => {
        if (!this.taxonomyFilters[taxonomy]) return;
        this.taxonomyFilters[taxonomy].options = this.taxonomyFilters[taxonomy].options.map((option) => ({
          ...option,
          count: counts[option.value] ?? 0,
        }));
      });

      this.periodeCounts = data.periode_counts || this.periodeCounts;
      this.monthCounts = data.month_counts || this.monthCounts;
    } catch (error) {
      console.error("Error filtering agenda:", error);
    } finally {
      this.loading = false;
      if (results) results.style.opacity = "";
    }
  },
});
