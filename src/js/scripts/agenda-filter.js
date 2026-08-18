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

  hasTaxonomyFilters() {
    return Object.values(this.filters).some((terms) => terms.length > 0);
  },

  getTaxonomyFilterCount() {
    return Object.values(this.filters).reduce((sum, terms) => sum + terms.length, 0);
  },

  clearTaxonomyFilters() {
    Object.keys(this.filters).forEach((key) => {
      this.filters[key] = [];
    });
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
    if (this.currentPage > 1) {
      params.set("paged", this.currentPage);
    }
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(taxonomy, term));
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
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(taxonomy, term));
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
    } catch (error) {
      console.error("Error filtering agenda:", error);
    } finally {
      this.loading = false;
      if (results) results.style.opacity = "";
    }
  },
});
