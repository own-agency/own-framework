# own-framework

Full block theme (`templates/index.html` exists, so `wp_is_block_theme()` is
`true`). Tailwind CSS v4 + Vite, Alpine.js, Secure Custom Fields. UI strings
are Dutch. Full layout reference: `STRUCTURE.md`.

Custom blocks (`blocks/hero`, `blocks/cta`, `blocks/content-blok`,
`blocks/afbeeldingen-slider`, `blocks/header*`, `blocks/footer*`) already
exist and are the reference
implementations for the conventions below — `template-parts/` and `patterns/`
are still empty. Header/Footer are designed as Site Editor template parts
that themselves render these custom blocks, not blank areas.

Being a full block theme is about page *structure* only — `templates/*.html`
instead of classic PHP templates, unlocking the native Ontwerp/Styles/
Templates editing experience. It has no bearing on custom blocks: SCF/ACF
blocks (`blocks/<name>/block.json` + `render.php` + `acf-json`) register and
render exactly the same way regardless. Don't assume "full block theme" means
"no PHP" — `render.php` files are still PHP, still the way to build a custom
block, `functions.php`/`inc/` are unchanged. Only the top-level page templates
became `.html`.

## The placement rule

> **A file lives next to its only consumer.** `template-parts/` is for markup
> with two or more consumers — nothing else.

Count the callers: one → it belongs inside that caller's folder; two or more →
`template-parts/`.

Do **not** create a `template-parts/components/<x>.php` that mirrors a single
block — that indirection was deliberately removed from this theme's history.
A block's markup goes directly in its `render.php`. Extract to
`template-parts/components/` only when a *second* consumer actually appears,
not in anticipation of one.

## Never rename these — they are stored data

Once blocks/fields exist, renaming any of these orphans live content or
breaks URLs. Not style choices:

| Name | Stored where |
|---|---|
| Block name (e.g. `acf/statistiek`) | `post_content` as `<!-- wp:acf/statistiek -->` |
| Block folder | must mirror the block name — keep them equal |
| ACF field name (e.g. `statistiek_title`) | `postmeta` keys |
| ACF group key (e.g. `group_block_statistiek`) | `acf-json` + DB |
| Post type / taxonomy slug | DB + permalinks |

Freely renameable (internal references only): files under `template-parts/`,
`inc/`, `src/`, `media/`. Update the `get_template_part()` paths or `@import`s
that point at them.

## Blocks

One folder per block, four slots, nothing else:

```
blocks/<name>/
  block.json     registration + SCF config
  render.php     get_field() AND markup — the only entry point
  parts/         sub-templates for THIS block only
  hooks/         autoloaded PHP (filters, REST routes, editor config)
```

Registration is automatic — `functions.php` globs `blocks/*/block.json`. The
autoload glob is `blocks/*/hooks/*.php`, deliberately narrower than
`blocks/*/`: `render.php` and `parts/` are templates and must never be
`require_once`'d.

In `render.php`: use `get_field()` (never `get_sub_field()` — there is no
flexible content field), and always emit `get_block_wrapper_attributes()` on the
root element or editor alignment/spacing/position controls won't apply.

A custom block category is already registered — use
`"category": "own-framework"` in `block.json`. Custom WordPress-bundled patterns are
disabled (`inc/functions/block-patterns.php`), so the inserter's Patterns tab
only ever shows this theme's own.

Currently available helpers: `ownframework_get_icon($name, $class)` (reads
`media/icons/<name>.svg`, see `inc/functions/icons.php`) and
`ownframework_resolve_block_image(array $image): array` (resolves a plain
`"image"` block attribute's url/alt via its attachment ID, with a fallback for
an image without one — see `inc/functions/image-helpers.php`, shared by
`blocks/cta` and `blocks/content-blok`, extracted once a second block needed
the exact same logic). There is no `bg_class`/`pattern_color_class`/
`split_content_blocks`/`research_query` helper anymore; those existed in a
previous iteration of this theme and were removed along with the content
that used them. Add equivalents to `inc/functions/` yourself if a new block
needs that kind of shared logic — don't assume they still exist beyond the
two named above.

## Alignment, width and full-bleed backgrounds

Every custom block's own "align" is now just **`""` (content width) or
`"wide"`** — no block supports `"full"` any more (removed from `hero`/`cta`/
`afbeeldingen-slider`; `header`/`header-topbar`/`footer`/`footer-bottom` never
had it). An editor always picks between two widths, never a raw "edge to
edge" option, and every block's background/content behaviour follows from
that single fact plus where the block actually lives.

