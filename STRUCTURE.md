# Theme structure

This is a **full block theme** (`wp_is_block_theme()` is `true` — WordPress
detects this purely from `templates/index.html` existing). `blocks/` already
holds a growing set of custom blocks (`hero`, `cta`, `content-blok`, `afbeeldingen-slider`,
`header*`, `footer*`) that double as the reference implementations for the
conventions below — `template-parts/` and `patterns/` are still empty, so
those two remain a description of the rule to follow, not existing content.

Being a full block theme has nothing to do with custom blocks: SCF/ACF blocks
(`blocks/<name>/block.json` + `render.php` + a matching `acf-json` field
group) work identically to how they always have — block registration and
rendering happen at the WordPress/Gutenberg level, independent of whether the
*theme* assembles pages via `templates/*.html` or classic PHP. What actually
changed is page *structure*: `templates/*.html` (block markup) replaced the
classic PHP templates, which unlocks the full native Ontwerp/Styles/Templates
editing experience in the Site Editor.

## The rule

> **A file lives next to its only consumer.**
> `template-parts/` is for markup with two or more consumers — nothing else.

That single rule decides every placement below. If you are unsure where
something goes, count its callers.

- **One caller** → it belongs inside that caller's folder.
- **Two or more** → `template-parts/`.

Don't split a block into `blocks/x/render.php` + `template-parts/components/x.php`
from the start — that pair has exactly one caller, so the indirection costs two
files per block and buys nothing. Extract to `template-parts/` only once a
*second* consumer genuinely appears.

---

## Layout

```
own-framework/
├── templates/              Block templates (HTML, not PHP) — the front end's
│   ├── index.html          entry point. index.html is the universal fallback
│   ├── single.html         (blog index, search, archives via Query "inherit")
│   ├── page.html           as well as the actual blog listing template.
│   └── 404.html            Add archive.html/category.html/author.html/
│                            search.html later for more specific designs —
│                            they're not required, index.html covers them.
│
├── parts/                   Site Editor template parts — header.html / footer.html
│                             (+ footer-versie-2.html, an alternate footer).
│                             Each is a thin wrapper composed of acf/header*
│                             and acf/footer* custom blocks, designed via
│                             Appearance → Ontwerp → Template-onderdelen.
│                             Every templates/*.html file references them via
│                             <!-- wp:template-part {"slug":"header"} /-->.
│
├── blocks/                 One folder per custom (SCF/ACF) block: hero, cta,
│   └── <name>/              content-blok, afbeeldingen-slider, header/
│                             header-topbar, footer/footer-bottom/
│                             footer-contact/footer-cta/footer-menu.
│       ├── block.json      Registration + field/supports config.
│       ├── render.php      Fetches fields AND renders markup. The only entry point.
│       ├── parts/          Sub-templates used ONLY by this block.
│       └── hooks/          Autoloaded PHP for this block (filters, REST routes).
│
├── template-parts/         PHP markup shared by 2+ callers — see "The rule"
│   ├── components/         above. Now only ever called from blocks/*/render.php
│   ├── shapes/             (get_template_part()), since templates/*.html can't
│   └── layout/             call PHP at all. Currently empty either way.
│
├── patterns/                WordPress block patterns ONLY. Currently empty.
│
├── inc/
│   ├── functions/          Theme-wide features and helpers. Autoloaded.
│   ├── core-blocks/        Changes to WordPress' OWN blocks. Autoloaded.
│   └── scf/                Editor/admin UX for SCF fields + the colour system. Autoloaded.
│
├── media/                  Static assets, served as-is: icons/ vectors/ logos/ images/.
├── fonts/                  Font files. Root by convention; @font-face paths point here.
├── src/                    Build INPUT — Tailwind CSS + JS. Compiled by Vite.
├── assets/                 Build OUTPUT. Generated, gitignored, never edited.
├── acf-json/               Synced SCF field groups. Keyed by group key, not path.
└── index.php               NOT used for rendering — exists only because WP
                             requires it + style.css to recognize a theme at
                             all. templates/index.html is the real entry point.
```

