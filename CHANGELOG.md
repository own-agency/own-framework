# Changelog

All notable changes to the OWN Framework theme. Newest first. Versioning
follows [semver](https://semver.org/); see "Versioning" in `README.md`.

## 1.2.0 — 2026-10-19

### Added

- **Vacature hero › Zoekveld › Tekstkleur**: the search field has its own text
  colour, separate from the hero's. The placeholder, search icon and clear
  button use a lighter tint of it. Unset, the text is the palette's contrast
  colour, so a hero with white text no longer gives white text on the white
  field.
- **Vacancy filters on mobile** open in a drawer that looks and works like the
  mobile menu, mirrored: slides in from the left over the header, fading
  backdrop, closes on Escape, backdrop or ×, page scroll locked while open.
- **Vacancy "Filters" button on mobile** restyled to match the sort button
  (white, rounded, gray text) with a filter icon; a round badge in the accent
  colour shows how many filters are active.

### Changed

- **Side padding on phones** (below 640px) is a fixed 1rem everywhere —
  header, topbar, page content, footer — instead of following the Stijlen
  spacing scale (24–36px). From 640px up nothing changes.
- **Mobile menu**: the active page is underlined instead of coloured. A
  parent's "Ga naar …" link is only marked when it is the current page.
- **Mobile menu**: "Snel naar:" only appears when the topbar menu has items;
  with no quick links and no CTA the bottom strip is left out.
- **Mobile menu**: the "Snel naar:" links are light gray pills in the menu's
  own text colour, instead of taking the topbar's colours (white text on a
  barely visible background when the topbar has light text).
- **Archive toolbars on phones** (news, agenda, vacatures, verhalen): the
  post count sits on its own line and the sort button is full width below it,
  on one line.
- **Long titles on phones** hyphenate instead of running off the screen. The
  breadcrumb stays on one line and truncates the current page with "…".
- **Vacancy card badges** are smaller on phones.
- **Buttons on phones** are a little smaller: padding at 75% and text at about
  94% of what Ontwerp sets (Elementen › Knop, Blokken › Knop and its style
  variations). Ontwerp and per-button settings in the editor are untouched.
- **Button hover** fades in more slowly (0.45s instead of 0.2s).

### Fixed

- Pages with a slider were laid out far wider than the screen on phones, so
  the browser zoomed out and the whole page looked tiny.
- Sliders with images broke on mobile on sites running Smush lazy load; slider
  images now skip lazy loading.
- The image slider with thumbnails broke when a tablet was rotated from
  landscape to portrait: the thumbnail strip stayed vertical and the
  thumbnails collapsed to a thin band until the page was reloaded.
- The search and menu icons in the mobile header didn't line up with the right
  edge of the content.
- A notification popup covered the bottom of the open mobile menu.
- Sticky columns kept sticking on phones once columns are stacked (below
  782px), sliding over the content beneath them.
