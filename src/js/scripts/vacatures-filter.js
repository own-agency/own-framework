import Alpine from "alpinejs";

// Shared by two separate blocks — acf/vacature-hero (the search field) and
// acf/vacatures (filters/sort/results/pagination) — hence a global store
// (Alpine.store), not a local Alpine.data() component: state read/written
// from more than one block's own markup needs to live somewhere reachable
// from both, not nested inside either one's own x-data. Same reasoning as
// ./live-search.js's own $store.searchDialog/$store.liveSearch. Both blocks
// are restricted to templates/archive-vacancy.html (blocks/vacature-hero/
// hooks/restrict-to-archive-vacancy-template.php), so assuming both are
// present together — or at least that acf/vacatures rendered its config
// attribute somewhere on the page — is safe.
//
// Config (available taxonomies + which are active from the URL) comes from
// acf/vacatures's own "data-own-vacatures-filters" attribute, read directly
// from the DOM at registration time rather than passed in like Alpine.data()
// would — a store has no per-instance $el, it's a single, page-wide object.
// Must be imported before ./packages/alpine.js's own Alpine.start() call,
// same reason as every other store in this file's sibling scripts.

const SORT_PARAMS = {
  date_desc: { orderby: "date", order: "DESC" },
  date_asc: { orderby: "date", order: "ASC" },
  title_asc: { orderby: "title", order: "ASC" },
  title_desc: { orderby: "title", order: "DESC" },
};

// Mirrors render.php's own $sort_options labels — duplicated rather than
// read from the DOM, same trade-off live-search.js already accepts for its
// own hardcoded UI strings: a fixed, non-editorial set of 4 options isn't
// worth round-tripping through a data attribute.
const SORT_LABELS = {
  date_desc: "Datum (nieuwste eerst)",
  date_asc: "Datum (oudste eerst)",
  title_asc: "Titel (A-Z)",
  title_desc: "Titel (Z-A)",
};

const configEl = document.querySelector("[data-own-vacatures-filters]");
const config = configEl ? JSON.parse(configEl.dataset.ownVacaturesFilters || "{}") : {};
const taxonomyFilters = config.taxonomies || {};
const activeTerms = config.activeTerms || {};

const initialFilters = {};
Object.keys(taxonomyFilters).forEach((key) => {
  initialFilters[key] = Array.isArray(activeTerms[key]) ? [...activeTerms[key]] : [];
});

Alpine.store("vacaturesFilters", {
  taxonomyFilters,
  filters: initialFilters,
  searchQuery: config.search || "",
  sortBy: "date_desc",
  currentPage: parseInt(new URLSearchParams(window.location.search).get("paged"), 10) || 1,
  loading: false,

  get sortLabel() {
    return SORT_LABELS[this.sortBy] || SORT_LABELS.date_desc;
  },

  init() {
    // Event delegation on the document, not a per-button listener: apply()
    // replaces the pagination markup wholesale via innerHTML, so freshly
    // injected buttons would otherwise need their listeners re-bound after
    // every fetch.
    document.addEventListener("click", (event) => {
      const button = event.target.closest("[data-own-vacatures-page]");
      if (!button) return;
      this.currentPage = parseInt(button.dataset.ownVacaturesPage, 10) || 1;
      this.apply();
      document.querySelector("[data-own-vacatures-results]")?.scrollIntoView({ behavior: "smooth", block: "start" });
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

  submitSearch() {
    this.currentPage = 1;
    this.apply();
  },

  // Checkboxes and the sort dropdown's own option buttons call this
  // directly on @change/@click — see ./live-search.js's own store for why
  // this theme prefers explicit calls from the template over a $watch
  // inside the store itself.
  onFilterChange() {
    this.currentPage = 1;
    this.apply();
  },

  updateUrl() {
    const params = new URLSearchParams();
    if (this.searchQuery.trim() !== "") {
      params.set("search", this.searchQuery.trim());
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
    if (!window.OwnVacatures) return;
    this.loading = true;
    this.updateUrl();

    const sort = SORT_PARAMS[this.sortBy] || SORT_PARAMS.date_desc;
    const params = new URLSearchParams({
      search: this.searchQuery,
      paged: this.currentPage,
      orderby: sort.orderby,
      order: sort.order,
      per_page: config.perPage || 10,
    });
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(taxonomy, term));
    });

    const results = document.querySelector("[data-own-vacatures-results]");
    if (results) results.style.opacity = "0.4";

    try {
      const response = await fetch(`${window.OwnVacatures.endpoint}?${params.toString()}`);
      const data = await response.json();
      if (!data.success) return;

      const pagination = document.querySelector("[data-own-vacatures-pagination]");
      const count = document.querySelector("[data-own-vacatures-count]");

      if (results) results.innerHTML = data.html;
      if (pagination) pagination.innerHTML = data.pagination_html || "";
      if (count) count.textContent = `${data.count} ${data.count === 1 ? "vacature" : "vacatures"} gevonden`;

      Object.entries(data.taxonomy_counts || {}).forEach(([taxonomy, counts]) => {
        if (!this.taxonomyFilters[taxonomy]) return;
        this.taxonomyFilters[taxonomy].options = this.taxonomyFilters[taxonomy].options.map((option) => ({
          ...option,
          count: counts[option.value] ?? 0,
        }));
      });
    } catch (error) {
      console.error("Error filtering vacatures:", error);
    } finally {
      this.loading = false;
      if (results) results.style.opacity = "";
    }
  },
});
