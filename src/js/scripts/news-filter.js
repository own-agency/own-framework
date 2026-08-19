import Alpine from "alpinejs";

// Shared by two separate blocks — acf/news-hero (the category filter
// dropdown) and acf/news (view toggle/sort/results/pagination) — hence a
// global store (Alpine.store), not a local Alpine.data() component: state
// read/written from more than one block's own markup needs to live
// somewhere reachable from both, same reasoning as ./vacatures-filter.js's
// own $store.vacaturesFilters / ./agenda-filter.js's own
// $store.agendaFilters. Both blocks are restricted to templates/home.html
// (blocks/news-hero/hooks/restrict-to-home-template.php), so assuming both
// are present together is safe.
//
// No search box (unlike ./vacatures-filter.js) — this feature was built
// without one (agenda-hero's own shape, not vacature-hero's), so there's no
// "searchQuery" state here at all.
//
// Config (available taxonomies + which are active from the URL) comes from
// acf/news-hero's own "data-own-news-filters" attribute, read directly from
// the DOM at registration time. Must be imported before ./packages/
// alpine.js's own Alpine.start() call, same reason as every other store in
// this file's sibling scripts.

const SORT_PARAMS = {
  date_desc: { orderby: "date", order: "DESC" },
  date_asc: { orderby: "date", order: "ASC" },
  title_asc: { orderby: "title", order: "ASC" },
  title_desc: { orderby: "title", order: "DESC" },
};

// Mirrors render.php's own $sort_options labels — same trade-off
// ./vacatures-filter.js's own SORT_LABELS already accepts.
const SORT_LABELS = {
  date_desc: "Datum (nieuwste eerst)",
  date_asc: "Datum (oudste eerst)",
  title_asc: "Titel (A-Z)",
  title_desc: "Titel (Z-A)",
};

const configEl = document.querySelector("[data-own-news-filters]");
const config = configEl ? JSON.parse(configEl.dataset.ownNewsFilters || "{}") : {};
const taxonomyFilters = config.taxonomies || {};
const activeTerms = config.activeTerms || {};

const initialFilters = {};
Object.keys(taxonomyFilters).forEach((key) => {
  initialFilters[key] = Array.isArray(activeTerms[key]) ? [...activeTerms[key]] : [];
});

Alpine.store("newsFilters", {
  taxonomyFilters,
  filters: initialFilters,
  sortBy: "date_desc",
  currentPage: parseInt(new URLSearchParams(window.location.search).get("paged"), 10) || 1,
  loading: false,

  get sortLabel() {
    return SORT_LABELS[this.sortBy] || SORT_LABELS.date_desc;
  },

  init() {
    // Event delegation, same reason as ./vacatures-filter.js's own init():
    // apply() replaces the pagination markup wholesale via innerHTML.
    document.addEventListener("click", (event) => {
      const button = event.target.closest("[data-own-news-page]");
      if (!button) return;
      this.currentPage = parseInt(button.dataset.ownNewsPage, 10) || 1;
      this.apply();
      document.querySelector("[data-own-news-results]")?.scrollIntoView({ behavior: "smooth", block: "start" });
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
    // Bracketed "category[]", not a bare repeated "category=...": PHP only
    // keeps the LAST value for a repeated non-bracketed key once it
    // populates $_GET (verified directly — parse_str("category=a&category=b")
    // gives "b", not an array), so a genuine multi-select facet needs the
    // "[]" to survive as more than one value once this URL is loaded
    // server-side (pre_get_posts) or a REST fetch parses its own query
    // string the same way.
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });
    const url = window.location.pathname + (params.toString() ? `?${params.toString()}` : "");
    window.history.replaceState({}, "", url);
  },

  async apply() {
    if (!window.OwnNews) return;
    this.loading = true;
    this.updateUrl();

    const sort = SORT_PARAMS[this.sortBy] || SORT_PARAMS.date_desc;
    const params = new URLSearchParams({
      paged: this.currentPage,
      orderby: sort.orderby,
      order: sort.order,
      per_page: config.perPage || 10,
    });
    Object.entries(this.filters).forEach(([taxonomy, terms]) => {
      terms.forEach((term) => params.append(`${taxonomy}[]`, term));
    });

    const results = document.querySelector("[data-own-news-results]");
    if (results) results.style.opacity = "0.4";

    try {
      const response = await fetch(`${window.OwnNews.endpoint}?${params.toString()}`);
      const data = await response.json();
      if (!data.success) return;

      const pagination = document.querySelector("[data-own-news-pagination]");
      const count = document.querySelector("[data-own-news-count]");

      if (results) results.innerHTML = data.html;
      if (pagination) pagination.innerHTML = data.pagination_html || "";
      if (count) count.textContent = `${data.count} ${data.count === 1 ? "bericht" : "berichten"} gevonden`;

      Object.entries(data.taxonomy_counts || {}).forEach(([taxonomy, counts]) => {
        if (!this.taxonomyFilters[taxonomy]) return;
        this.taxonomyFilters[taxonomy].options = this.taxonomyFilters[taxonomy].options.map((option) => ({
          ...option,
          count: counts[option.value] ?? 0,
        }));
      });
    } catch (error) {
      console.error("Error filtering news:", error);
    } finally {
      this.loading = false;
      if (results) results.style.opacity = "";
    }
  },
});
