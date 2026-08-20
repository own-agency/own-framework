import Alpine from "alpinejs";

// Shared by two separate blocks — acf/verhaal-hero (the category filter
// dropdown) and acf/verhalen (sort/results/pagination) — hence a global
// store (Alpine.store), not a local Alpine.data() component: state
// read/written from more than one block's own markup needs to live
// somewhere reachable from both, same reasoning as ./news-filter.js's own
// $store.newsFilters. Both blocks are restricted to templates/
// archive-verhaal.html (blocks/verhaal-hero/hooks/
// restrict-to-archive-verhaal-template.php), so assuming both are present
// together is safe.
//
// No search box, no list/grid view toggle (unlike ./vacatures-filter.js/
// ./agenda-filter.js) — this feature was built without either, same shape
// as ./news-filter.js.
//
// Config (available taxonomies + which are active from the URL) comes from
// acf/verhaal-hero's own "data-own-verhalen-filters" attribute, read
// directly from the DOM at registration time. Must be imported before
// ./packages/alpine.js's own Alpine.start() call, same reason as every
// other store in this file's sibling scripts.

const SORT_PARAMS = {
  date_desc: { orderby: "date", order: "DESC" },
  date_asc: { orderby: "date", order: "ASC" },
  title_asc: { orderby: "title", order: "ASC" },
  title_desc: { orderby: "title", order: "DESC" },
};

// Mirrors render.php's own $sort_options labels — same trade-off
// ./news-filter.js's own SORT_LABELS already accepts.
const SORT_LABELS = {
  date_desc: "Datum (nieuwste eerst)",
  date_asc: "Datum (oudste eerst)",
  title_asc: "Titel (A-Z)",
  title_desc: "Titel (Z-A)",
};

const configEl = document.querySelector("[data-own-verhalen-filters]");
const config = configEl ? JSON.parse(configEl.dataset.ownVerhalenFilters || "{}") : {};
const taxonomyFilters = config.taxonomies || {};
const activeTerms = config.activeTerms || {};

const initialFilters = {};
Object.keys(taxonomyFilters).forEach((key) => {
  initialFilters[key] = Array.isArray(activeTerms[key]) ? [...activeTerms[key]] : [];
});

Alpine.store("verhalenFilters", {
  taxonomyFilters,
  filters: initialFilters,
  sortBy: "date_desc",
  currentPage: parseInt(new URLSearchParams(window.location.search).get("paged"), 10) || 1,
  loading: false,

  get sortLabel() {
    return SORT_LABELS[this.sortBy] || SORT_LABELS.date_desc;
  },

  init() {
    // Event delegation, same reason as ./news-filter.js's own init():
    // apply() replaces the pagination markup wholesale via innerHTML.
    document.addEventListener("click", (event) => {
      const button = event.target.closest("[data-own-verhalen-page]");
      if (!button) return;
      this.currentPage = parseInt(button.dataset.ownVerhalenPage, 10) || 1;
      this.apply();
      document.querySelector("[data-own-verhalen-results]")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  },

  getSelectedCount(taxonomy) {
    return (this.filters[taxonomy] || []).length;
  },

  hasTaxonomyFilters() {
    return Object.values(this.filters).some((terms) => terms.length > 0);
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
    if (this.currentPage > 1) {
      params.set("paged", this.currentPage);
    }
    // Bracketed "verhalen-categorie[]", not a bare repeated
    // "verhalen-categorie=...": same reason as ./news-filter.js's own
    // updateUrl() — PHP only keeps the LAST value for a repeated
    // non-bracketed key once it populates $_GET server-side.
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });
    const url = window.location.pathname + (params.toString() ? `?${params.toString()}` : "");
    window.history.replaceState({}, "", url);
  },

  async apply() {
    if (!window.OwnVerhalen) return;
    this.loading = true;
    this.updateUrl();

    const sort = SORT_PARAMS[this.sortBy] || SORT_PARAMS.date_desc;
    const params = new URLSearchParams({
      paged: this.currentPage,
      orderby: sort.orderby,
      order: sort.order,
      per_page: config.perPage || 12,
    });
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });

    const results = document.querySelector("[data-own-verhalen-results]");
    if (results) results.style.opacity = "0.4";

    try {
      const response = await fetch(`${window.OwnVerhalen.endpoint}?${params.toString()}`);
      const data = await response.json();
      if (!data.success) return;

      const pagination = document.querySelector("[data-own-verhalen-pagination]");
      const count = document.querySelector("[data-own-verhalen-count]");

      if (results) results.innerHTML = data.html;
      if (pagination) pagination.innerHTML = data.pagination_html || "";
      if (count) count.textContent = `${data.count} ${data.count === 1 ? "verhaal" : "verhalen"} gevonden`;

      Object.entries(data.taxonomy_counts || {}).forEach(([taxonomy, counts]) => {
        if (!this.taxonomyFilters[taxonomy]) return;
        this.taxonomyFilters[taxonomy].options = this.taxonomyFilters[taxonomy].options.map((option) => ({
          ...option,
          count: counts[option.value] ?? 0,
        }));
      });
    } catch (error) {
      console.error("Error filtering verhalen:", error);
    } finally {
      this.loading = false;
      if (results) results.style.opacity = "";
    }
  },
});
