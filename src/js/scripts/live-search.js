import Alpine from "alpinejs";

// Registers 2 global stores rather than local x-data on <header> (own
// mobileMenuOpen's own pattern, blocks/header/render.php): the search dialog
// has to open from ⌘K/Ctrl+K pressed anywhere on the page (search-modal.php's
// own window keydown listeners), not just from a click inside header's own
// Alpine scope — a global store is reachable from anywhere, header's own
// x-data isn't. Must be imported before ./packages/alpine.js in main.js: that
// file's own Alpine.start() call requires every store/component already
// registered by the time it runs, or markup using $store.searchDialog/
// $store.liveSearch on first paint initialises against undefined.

Alpine.store("searchDialog", {
  isOpen: false,

  open() {
    this.isOpen = true;
  },

  close() {
    this.isOpen = false;
    Alpine.store("liveSearch").clear();
  },

  toggle() {
    this.isOpen ? this.close() : this.open();
  },
});

Alpine.store("liveSearch", {
  query: "",
  results: [],
  total: 0,
  loading: false,
  searched: false,
  error: null,
  _timer: null,

  search() {
    const q = this.query.trim();
    if (q.length < window.OwnLiveSearch.minChars) {
      this.results = [];
      this.searched = false;
      return;
    }

    clearTimeout(this._timer);
    this._timer = setTimeout(async () => {
      this.loading = true;
      this.error = null;
      try {
        const url = `${window.OwnLiveSearch.endpoint}?q=${encodeURIComponent(q)}`;
        const res = await fetch(url, {
          headers: { "X-WP-Nonce": window.OwnLiveSearch.nonce },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        this.results = data.results ?? [];
        this.total = data.total ?? 0;
      } catch (err) {
        this.error = err.message;
        this.results = [];
      } finally {
        this.loading = false;
        this.searched = true;
      }
    }, window.OwnLiveSearch.debounce);
  },

  // own-search-highlight (header.css) supplies the visual tint — this only
  // wraps the match, same currentColor-friendly split as elsewhere in the
  // header (own-header-icon-btn, own-mobile-menu__quicklink).
  highlight(text) {
    if (!window.OwnLiveSearch.highlight || !text) return text;
    const q = this.query.trim();
    if (!q) return text;
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    return text.replace(
      new RegExp(`(${escaped})`, "gi"),
      '<mark class="own-search-highlight">$1</mark>',
    );
  },

  clear() {
    this.query = "";
    this.results = [];
    this.searched = false;
    this.error = null;
  },
});