**Which UI produces the align choice depends on where the block lives:**

- **Blocks inside page content** (`hero`, `cta`, `afbeeldingen-slider` — i.e.
  anything nested in `core/post-content`) use the NATIVE `supports.align:
  ["wide"]` toolbar control. This only works because `core/post-content`
  itself is `layout:{"type":"constrained"}` — see Traps #11 below for why
  that ancestor is required and not automatic.
- **Template-part blocks** (`header`, `header-topbar`, `footer`,
  `footer-bottom`) can never get that native control (Traps #11) — each has a
  plain `align` attribute instead, plus its own custom `SelectControl` in
  `editor.js` (e.g. `header`'s "Breedte inhoud", same pattern as its existing
  `menuAlignment` control).

Either way, `render.php` just reads `$block['align'] ?? ''` — same attribute
name, same two values, only the editor UI mechanism differs.

**Width and gutter are two separate CSS concerns, on purpose** (`src/css/
app.css`):

- `.content-width` / `.wide-width` — `max-width` + `margin-inline:auto`
  only, no padding. Picks the box's WIDTH.
- `.gutter-x` — `padding-inline: var(--wp--preset--spacing--s)` only. Picks
  the side GUTTER, applied to whichever element is genuinely edge-to-edge
  (a template-part's own outer wrapper) — never to the width-constrained box
  itself.
- `.container` (older, still `max-width` + padding-inline combined in one
  box) exists ONLY for `hero`/`cta`'s legacy, already-saved `align:"full"`
  content from before `"full"` was removed. Don't reach for it for anything
  new — see Traps #12 for exactly why that combination breaks for a block
  that ISN'T genuinely edge-to-edge already.

**A block whose BACKGROUND still needs to be edge-to-edge despite a
constrained width** — impossible via core's align system now, since nothing
supports `"full"` — uses its own CSS trick instead: `hero`'s `$bleed_class`
(`absolute inset-y-0 left-1/2 w-screen -translate-x-1/2 overflow-hidden`), a
plain "full-bleed" centering trick that's unconditional for `hero` (its whole
point) and OPTIONAL for `cta` (the "Achtergrond over volle breedte" toggle,
`$use_bleed` in its `render.php`). Content always stays inside the block's
own `align`-picked width regardless — only the background/media layer bleeds
to 100vw. `header`/`header-topbar`/`footer`/`footer-bottom` never need this
trick at all: sitting outside `core/post-content`'s constrained layout, their
outer wrapper is ALREADY naturally edge-to-edge, so a plain background-colour
there reaches the screen edges with zero extra code.

## Brand identity: native Global Styles, not a custom options page

There is **no ACF "brand settings" page** in this theme, and there shouldn't
be one. A client's colours, fonts, shadows and background settings are set
entirely through the Site Editor's own "Ontwerp" (Styles) panel — Global
Styles, WordPress' own per-site, database-backed brand-settings system. This
is deliberate: a previous iteration of this theme had a custom options page
that generated a standalone CSS file, completely disconnected from the block
editor's own colour/font pickers — editors saw default WP colours everywhere
except inside custom blocks. Don't reintroduce that split. If a client needs
a *new* colour, font, shadow or spacing size, add its **preset** to
`theme.json`'s `settings.*` (so it's a first-class option everywhere) — don't
build a field for it.

