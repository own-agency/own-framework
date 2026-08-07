// Keeps --site-header-height in sync with the real, currently-rendered height
// of the STICKY part of the header (.own-header, the native Group in
// parts/header.html) — the topbar above it isn't sticky, so once a visitor
// has scrolled past it, it no longer occupies any space to account for.
// app.css's .is-position-sticky rule reads this to offset native sticky
// blocks below the header instead of under it.
const header = document.querySelector(".own-header");

if (header) {
  const setHeaderHeightVar = () => {
    document.documentElement.style.setProperty(
      "--site-header-height",
      `${header.offsetHeight}px`,
    );
  };

  setHeaderHeightVar();
  new ResizeObserver(setHeaderHeightVar).observe(header);
}
