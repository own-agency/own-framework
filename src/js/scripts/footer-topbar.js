import Alpine from "alpinejs";

// Local Alpine.data() component, not a global Alpine.store — acf/footer-topbar
// is its own single consumer (parts/footer.html), unlike vacatures-filter.js's
// store which two separate blocks read/write. Config (the other network sites,
// current site already excluded — render.php) comes from this block's own
// "data-own-footer-topbar-sites" attribute, read from $el in init() rather
// than passed in, same convention as vacatures-filter.js's own config
// attribute. Must be registered before ./packages/alpine.js's own
// Alpine.start() call, same reason as every other Alpine.data()/store in this
// file's sibling scripts.
Alpine.data("footerTopbarSwitcher", () => ({
  open: false,
  query: "",
  sites: [],

  init() {
    this.sites = JSON.parse(this.$el.dataset.ownFooterTopbarSites || "[]");
  },

  get filtered() {
    const query = this.query.trim().toLowerCase();
    if (query === "") return this.sites;
    return this.sites.filter((site) => site.name.toLowerCase().includes(query));
  },

  toggle() {
    this.open = !this.open;
    if (this.open) {
      this.$nextTick(() => this.$refs.search?.focus());
    }
  },
}));