`theme.json` seeds a starter palette (`base`/`contrast`/`primary`/`secondary`),
a heading/body font pair (already bundled — see `fonts/` and
`src/css/components/typography.css`'s `@font-face` rules), a fluid font-size
scale, and shadow presets, all editable via Ontwerp with zero code changes.
`settings.color.defaultPalette`/`defaultGradients`/`defaultDuotone` are set
`false` so WP's own generic default swatches don't clutter the picker
alongside this theme's actual palette.

**Fonts stay in sync between Global Styles/Font Library and Tailwind** via one
indirection: `src/css/app.css`'s `--font-headings`/`--font-sans` are defined as
`var(--wp--preset--font-family--heading/body, ...)`, not a literal font name.
WordPress always emits a `--wp--preset--font-family--<slug>` custom property
per `fontFamilies` entry, so changing a font via Ontwerp or the native Font
Library reaches every Tailwind class built on `font-headings`/`font-sans`
automatically — no further change needed anywhere else.

**Colours are not unified the same way, and can't be as simply**: Tailwind's
own colour scale in `app.css`'s `@theme` block (`paars-500` etc. — legacy
naming, not the current palette) is a *separate*, build-time system, compiled
once and shared across every site in the network. There's no 1:1 mapping
from a flat Global Styles palette entry to a 10-shade Tailwind ramp the way
there is for a single font family. If a new block needs to render a brand
colour, don't reach for a Tailwind utility class for it — use the CSS custom
property Gutenberg already emits for a palette entry
(`var(--wp--preset--color--<slug>)`), or a native colour control
(`get_block_wrapper_attributes()` + the block's own `color` support), so it
stays dynamic per site. Save Tailwind utilities for structural/neutral
styling that doesn't need to vary by brand.

The custom `theme_color_selector` ACF field type
(`inc/scf/color-selector.php`) — for a *block's own* colour field, e.g. "Achtergrondkleur" —
and the TinyMCE colour sync (`inc/scf/custom-color-palette.php`) both read the
palette via `wp_get_global_settings(['color', 'palette'])`, the same resolved
source Gutenberg's own pickers use. A colour a client adds via Ontwerp shows
up in both automatically.

There is no secondary logo mechanism — only the native Site Identity /
`add_theme_support('custom-logo')` single logo slot (in `functions.php`). If a
project genuinely needs a second logo variant (e.g. for a dark header), that's
a deliberate future addition, not something to assume exists.

## Style settings: radius & spacing (the one deliberate exception)

`acf-json/group_own_style_settings.json` adds a "Stijlen" SCF options page
(Thema opties → Stijlen, sibling of "Algemeen") with two dials: **Radius**
(none/small/medium/large, `get_field('radius', 'option')['scale']`) and
**Spacing** (compact/standard/spacious,
`get_field('spacing', 'option')['scale']`). This looks like the custom
options page the "Brand identity" section above argues against — it isn't,
for a reason specific to each field:

- **Spacing** (`inc/functions/spacing-scale.php`) hooks
  `wp_theme_json_data_theme` to overwrite `settings.spacing.spacingSizes` at
  runtime, fetching the existing data via `get_data()` and replacing only that
  key before calling `update_with()` — never construct a fresh partial array,
  that would wipe colours/fonts/shadows. This is genuinely still "native
  Global Styles data", just injected by PHP instead of typed into Ontwerp, so
  the editor's own Spacing preset picker reflects the site's chosen scale —
  no disconnect from the block editor's own UI, which is exactly what the
  "Brand identity" section is protecting against. `theme.json`'s own static
  `spacingSizes` is only a pre-SCF-activation fallback; this filter always
  wins once the field group is active.
- **Radius** (`inc/functions/radius-scale.php`) has no theme.json preset
  scale to hook into — Gutenberg's own border-radius control is a free slider,
  not a choice list. Tailwind v4's `rounded-*` utilities already read
  `var(--radius-sm/md/lg/xl/2xl/3xl)` rather than baked-in values (confirmed
  in compiled CSS), so this function only overrides those custom properties on
  `:root`, via `wp_add_inline_style()` on both `theme-styles` and
  `theme-styles-editor` at priority 20. Every `rounded-*` class in the theme,
  present and future, follows automatically — no block needs touching.
  `rounded-full` (pills/avatars) is a fixed pixel value in Tailwind itself, not
  var-driven, so it always stays a pill regardless of this setting.

Current radius→Tailwind-step convention (`radius-scale.php`'s "medium"/default
row): a card-style block's outer boxed element uses `rounded-2xl`, its inner
media uses `rounded-xl` — one step down. `blocks/cta` and `blocks/hero` both
follow this; match it in new blocks rather than introducing a third radius
step.

Don't add a third custom-options-page field for anything else color/font/
shadow-shaped — those go through Ontwerp exactly as the "Brand identity"
section says. This exception exists only because radius and spacing are
site-wide *scales*, not colours, and one of the two genuinely feeds the native
picker.

## Traps that fail silently

1. **Alpine attributes must be single-line, arrow-free, `esc_attr()`-wrapped.**
   Block output passes through `the_content`'s `wpautop()`/`wptexturize()`, which
   splice stray `<br>` into multi-line attribute values and mangle `=>`.
2. **Dynamic Tailwind class names are invisible to the scanner.** Blocks build
   them as `'bg-' . $token`, so Tailwind can't see them. A new colour *family*
   must be added to the `@source inline(...)` safelists at the top of
   `src/css/app.css` or its utilities get tree-shaken out. This only applies to
   Tailwind's own static palette — see "Brand identity" above for why brand
   colours shouldn't go through Tailwind classes at all.
3. **`<main>` needs the `main-own-content` class** for the theme's `.prose` rules
   (`src/css/components/typography.css`) to apply, and `core/post-content`
   needs a `prose` className. `templates/single.html` and `templates/page.html`
   already set both directly in the block markup — copy that pattern in any
   new template rather than assuming it's automatic.
4. **Comments inside a PHP string are code**, not comments — e.g. the JS in
   `wp_add_inline_script()` in `functions.php`. Leave them alone.
5. **`patterns/*.php` docblocks are WordPress pattern headers** (`Title:`,
   `Slug:`, `Categories:`), parsed to register the pattern, once you add any.
   Rewriting them changes the inserter. Not prose.
6. **`ownframework_get_icon()` returns `''` for a missing file** — a typo'd or
   absent icon renders nothing at all, with no warning. Confirm
   `media/icons/<name>.svg` exists before referencing it.
7. **`get_block_wrapper_attributes()` auto-injects native `supports.color`
   styling onto whatever element it decorates** — independent of any manual
   style logic in `render.php`. If a block needs its own colour-support
   element to be a boxed *sub*-element rather than the root wrapper (e.g.
   `blocks/cta`'s card, not its full-width `<section>`), the native "Kleuren"
   panel can't reach that sub-element — it always targets the wrapper. Give
   the sub-element its own plain attribute + a custom `PanelColorSettings`
   panel in `editor.js` instead (see `blocks/cta`'s `cardBackgroundColor` +
   "Kleur kaart" panel, same pattern as `blocks/hero`'s pagination/nav colour)
   — don't expect `supports.color` to cascade down to it.
8. **A block whose front-end JS must also run inside the block editor's
   preview** (Swiper sliders, anything needing `initOnAppearance()` from
   `src/js/packages/init-on-appearance.js`) needs its script loaded via
   `enqueue_block_editor_assets`, not just `wp_enqueue_scripts` — the editor
   iframe never loads the front end's own enqueue. Don't just point that hook
   at `main.js`: `main.js` also runs `Alpine.start()` plus `header.js`/
   `live-search.js`, which misbehave against the editor's reduced single-block
   DOM (this broke the `MediaUpload` "choose image" button once already).
   Use the dedicated `src/js/editor-scripts.js` Vite entry instead — it only
   imports the specific per-block scripts that genuinely need to run in both
   places (currently `hero.js`, `image-slider.js`). Add a new block script to
   that file's imports only if the block actually needs live JS behaviour
   inside the editor preview itself.
9. **A viewport breakpoint (`lg:`) is the wrong tool for a block meant to be
   nested inside columns/containers** (`blocks/content-blok`, and any future
   block like it) — the viewport can be wide while the block's own rendered
   width is narrow (a 3-4 column layout on a desktop screen). Use Tailwind
   v4's own container queries instead: add `@container` to the element whose
   width should drive the decision, then `@lg:`/`@md:` etc. on its
   descendants. `@lg` here is 32rem (its own container-query scale), not the
   64rem of the viewport `lg:` breakpoint — a different scale, not a typo.
   The `@container` class must sit on an actual ancestor of the elements
   using `@lg:` etc., or the query has nothing to measure against and never
   matches.
10. **A "constrained" (non-`align:full`) block gets zero side padding on any
    viewport narrower than `settings.layout.contentSize` (1100px) unless
    `settings.useRootPaddingAwareAlignments` is on** — `theme.json` sets this
    plus a matching `styles.spacing.padding` (left/right only, using the
    same `spacing|s` preset as `blockGap`) precisely so a plain, non-full
    block gets a side gutter on mobile. `blocks/hero`, `/cta` and
    `/afbeeldingen-slider` all removed `"full"` from their own
    `supports.align` (only `""`/`"wide"` remain, picked via the block's own
    toolbar) — none of them are ever `align:full` anymore, so this rule now
    covers them the same way it always covered `blocks/content-blok`/a bare
    `core/paragraph`. A block that genuinely needs an edge-to-edge
    background now does that itself (see `blocks/hero/render.php`'s
    `$bleed_class` docblock) rather than through core's `align:full`.
11. **WordPress' native block-toolbar "Uitlijning" (wide/full) control never
    appears on a bare, top-level block inside a template part** (`blocks/
    header`, `/header-topbar`, `/footer`, `/footer-bottom` — all 4 sit
    directly in `parts/header.html`/`parts/footer.html`, no wrapping
    `core/group`), **even with `supports.align` declared** — a block-based
    theme's default ("flow") layout deliberately skips offering `wide`/`full`
    at all (`packages/block-editor/src/layouts/flow.js`'s `getAlignments`
    only adds them for *non*-block-based/hybrid themes); only a
    `layout:{"type":"constrained"}` ancestor's `getAlignments` (`layouts/
    constrained.js`) checks `contentSize`/`wideSize` and offers them. Wrapping
    these 4 blocks in such a group to get the control back would also make
    WordPress' own `useRootPaddingAwareAlignments` CSS (rule 10 above)
    constrain THEIR OWN wrapper — exactly the always-edge-to-edge background
    these blocks depend on. Their fix: a plain `align` attribute plus a
    custom `SelectControl` in each one's own `editor.js` (same pattern as
    `header`'s existing `menuAlignment`) — `render.php` reads the attribute
    identically either way, only the editor UI differs. `supports.align` is
    `false` on all 4 currently; if it's ever declared truthy again (by habit,
    copy-pasting another block's `block.json`) that's harmless, not a
    regression — the native control still can't appear per the reasoning
    above, it just becomes an inert declaration. A block genuinely nested
    inside `core/post-content` (`hero`, `cta`, `afbeeldingen-slider`) never
    hits this: `core/post-content` itself is `layout:{"type":"constrained"}`,
    so the native toolbar control works there without any of this workaround.
12. **Baking `padding-inline` into the SAME box as `max-width` +
    `margin-inline:auto` makes content sit further in than a native block at
    the same width, once the viewport is wide enough that centering alone
    already clears the padding** — `.container` (`src/css/app.css`) does
    this and it's correct there ONLY because it always wraps content inside
    an ALREADY genuinely edge-to-edge ancestor (no padding anywhere else to
    interact with — `hero`/`cta`'s legacy `align:"full"` case). A native WP
    block's gutter instead comes from a PADDED ANCESTOR
    (`core/post-content`'s own `has-global-padding`), applied OUTSIDE the
    `max-width`-centered box, not baked into it — on a wide viewport the
    centering alone already clears that padding, so it contributes nothing
    extra; `.container`'s baked-in padding has no such ancestor to disappear
    into, so it always adds on top. This is exactly the bug `header`/`footer`
    hit after their `align` feature (Traps #11) first shipped using
    `.container`/a `.container-content` copy of it: their content sat one
    full gutter further in than `hero`/`cta`'s at the same width, invisible
    in code review, only visible side-by-side in the browser. Fix: split
    width from gutter (see "Alignment, width and full-bleed backgrounds"
    above) — `.gutter-x` on the outer, already edge-to-edge wrapper;
    `.content-width`/`.wide-width` (no padding) on the inner box.

## Comments

Short but clear. A comment earns its place by explaining what the code cannot.

**Keep**: why a workaround exists and what breaks without it; WordPress /
Gutenberg / SCF / browser quirks; non-obvious ordering ("must run before X",
"this hook not that one"); why an unguessable number was chosen (SVG crop
offsets, pattern `shift` values); the `$args` contract of a shared part.

**Cut**: restating what the code plainly does; historical narrative ("this used
to be X, which was fragile" — state the current rule, not the journey);
tutorial explanations of standard behaviour; `@param`/`@return` that only echo
the type hints.

## Verify before finishing

```sh
php -l <file>                       # every PHP file touched
pnpm build                          # CSS/JS still compile
```

For a **comments-only or rename pass**, prove you changed nothing else —
`php -w` strips comments and whitespace, so identical output means code is
untouched:

```sh
php -w <file> | tr -d '[:space:]' | shasum   # compare before vs after
```

Caveats: `php -w` preserves inline HTML, so CSS/JS comments inside `<style>` or
`<script>` blocks won't show up — syntax-check extracted scripts with
`node --check` instead. And in zsh never `read` into a variable named `path`; it
is tied to `$PATH` and will break every command in the loop.

## Language

Dutch for anything an editor sees: UI strings, ACF labels, block titles and
descriptions, `expandedEditorButtonText`. Code comments: match the file you are
editing, don't translate existing ones.
