# Changelog

All notable changes to the OWN Framework theme. Newest first. Versioning
follows [semver](https://semver.org/); see "Versioning" in `README.md`.

## 1.5.0 — 2026-11-25

### Added

- **Hero: logo's naast de inhoud**: "Weergave" in the hero's Achtergrond
  panel (type "Geen — alleen kleur of verloop") gets a third option, "Logo's
  naast de inhoud", next to "Alleen inhoud" and "Afbeelding naast de inhoud".
  It shows one or more logos (a partner, a quality mark) to the right of the
  breadcrumb, title, text and buttons. Once logos are chosen, editors set
  the position (left or right of the content), the column split, the logo
  height in pixels (70% of that on mobile), next to each other or stacked,
  the spacing, horizontal and vertical alignment, the colour (original,
  grayscale, white or black) and whether to hide them on mobile. Logos keep
  their proportions and sit below the content on
  mobile. The former "Geen afbeelding" option is now called "Alleen inhoud".

## 1.4.0 — 2026-11-25

### Added

- **Pattern "Instagram-feed met titel"**: a ready-made section with a title
  on the left, a "Bekijk alle posts" button to the site's Instagram account
  on the right and the feed in a grid below. The button links to the
  connected account when the pattern is inserted; title, button text and
  feed settings stay editable.

### Changed

- **Instagram-feed block** without a connection or posts now shows the
  standard empty state ("Nog geen Instagram-posts") on the site instead of
  disappearing, so a title and button above it don't end up above nothing.
  In the editor it still says what's missing.

## 1.3.0 — 2026-11-25

### Added

- **Instagram-feed block**: shows the latest 3, 4, 6 or 8 posts of the
  Instagram account connected to the site. By default each post is a card
  like in the Instagram app (account name, photo or video cover, first two
  lines of the caption); "Alleen foto's" shows a square photo grid instead.
  Every post links to Instagram. Images are stored on the site itself, so nothing loads from Meta and
  the feed keeps working when Instagram's image links expire.
- **Developer page › Instagram**: one Meta app of OWN for every site. Per site
  you can create a single-use connect link for the school, connect yourself,
  refresh now or disconnect, and see the account, post count, last refresh,
  token expiry and errors. The site refreshes every hour and renews its token
  weekly. A broken connection keeps the last posts on the site and sends one
  e-mail.
- **Instagram relay** (`developer/instagram-relay/`): an optional Cloudflare
  Worker, so the Meta app needs one redirect URI for every
  installation instead of one per site or network. Set its address as
  Relay-URL on the developer page. It also answers Meta's deauthorize and
  data deletion callbacks, with a status page for the person who asked.
- **Thema opties › Instagram**: each site connects its own Instagram account,
  without network admin rights. Editors and administrators get access once
  (new capability `manage_theme_instagram`, switchable per role in User Role
  Editor). Shows the account, post count and problems in plain language, with
  buttons to connect, refresh, disconnect or make a connect link for whoever
  manages the account. Until OWN has set up the Meta app, the page says so.
- **Instagram data is removed automatically** when a site's connection has
  been invalid for 30 days (also after someone revokes the app or asks Meta
  for deletion). The developer page shows the date beforehand and
  "Automatisch ontkoppeld" afterwards.

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