`live-search/` (`load.php` + `includes/`) is a real, standalone module — not a
block, not shared markup, so it lives at the theme root rather than in
`blocks/` or `template-parts/` (see "The rule" above: it has exactly one
consumer, `blocks/header/parts/search-modal.php`, but is configured
site-wide via a plain PHP config array in `load.php`, not per-caller). It
registers its own REST route and JS store independently of
`functions.php`'s block-registration glob.

---

## Block folder: four slots, four meanings

A block folder has at most four things in it, and each is unambiguous:

| Slot | What it is | Loaded how |
|---|---|---|
| `block.json` | Registration, supports, SCF config | `register_block_type()` in `functions.php` |
| `render.php` | Fields + markup, the block's output | SCF, via `acf.renderTemplate` |
| `parts/*.php` | Sub-templates for this block only | `get_template_part()` at render time |
| `hooks/*.php` | Filters, REST routes, editor config | Autoloaded at load time |

`hooks/` is deliberately narrower than the block folder itself. `render.php` and
`parts/` are **templates** — they emit output when called and must never be
`require_once`'d at load time. Keeping autoloaded PHP in its own subfolder makes
that mistake impossible, which is why the glob in `functions.php` is
`blocks/*/hooks/*.php` and not `blocks/*/*.php`.

A custom block category (`own-framework`) is already registered in
`functions.php` — use it in every new block's `block.json`. WordPress' own
bundled/wordpress.org patterns are disabled
(`inc/functions/block-patterns.php`), so the Patterns tab starts empty too,
ready for your own.

---

## Autoloading

`functions.php` holds one list of glob patterns. Adding a directory means adding
one line to it. PHP's `glob()` does **not** treat `**` as recursive — that's a
shell/globstar feature — so every directory needs its own explicit pattern.

```php
$ownframework_autoload = [
    "/inc/functions/*.php",
    "/inc/core-blocks/*.php",
    "/inc/scf/*.php",
    "/blocks/*/hooks/*.php",
    "/live-search/load.php",
];
```

---

## "Pattern" means exactly one thing per folder

| Path | Meaning |
|---|---|
| `patterns/` | WordPress block patterns (the inserter's Patterns tab) |
| `template-parts/shapes/` | PHP that renders decorative SVG line artwork |
| `media/vectors/` | The `.svg` source files that artwork inlines |

All three are currently empty — keep them distinct as you populate them; don't
let "pattern" drift back to meaning more than one thing per folder.

---

## Adding things

**A new block** — create `blocks/<name>/` with `block.json` and `render.php`.
Registration is automatic (`functions.php` globs `blocks/*/block.json`). Put
markup directly in `render.php`. Only reach for `parts/` if the block needs
sub-templates, and only reach for `template-parts/components/` if a *second*
consumer appears.

**Shared markup** — move a file into `template-parts/components/` at the moment
it gains a second caller, not in anticipation of one.

**A theme-wide feature** — add a file to `inc/functions/`. No `require` needed.

**A change to a core WP block** — add a file to `inc/core-blocks/`. Core blocks
have no `block.json` you can edit, so these use the
`block_type_metadata_settings` / `block_type_metadata` filters.

**A brand colour** — added once, in `theme.json`'s `settings.color.palette`, or
directly by a client via the Site Editor's Ontwerp → Kleuren panel (which
edits the same data, stored per-site in the `wp_global_styles` post type).
Not hand-rolled per block. Both `ownframework_get_scf_colors()`
(`inc/scf/color-selector.php`, the `theme_color_selector` ACF field type's
swatches) and `ownframework_get_theme_json_colors()`
(`inc/scf/custom-color-palette.php`, the TinyMCE colour picker) read the same
resolved palette via `wp_get_global_settings(['color', 'palette'])` — the same
source Gutenberg's own colour pickers use, which includes whatever a client
sets via Ontwerp. A colour added there shows up in all of these
automatically; there's nothing else to wire up.

The color selector field type carries no hardcoded default colours anymore —
without an explicit "Allowed Colors" setting on a field, it shows every
standard (white/black/transparent) and theme colour that currently exists.

**A Tailwind utility colour** (structural/neutral, not brand) — still a
separate, static system: a `--color-*` variable in `src/css/app.css`'s
`@theme` block generates the Tailwind class. A new colour *family* also needs
adding to the `@source inline(...)` safelists there, or dynamically-built
class names (`'bg-' . $token`) get tree-shaken out of the build. Don't use this
system for brand colours — see "Brand identity" below for why.

---

## Brand identity: native Global Styles, not a custom options page

There is no ACF "brand settings" page in this theme. A client's colours,
fonts, shadows and background settings are all set through the Site Editor's
own "Ontwerp" (Styles) panel — Global Styles, WordPress' own per-site,
database-backed brand-settings system (stored in the `wp_global_styles` post
type, one per site, so it's multisite-safe by construction). This is
deliberate: a previous iteration of this theme had a custom options page that
generated a standalone CSS file instead, completely disconnected from the
block editor's own colour/font pickers — editors saw default WP colours
everywhere except inside custom blocks. Don't reintroduce that split.

`theme.json` seeds:

- a starter colour palette (`base` / `contrast` / `primary` / `secondary`),
  with `settings.color.defaultPalette`/`defaultGradients`/`defaultDuotone` set
  `false` so WP's own generic default swatches don't clutter the picker
  alongside it;
- a heading/body font pair, already bundled (`fonts/`,
  `src/css/components/typography.css`'s `@font-face` rules);
- a fluid font-size scale (`small` … `xx-large`);
- shadow presets (`natural` / `deep` / `sharp`);
- `settings.background.backgroundImage` enabled.

All of it is edited via Ontwerp with zero code changes — adding a *new*
preset (a colour, a font, a shadow) means adding it to `theme.json`'s
`settings.*`, not building a field for it.

**Fonts stay in sync between Global Styles/Font Library and Tailwind** via one
indirection: `src/css/app.css`'s `--font-headings`/`--font-sans` are defined as
`var(--wp--preset--font-family--heading/body, ...)`, not a literal font name.
WordPress always emits a `--wp--preset--font-family--<slug>` custom property
per `fontFamilies` entry, so changing a font via Ontwerp or the native Font
Library reaches every Tailwind class built on `font-headings`/`font-sans`
automatically.

**Colours aren't unified the same way, and can't be as simply** — see "A
Tailwind utility colour" above. There's no 1:1 mapping from a flat Global
Styles palette entry to a 10-shade Tailwind ramp the way there is for a
single font family.

There is no secondary logo mechanism — only the native Site Identity /
`add_theme_support('custom-logo')` single logo slot. If a project genuinely
needs a second logo variant, that's a deliberate future addition.

---

## Style settings: radius & spacing

One deliberate exception to "no custom options page":
`acf-json/group_own_style_settings.json` adds a "Stijlen" SCF options page
(Thema opties → Stijlen) with two site-wide dials, each read via
`get_field('<name>', 'option')['scale']`:

- **Radius** — none / small / medium / large. `inc/functions/radius-scale.php`
  overrides Tailwind v4's `--radius-sm…3xl` custom properties on `:root` via
  `wp_add_inline_style()` (front end and editor both), since every
  `rounded-*` utility already reads those variables rather than a baked-in
  value. No theme.json preset scale exists for border-radius to hook into
  instead — Gutenberg's own Radius control is a free slider — so a CSS
  variable override is the *only* available site-wide mechanism, not a
  workaround. Convention: a card-style block's outer element uses
  `rounded-2xl`, its inner media one step down at `rounded-xl` (see
  `blocks/cta`, `blocks/hero`).
- **Spacing** — compact / standard / spacious. `inc/functions/spacing-scale.php`
  hooks `wp_theme_json_data_theme` to replace `settings.spacing.spacingSizes`
  in the *already-merged* theme.json data (`get_data()` → mutate one key →
  `update_with()`, never a fresh partial array). Unlike radius, this
  genuinely is native Global Styles data — theme.json already has a
  `spacingSizes` preset scale that the editor's own Spacing panel reads
  from, so the site's chosen dial shows up in that native picker too, not
  just on the front end.

This doesn't reopen the standalone-CSS-file problem described above: radius
has nothing native to disconnect from, and spacing's filter feeds the same
native picker Ontwerp itself reads. Don't extend this pattern to colours,
fonts or shadows — those stay exclusively in Ontwerp.

---

## Page rendering

There is no PHP in the rendering path anymore — no `the_content()`, no Loop.
`templates/single.html` and `templates/page.html` use the native
`core/post-title` and `core/post-content` blocks; `templates/index.html` uses
`core/query` with `"inherit":true` (so it doubles as the blog index *and* the
fallback for search/date/tag/custom-taxonomy archives — WordPress' own
"inherit the main query" convention, same as core block themes). There is no
shared "split hero from body" helper anymore — that was a previous iteration's
PHP-only mechanism and it doesn't translate to block templates; a hero-style
block just goes directly in the template, or in a pattern.

`core/post-content` gets `"className":"prose"` and each template's root
`core/group` gets `"className":"main-own-content"` directly in the block
markup, so Tailwind Typography's `.prose` rules
(`src/css/components/typography.css`, scoped to `main.main-own-content .prose`)
are active out of the box — unlike the classic-PHP version of this theme,
where that wrapper had to be added by hand and wasn't.

`<main>` is `layout:{type:"constrained"}` and `core/post-content` inside it is
`align:full` with its OWN `layout:{type:"constrained"}` — that nesting, plus
`theme.json`'s `settings.useRootPaddingAwareAlignments: true` and a matching
`styles.spacing.padding` (left/right, `spacing|s`), is what gives a plain
block placed directly in page content a side gutter on narrow viewports.
Without that setting, a "constrained" block only gets a `max-width` +
`margin:auto` — nothing caps it from the viewport edge below
`settings.layout.contentSize` (1100px), so on any phone-width screen it would
sit flush against both edges.

No custom block supports `align:"full"` any more — `hero`, `cta` and
`afbeeldingen-slider` all had it removed from their own `supports.align`
(only `""`/`"wide"` remain), and `header`/`header-topbar`/`footer`/
`footer-bottom` never had it. `hero`/`cta`/`afbeeldingen-slider` now get their
side gutter from the SAME `useRootPaddingAwareAlignments` mechanism above,
same as `blocks/content-blok` or a bare `core/paragraph` — no more
`align:full`-specific exception for them. A block whose BACKGROUND still
needs to
reach the screen edges despite that (`hero`, always; `cta`, via its
"Achtergrond over volle breedte" toggle) does it with its own CSS trick
instead — a `position:absolute` layer centered on its own section
(`left-1/2`/`w-screen`/`-translate-x-1/2`, see `blocks/hero/render.php`'s
`$bleed_class`) — not through WordPress' align system at all.
`header`/`header-topbar`/`footer`/`footer-bottom` don't need that trick:
they're template parts, rendered outside `core/post-content`'s constrained
layout entirely, so their own wrapper is already naturally edge-to-edge —
their own `align` attribute (a plain attribute + custom `SelectControl`, not
the native toolbar control; see the theme's `CLAUDE.md`, Traps #11) only ever
picks their INNER content's width, via `.content-width`/`.wide-width`
(`src/css/app.css`) — never `.container`, which bakes its own padding into
the same box and would make that content sit further in than `hero`/`cta`'s
at the same width (`CLAUDE.md`, Traps #12). Their side gutter comes from a
separate `.gutter-x` utility on their own (already edge-to-edge) outer
wrapper instead — see `CLAUDE.md`'s "Alignment, width and full-bleed
backgrounds" section for the full reasoning behind that split.

---

## Naming: what you must NOT rename

Once blocks/fields exist, some names become **stored data**. Renaming them
orphans live content or breaks URLs. They are not style choices:

| Name | Stored where | Renaming it |
|---|---|---|
| Block name (e.g. `acf/statistiek`) | `post_content`, as `<!-- wp:acf/statistiek -->` | Every existing instance becomes an "invalid block" |
| Block folder | must mirror the block name | Desyncs folder from block; keep them equal |
| ACF field name (e.g. `statistiek_title`) | `postmeta` keys | Field values become unreachable |
| ACF group key (e.g. `group_block_statistiek`) | `acf-json` + DB | Field group detaches |
| Post type / taxonomy slug | DB + URLs | Breaks permalinks |

There are no blocks yet, so nothing is locked in right now — but pick every
block/field/group name carefully once you start, since renaming any of the
above later is destructive.

Freely renameable (internal references only, resolved at load or render time):
files under `template-parts/`, `inc/`, `src/`, and `media/`. Update the
`get_template_part()` paths or `@import`s that point at them and you're done.

---

## Comments

Short but clear. A comment earns its place by explaining something the code
cannot:

- **Keep**: why a workaround exists and what breaks without it; WordPress /
  Gutenberg / ACF / browser quirks; non-obvious ordering ("must run before X",
  "this hook not that one"); why an unguessable number was chosen (SVG crop
  offsets, pattern `shift` values); the `$args` contract of a shared part.
- **Cut**: restating what the code plainly does; historical narrative ("this
  used to be X, which was fragile" — state the current rule, not the journey);
  tutorial explanations of standard behaviour; `@param`/`@return` that only echo
  the type hints.

One caveat when editing comments: comments inside a PHP *string* — e.g. the JS
in `wp_add_inline_script()` in `functions.php` — are code, not comments. Leave
them alone. `php -w <file>` strips real comments, so comparing its output before
and after an edit proves you changed nothing else.

---

## Assets: three folders, three jobs

Easy to conflate, so worth stating plainly:

| Folder | Job | Committed? |
|---|---|---|
| `src/` | Build **input** — Tailwind CSS and JS, compiled by Vite | yes |
| `assets/` | Build **output** — hashed CSS/JS + `manifest.json` | no, gitignored |
| `media/` | Static files served **as-is** — SVG, images, logos | yes |
| `fonts/` | Font files, loaded via `@font-face` and `<link rel=preload>` | yes |

`fonts/` stays at the theme root rather than under `media/`: the `@font-face`
rules in `src/css/components/typography.css` use absolute paths, and
`vite.config.ts` marks that exact path `external` so Vite leaves the files
alone instead of bundling them. Those two facts are coupled — moving the folder
means changing both.

Nothing reads `assets/` directly. PHP resolves built filenames through
`assets/.vite/manifest.json` (see `inc/functions/enqueue-scripts-styles.php`),
which is why the hashed names never appear in templates.

`src/js/` has two Vite entries, not one: `main.js` (front end,
`wp_enqueue_scripts`) and `editor-scripts.js` (block editor only,
`enqueue_block_editor_assets`). They deliberately don't share the same
bundle — `main.js` also runs
`Alpine.start()` plus `header.js`/`live-search.js`, which broke the
`MediaUpload` picker when loaded into the editor's reduced single-block DOM.
`editor-scripts.js` only imports the specific per-block scripts that must
also run inside the editor preview (currently `hero.js`, `image-slider.js`,
both Swiper-driven). Since both entries import the same script files, they
share a Vite chunk — `vite.config.ts`'s `chunkFileNames` and a
`script_loader_tag` filter forcing `type="module"` on both enqueued handles
exist specifically to make that sharing work; don't remove either while both
entries still share code.
