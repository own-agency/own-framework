# own-framework

Full block theme (`templates/index.html` exists, so `wp_is_block_theme()` is
`true`). Tailwind CSS v4 + Vite, Alpine.js, Secure Custom Fields. UI strings
are Dutch. Full layout reference: `STRUCTURE.md`.

Custom blocks (`blocks/hero`, `blocks/cta`, `blocks/content-blok`,
`blocks/content-blokken-slider`, `blocks/feitjes-slider`,
`blocks/afbeeldingen-slider`, `blocks/google-maps`, `blocks/logo-slider`,
`blocks/proces`, `blocks/proces-stap`, `blocks/voordelen`,
`blocks/voordeel`, `blocks/melding`, `blocks/zoekbalk`, `blocks/vacature-hero`,
`blocks/vacatures`, `blocks/vacature-single-hero`, `blocks/vacature-apply`,
`blocks/vacatures-uitgelicht`, `blocks/faqs`, `blocks/zijbalk-navigatie`,
`blocks/header*`, `blocks/footer*`, `blocks/agenda-hero`, `blocks/agenda`,
`blocks/agenda-single-hero`, `blocks/agenda-aanmelden`,
`blocks/agenda-uitgelicht`, `blocks/verhaal-hero`, `blocks/verhalen`,
`blocks/verhaal-single-hero`, `blocks/verhaal-share`,
`blocks/verhalen-uitgelicht`) already exist and are the reference
implementations for the conventions below. `template-parts/components/
empty-state.php` is the one shared part so far (see "Empty states").
Header/Footer are designed as Site Editor
template parts that themselves render these custom blocks, not blank areas.
The five `vacature-*`/`vacatures*` blocks are the `vacancy` CPT's own feature
set — see "Vacancies" below for the full picture (taxonomies, templates,
options pages) rather than just the blocks in isolation. The five
`agenda*` blocks are the `agenda` CPT's own feature set the same way — see
"Agenda" below. The five `verhaal*`/`verhalen*` blocks are the `verhaal`
CPT's own feature set — see "Verhalen" below.

Being a full block theme is about page _structure_ only — `templates/*.html`
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
`template-parts/components/` only when a _second_ consumer actually appears,
not in anticipation of one.

## Never rename these — they are stored data

Once blocks/fields exist, renaming any of these orphans live content or
breaks URLs. Not style choices:

| Name                                          | Stored where                                   |
| --------------------------------------------- | ---------------------------------------------- |
| Block name (e.g. `acf/statistiek`)            | `post_content` as `<!-- wp:acf/statistiek -->` |
| Block folder                                  | must mirror the block name — keep them equal   |
| ACF field name (e.g. `statistiek_title`)      | `postmeta` keys                                |
| ACF group key (e.g. `group_block_statistiek`) | `acf-json` + DB                                |
| Post type / taxonomy slug                     | DB + permalinks                                |
| Capability name (e.g. `edit_vacancies`)       | each site's `wp_user_roles` option             |

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

Two custom block categories are already registered — `own-framework` (the
default, use it for anything new) and `own-framework-vacatures` (used only by
the five `vacancy`-related blocks, see "Vacancies" below) — pick whichever
actually describes the block in its `block.json`'s `"category"`. Custom
WordPress-bundled patterns are disabled (`inc/functions/block-patterns.php`),
so the inserter's Patterns tab only ever shows this theme's own.
User-created patterns (`wp_block`) made on the main site are copied to every
subsite on publish/update (`inc/functions/multisite-pattern-sync.php`) —
one-way, main site only; see that file's header for the known limits.

Currently available helpers: `ownframework_get_icon($name, $class)` (reads
`media/icons/<name>.svg`, see `inc/functions/icons.php`) and
`ownframework_resolve_block_image(array $image): array` (resolves a plain
`"image"` block attribute's url/alt via its attachment ID, with a fallback for
an image without one — see `inc/functions/image-helpers.php`, shared by
`blocks/cta` and `blocks/content-blok`, extracted once a second block needed
the exact same logic), plus `ownframework_full_bleed_class(): string` and
`ownframework_full_bleed_background(string $background): string`
(`inc/functions/full-bleed.php`) — the edge-to-edge background layer, see
"Alignment, width and full-bleed backgrounds" below;
`ownframework_get_contact_address(): array`
(`inc/functions/contact-address.php`) — the options-page address in one
normalised shape (`text`/`lines`/`lat`/`lng`/`has_coords`/`maps_url`), shared
by `blocks/footer-contact` and `blocks/google-maps`, so neither needs to know
that the underlying field is a `google_map` array; and
`ownframework_get_google_maps_api_key(): string`
(`inc/functions/google-maps-api-key.php`), see "Credentials" below. There is no
`bg_class`/`pattern_color_class`/
`split_content_blocks`/`research_query` helper anymore; those existed in a
previous iteration of this theme and were removed along with the content
that used them. Add equivalents to `inc/functions/` yourself if a new block
needs that kind of shared logic — don't assume they still exist beyond the
two named above.

**Restricting a child block to one parent** — when a block only makes sense
nested inside one other block (not tied to a specific TEMPLATE, which is what
the `blocks/*/hooks/restrict-to-*.php` filter pattern used by the `vacancy`/
`agenda`/`verhaal` CPT blocks is for) — use block.json's native
`"ancestor": ["acf/parent-block-name"]` instead of writing a custom
`allowed_block_types_all` filter. `blocks/proces-stap` and `blocks/voordeel`
both do this: one line, declarative, no `hooks/` file needed at all.

**WordPress 7.1 shipped a native `core/icon` block** with its own icon picker,
grouped by collection (a theme/plugin can register its own via
`wp_register_icon_collection()`/`wp_register_icon()`, though this theme
doesn't register any yet — `blocks/voordelen`/`blocks/voordeel` deliberately
ship with WordPress' own bundled icons only, see that pair's own docblocks).
It already has native background/text/border/padding/width supports, so a
block needing "pick an icon, show it in a coloured badge" (`blocks/voordeel`
is the reference) should nest a real `core/icon` in its `InnerBlocks`
template rather than building a custom `MediaUpload`/icon-name `SelectControl`
picker — same "don't reinvent an already-polished native UI" reasoning as
using ACF's own Relationship field for the `*-uitgelicht` blocks' hand-picked
selections. One real limitation: the SVG sanitizer behind it only allows
`fill` on shape elements, not `stroke` — most of this theme's own
`media/icons/*.svg` are stroke-based outline icons, so they aren't a
drop-in for registering as a collection without converting them to filled
shapes first.

## Alignment, width and full-bleed backgrounds

Every custom block's own "align" now has **three** tiers instead of two —
**`""` (Smal, 1100px), `"wide"` (Normaal, 1280px — the default), or `"full"`
(Breed, 1440px)**. This reuses WordPress' own native three-state align
system (none/wide/full), just with new MEANING: `"full"` is deliberately
**not** edge-to-edge any more — it's a third, still-constrained width tier,
the same way `"wide"` already was. There is no native way to add a genuine
fourth ("narrow") tier or a third `theme.json` size slot, so this reuses the
three states core already offers rather than building an entirely custom
control.

**Which UI produces the align choice depends on where the block lives, same
as before** — only the number of options changed, not the mechanism:

- **Blocks inside page content** nested in `core/post-content` (`hero`,
  `cta`, `afbeeldingen-slider`, `content-blokken-slider`, `feitjes-slider`,
  `google-maps`, `logo-slider`, `proces`) use the NATIVE `supports.align:
["wide", "full"]` toolbar control. This only works because `core/
post-content` itself is `layout:{"type":"constrained"}` — see Traps #11
  below for why that ancestor is required and not automatic.
- **`hero` and `cta` needed their own OLD legacy code removed first** —
  both used to have `$use_container = $align === 'full'` from a PREVIOUS
  round of removing "full" (a much earlier one, unrelated to this change),
  which special-cased any already-saved `align:"full"` content into an
  edge-to-edge `.container`-based layout. Widening `supports.align` back to
  include `"full"` without also deleting that code would have made the OLD
  edge-to-edge path reachable again via the toolbar instead of the NEW
  1440px tier — a real conflict, not a style choice. Both blocks' render.php
  now treat `"full"` exactly like `"wide"`/`""` — just a different native
  align class, no special-casing left. This also changes how any
  PRE-EXISTING content with `align:"full"` saved on these two blocks
  renders (from the old edge-to-edge look to the new 1440px-constrained
  one) — intentional, for consistency with every other block, not an
  oversight.
- **Template-part blocks** (`header`, `header-topbar`, `footer`,
  `footer-bottom`) can never get the native control at all (Traps #11) —
  each has a plain `align` attribute instead, plus its own custom
  `SelectControl` in `editor.js` (e.g. `header`'s "Breedte inhoud", same
  three labels — "Smal"/"Normaal"/"Breed" — as everywhere else), now with a
  third option mapped to `"full"`.

Either way, `render.php` just reads `$block['align']`, same attribute name,
same three values, only the editor UI mechanism differs. Every block's
attribute now DEFAULTS to `"wide"` (was `""`) — both explicitly in each
block's own `attributes.align.default`, and centrally for every OTHER block
on the site (core included) via `inc/functions/default-block-align.php`'s
`register_block_type_args` filter. That filter has to unconditionally
overwrite `default`, not just inject it when the attribute is missing — this
theme's own blocks already declare `align` explicitly, so "only inject if
absent" would silently skip exactly the blocks meant to change. The toolbar's
own labels ("None"/"Wide width"/"Full width") and the Styles→Layout panel's
matching field labels are relabelled to "Smal"/"Normaal"/"Breed" via
`inc/functions/enqueue-scripts-styles.php`'s
`ownframework_relabel_align_controls()` — a `wp_add_inline_script('wp-hooks',
$js, 'after')` call containing `wp.hooks.addFilter('i18n.gettext'/
'i18n.gettext_with_context', ...)`. Getting there took four attempts (PHP
`gettext` filter, then `wp.i18n.setLocaleData()`, then a standalone
`wp.hooks.addFilter()` script, all verified NOT to work reliably — see Traps
#25/#26 for exactly why each one failed) — verified against the exact
strings/context in compiled `wp-includes/js/dist/block-editor.js` before
targeting them, so this doesn't accidentally mislabel unrelated wp-admin
text. The toolbar buttons' ICONS can't be changed this way (or at all, short
of replacing the whole
native control) — they're hardcoded `@wordpress/icons` component references
baked into Gutenberg's compiled JS with no filter anywhere near them. The
"full" option also can't get a genuine SECOND-LINE "Max 1440px wide"-style
subtitle the way the other two show — Gutenberg's own subtitle logic
(`getAlignmentsInfo()`) only ever computes one for "none"/"wide" (reading
`layout.contentSize`/`wideSize`), and structurally never for "full" at all,
regardless of what's in `theme.json` — there's no hook that makes it build a
second line for an option it was never written to describe. Worked around,
not solved: the max-width is folded directly into the MAIN label text instead
("Breed (max. 1440px)", `ownframework_relabel_align_controls()`) rather than
attempting a second line — a small, honest compromise using the SAME already-
working mechanism, instead of DOM-patching the rendered menu after the fact
to force a visual match (rejected as too fragile, same reasoning as the icons
above).

**Width and gutter are two separate CSS concerns, on purpose** (`src/css/
app.css`):

- `.content-width` / `.wide-width` — `max-width` + `margin-inline:auto`
  only, no padding, reading a theme.json-driven CSS custom property
  (`--wp--style--global--content-size`/`--wide-size`, WP's own native
  `settings.layout` keys) rather than a hardcoded px value, so both stay
  correct without any CSS change if the values are ever tuned in
  `theme.json` again.
- `.full-width` — same shape, but reads `--own-full-width`, a THEME-OWN CSS
  custom property (`inc/functions/full-width-scale.php`, Thema opties →
  Stijlen → Breedte — see "Style settings" below), not a theme.json-driven
  one: unlike the other two, there's no native `settings.layout` slot for a
  third named size, AND no Ontwerp/Styles UI field ever reads a third value
  either way (Styles → Layout only ever exposes Content width/Wide width,
  confirmed against Gutenberg's own compiled JS) — so this is `radius-scale.
php`'s exact pattern (a plain `:root` CSS variable override, no theme.json
  involved) applied to width for the identical reason: nothing native to feed
  either way, so a custom options field is the legitimate way to make it
  admin-tunable, same justification as radius/spacing's own exception to the
  "no custom options page for style values" rule. Kept in ONE place (this
  utility) and read directly by the native `.alignfull` repurposing below, so
  there's
  still only one number to change if it's ever adjusted.
- `.gutter-x` — `padding-inline: var(--wp--preset--spacing--s)` only. Picks
  the side GUTTER, applied to whichever element is genuinely edge-to-edge
  (a template-part's own outer wrapper) — never to the width-constrained box
  itself.
- `.container` (older, still `max-width` + padding-inline combined in one
  box) exists ONLY for legacy, already-saved content from a MUCH EARLIER
  removal of "full" than the one described above (that content predates
  BOTH changes, and — unlike `hero`/`cta`'s own OLD `$use_container` code,
  which this rework deleted — nothing left in this theme still reads
  `align === "full"` to reach for it). Don't reach for it for anything new
  — see Traps #12 for exactly why that combination breaks for a block that
  isn't genuinely edge-to-edge already.

**Repurposing native "full" alignment took a real CSS fix, documented as
Traps #23 below** — the short version: WordPress' own generated CSS for
`.alignfull` (`max-width:none`, plus a separate negative-margin rule that
escapes the root's own padding) is what makes it reach the true viewport
edge; both need overriding together (`src/css/app.css`), and the override
must explicitly exclude `.wp-block-post-content` — `templates/single.html`/
`page.html` deliberately give `core/post-content` ITSELF `align:"full"`, not
as a width choice but structurally, to escape `<main>`'s padding and reach
the real viewport edge (the precondition the full-bleed-background trick
below, and this theme's gutter-parity math generally, depend on). Without
that exclusion, repurposing "full" would also silently cap post-content
itself at 1440px on every single post/page.

**A block whose BACKGROUND still needs to be genuinely edge-to-edge despite a
constrained width** — impossible via core's align system now that "full"
means a constrained 1440px tier rather than edge-to-edge (see above) — uses
its own CSS trick instead, independent of whichever of the three align tiers
the block happens to be set to. That trick lives in ONE place:
`ownframework_full_bleed_class()` (`inc/functions/full-bleed.php`). Never
re-type the class string; six blocks share it.

- `ownframework_full_bleed_background($bg)` returns a ready-made coloured
  layer (empty string when no colour is set, so the call can sit
  unconditionally in the markup). `afbeeldingen-slider`,
  `content-blokken-slider`, `feitjes-slider` and `logo-slider` use this:
  setting a background colour on any of them paints the full screen width
  automatically, no toggle. `logo-slider` didn't originally — an earlier
  version let the colour come purely from the native "Kleuren" panel,
  stopping at the section's own align-width, on the reasoning that the
  section already stays at that width by design (see its own "Volledige
  schermbreedte"/Traps #22, which is about the MARQUEE BAND's own separate
  bleed, not the background) — inconsistent with every other coloured
  section on the site actually reaching the screen edge, fixed by adding the
  same layer here too. Those blocks must therefore NOT also put
  `background-color` in their own wrapper `style` — WP's colour support
  still adds it there itself, which is harmless only because the wider layer
  covers it.
- `hero` and `cta` use the bare class instead, because their layer also holds
  an image/slider/video plus an overlay. Unconditional for `hero` (its whole
  point); for `cta`'s own CARD background (`cardBackgroundColor`, a separate
  attribute from the section's native colour — see `blocks/cta/render.php`'s
  own docblock) it is the "Achtergrond over volle breedte" toggle
  (`$use_bleed`), since that one controls whether the card itself looks
  edge-to-edge, a deliberate opt-in. `cta`'s SECTION-level native background
  (the plain "Kleuren" panel, `supports.color.background`) is a THIRD,
  separate usage on the same block, and that one is unconditional like
  `hero`'s — it used to not bleed at all (stopped at the section's own
  align-width, same bug `logo-slider` had below), fixed the same way: a
  `$section_bleed_style_parts` layer rendered as the section's first child,
  regardless of `$use_bleed`, since a section's own background colour reaching
  the screen edge was never supposed to depend on the card's separate
  full-width toggle.
  Content always stays inside the block's own `align`-picked width regardless —
  only the background/media layer bleeds to 100vw. The block needs
  `position: relative` and its content in a `relative z-10` layer, or the layer
  centres against the wrong ancestor / covers the content. `header`/`header-topbar`/`footer`/`footer-bottom` never need this
  trick at all: sitting outside `core/post-content`'s constrained layout, their
  outer wrapper is ALREADY naturally edge-to-edge, so a plain background-colour
  there reaches the screen edges with zero extra code.
- **`core/columns` and `core/group`** (native WordPress blocks, not this
  theme's own) get the same "content stays contained, background bleeds"
  treatment too, but via a completely different mechanism:
  `src/css/components/full-bleed-core-blocks.css`, pure CSS, no PHP. A custom
  block controls its own `render.php`, so it can inject a real full-bleed
  `<div>`; a core block's EDITOR PREVIEW never runs PHP at all (Gutenberg
  renders `core/columns`/`core/group` client-side from their own `save()`
  output), so a `render_block` filter would only ever reach the front end,
  leaving the editor canvas showing the old constrained-only look while
  editing. The CSS file's own docblock has the full mechanism (a `::before`
  with `background: inherit`, `isolation: isolate` instead of a `z-index: 0`
  hack) and its one known limitation (assumes top-level usage, same as every
  other full-bleed block here) — read that before touching it rather than
  re-deriving the reasoning.

## Brand identity: native Global Styles, not a custom options page

There is **no ACF "brand settings" page** in this theme, and there shouldn't
be one. A client's colours, fonts, shadows and background settings are set
entirely through the Site Editor's own "Ontwerp" (Styles) panel — Global
Styles, WordPress' own per-site, database-backed brand-settings system. This
is deliberate: a previous iteration of this theme had a custom options page
that generated a standalone CSS file, completely disconnected from the block
editor's own colour/font pickers — editors saw default WP colours everywhere
except inside custom blocks. Don't reintroduce that split. If a client needs
a _new_ colour, font, shadow or spacing size, add its **preset** to
`theme.json`'s `settings.*` (so it's a first-class option everywhere) — don't
build a field for it.

`theme.json` seeds a starter palette (`base`/`contrast`/`primary`/`secondary`),
a heading/body font pair (already bundled — see `fonts/` and
`src/css/components/typography.css`'s `@font-face` rules), a fluid font-size
scale, and shadow presets, all editable via Ontwerp with zero code changes.
Heading sizes/weights/line-heights are `styles.elements.h1`–`h6` (margins stay
in `typography.css`). `settings.typography.fluid` is on: a heading size typed
in Ontwerp is the desktop maximum (at 1280px) and WP generates the mobile
clamp() itself; `minFontSize` 18px keeps body text fixed.
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

**Font sizes follow Ontwerp too.** `app.css`'s `@theme` points `--text-xs`/
`--text-sm`/`--text-base` at the `xs`/`small`/`medium` fontSizes presets
(identical values, 0.75/0.875/1rem), so every `text-xs/sm/base` utility
follows Ontwerp → Typografie → Lettergroottes; body text is
`styles.typography.fontSize` = `medium`. `text-lg` and up have no matching
preset, so don't use them for anything that should follow Ontwerp: a
heading-like element uses `--own-h1…h6-font-size`/`-line-height`
(`inc/functions/heading-size-vars.php`, the Ontwerp heading sizes as
variables) with `!` (Traps #34). **Every CPT and blog card title uses the H3
size** (`blocks/{news,vacatures,agenda,verhalen}/parts/card.php`, also used
by the `*-uitgelicht` blocks), whatever heading level it renders as. Still
fixed on purpose: the agenda date badge's numerals, `feitjes-slider`'s big
numbers and label, and the small count badges — display type sized to its
own box.

**Buttons: one hover for all of them** (`src/css/components/buttons.css`,
which also holds core/button's style variants). Hover/focus lighten the
button with a white tint over its background (`--own-button-tint` in a
`background-image`) and lift it 2px with a soft shadow; outline variants fill
in their own colour instead, Tekstlink only underlines. Never a fixed hover
colour: it works on every palette, every block style and every colour an
editor picks per button (that colour is a `!important` `background-color`
class, a different property). The lift is what shows on an already-light
button, where white barely does. `box-shadow` is composed from
`--own-button-ring` (focus) and `--own-button-lift` (hover), so set those
variables rather than `box-shadow` itself. Don't add a
hover colour to theme.json's `elements.button` — per-button colours would beat
it. A button that should react has `.wp-element-button`; that covers core/
button, the theme's own rendered buttons and Forminator's.

**Tables** (`src/css/components/table.css`): core/table restyled to a clean
table — rounded outer border, horizontal dividers, tinted header/footer — with
every line and tint derived from the table's own text colour, so the block's
Tekst/Achtergrond colours and dark sections need nothing extra. The block's own
settings map onto that design: Strepen, Kopsectie/Voetsectie, per-column
alignment, Vaste kolombreedte (hyphenated, not split mid-word), Bijschrift, and
Rand: colour → all lines, width → outer border only, style → outer + dividers.
Core puts colour/border on the `<table>`, not the `<figure>`; the doubled
`.wp-block-table.wp-block-table` is there to match core's own border selectors.
"Afgeronde hoeken" (Stijlen tab → Hoeken, `inc/core-blocks/table-corners.js`)
is a per-table switch: on = the Stijlen radius scale, off = square. Stored
inverted (`ownTableSquare`, marker class `has-own-square-corners`) so that only
switching it OFF changes saved markup — a default-on class would invalidate
every existing table.

**Colours are not unified the same way, and can't be as simply**: Tailwind's
own colour scale in `app.css`'s `@theme` block (`paars-500` etc. — legacy
naming, not the current palette) is a _separate_, build-time system, compiled
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
(`inc/scf/color-selector.php`) — for a _block's own_ colour field, e.g. "Achtergrondkleur" —
and the TinyMCE colour sync (`inc/scf/custom-color-palette.php`) both read the
palette via `wp_get_global_settings(['color', 'palette'])`, the same resolved
source Gutenberg's own pickers use. A colour a client adds via Ontwerp shows
up in both automatically.

There is no secondary logo mechanism — only the native Site Identity /
`add_theme_support('custom-logo')` single logo slot (in `functions.php`). If a
project genuinely needs a second logo variant (e.g. for a dark header), that's
a deliberate future addition, not something to assume exists.

## Style settings: radius, spacing & width (the one deliberate exception)

`acf-json/group_own_style_settings.json` adds a "Stijlen" SCF options page
(Thema opties → Stijlen, sibling of "Algemeen") with three dials: **Radius**
(none/small/medium/large, `get_field('radius', 'option')['scale']`),
**Spacing** (compact/standard/spacious,
`get_field('spacing', 'option')['scale']`), and **Breedte** (a single number
in px, `get_field('width', 'option')['full']`, default 1440). This looks like
the custom options page the "Brand identity" section above argues against —
it isn't, for a reason specific to each field:

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
- **Radius** (`inc/functions/radius-scale.php`) and **Breedte**
  (`inc/functions/full-width-scale.php`, `ownframework_get_full_width_max()`)
  both have no theme.json preset scale to hook into — Gutenberg's own
  border-radius control is a free slider, not a choice list, and there is no
  native `settings.layout` slot for a THIRD named width size beyond
  `contentSize`/`wideSize` at all (see "Alignment, width and full-bleed
  backgrounds" above). Both therefore only override a plain CSS custom
  property on `:root` (`--radius-*`/`--own-full-width`) via
  `wp_add_inline_style()` on both `theme-styles` and `theme-styles-editor` at
  priority 20 — no disconnect to protect against here, since there's no
  native picker either of them could feed even if the value were stored in
  theme.json instead. `--own-full-width` is also read directly by
  `ownframework_relabel_align_controls()` (`enqueue-scripts-styles.php`) to
  keep the "Breed" toolbar label's own "(max. Xpx)" text in sync with
  whatever this field is currently set to — one shared getter
  (`ownframework_get_full_width_max()`), not two places independently calling
  `get_field()`. Tailwind v4's `rounded-*` utilities already read
  `var(--radius-sm/md/lg/xl/2xl/3xl)` rather than baked-in values (confirmed
  in compiled CSS), so radius's override reaches every `rounded-*` class in
  the theme, present and future, automatically — no block needs touching.
  `rounded-full` (pills/avatars) is a fixed pixel value in Tailwind itself,
  not var-driven, so it always stays a pill regardless of this setting.

Current radius→Tailwind-step convention (`radius-scale.php`'s "medium"/default
row): a card-style block's outer boxed element uses `rounded-2xl`, its inner
media uses `rounded-xl` — one step down. `blocks/cta` and `blocks/hero` both
follow this; match it in new blocks rather than introducing a third radius
step.

Don't add a fourth custom-options-page field for anything else color/font/
shadow-shaped — those go through Ontwerp exactly as the "Brand identity"
section says. This exception exists only because radius, spacing and width
are site-wide _scales_, not colours, and one of the three genuinely feeds the
native picker.

## Credentials: Koppelingen on the developer page

API keys live on the developer page (Netwerkbeheer → Ontwikkelaar →
Koppelingen, `developer/`), not on a Thema opties page: a wrong key breaks an
integration site-wide, so clients don't manage it. The former SCF page
(`acf-json/ui_options_page_own_integrations.json` + `group_own_integrations_
settings.json`) is kept with `"active": false` — deleting the JSON would let
the DB-synced copy on every other environment take over again. Remove both
files (and their DB posts) once every environment has synced.

Currently one key, the Google Maps API key
(`inc/functions/google-maps-api-key.php`), resolved per site in this order:
the site's own key (blog option `ownframework_google_maps_api_key`) → the
network key (site option of the same name) → `OWN_GOOGLE_MAPS_API_KEY` in
wp-config.php. Until a site is saved on the developer page, the old SCF
value (`options_google_maps_api_key`) still counts as its own key; saving
migrates it and deletes the old rows. Hook `acf/settings/<name>` rather than
`acf_update_setting()` on `acf/init` when feeding SCF a key — the filter
resolves lazily, when the field enqueues its scripts.

`blocks/google-maps` consumes the same key, via the Maps **JavaScript** API
(`src/js/scripts/google-map.js`). An earlier version used the **Embed** API — a
plain `<iframe>`, no JS at all — which is the better default until a
requirement needs something the embed's fixed `place`-mode URL simply can't
express. Two such requirements arrived, one at a time, and each moved the
block further from the embed: a styled marker (reverted, see below), then
hiding the Street View pegman and the satellite/map-type toggle. Checked
directly against Google's own docs for `place` mode: it has exactly six URL
parameters (`q`, `center`, `zoom`, `maptype`, `language`, `region`), none of
them UI-control switches — `streetViewControl`/`mapTypeControl` exist only on
the JS API's `Map` object. There was no way to keep the embed once that was
asked for.

The marker itself is currently Google's own default pin — `google.maps.Marker`
is deprecated in favour of `AdvancedMarkerElement`, which needs a cloud-side
map ID (map appearance would then live in each client's Cloud Console instead
of this codebase), so the plain, working, long-notice-period `Marker` stays
until custom marker styling is actually requested again. If it is, add the
custom icon back to `initMap()` in `google-map.js` rather than reaching for
`AdvancedMarkerElement` for the reason above. "Zwart-wit" is a plain CSS
`grayscale` filter on the block's own wrapper, not a Maps style array — with
no custom marker to protect from being greyed out too, the filter is simpler
and the visual difference is moot. The key needs "Maps JavaScript API" (not
"Maps Embed API" — no longer used) enabled alongside Geocoding/Places.

The block's `render.php` never puts the real map in the editor, even with a
valid key and address — Gutenberg re-renders a block's preview on every
attribute change, so a live map there would turn a few drags of the zoom
slider into that many billed Maps requests. The editor always gets a plain
placeholder (address text + a note that the map only loads on the site itself)
sized to match the real map's height, so the layout doesn't jump once it's
viewed on the front end. Same spirit as the editor-vs-front-end split in
`blocks/content-blokken-slider`/`feitjes-slider` (Traps #13), for a different
reason: there it's about editability, here it's purely about not paying for
requests a page edit doesn't need.

**SCF's own `google_map` field is hardwired to Google's LEGACY APIs**, verified
in `assets/build/js/acf-input.js` (SCF 6.9.4): it calls
`google.maps.places.Autocomplete` and `google.maps.Marker`, loads the script
with `libraries=places`, and contains no `PlaceAutocompleteElement`,
`importLibrary` or `places.Place` anywhere — i.e. zero "Places API (New)"
support. Nothing on the theme side can change that: the
`acf/fields/google_map/api` filter only rewrites the script URL's parameters
(key, libraries, language), not which classes the plugin's compiled JS then
calls. So the key needs the classic "Places API" enabled, not just "Places API
(New)". Enabling only the new one degrades partially rather than fully — the
map and the draggable marker keep working (Maps JavaScript API + Geocoding
API), only the address search box goes dead. Fixing it properly means an
upstream SCF change; don't try to shim it here. `blocks/google-maps` is
unaffected either way, since the Embed API isn't part of the Places
deprecation.

## Developer settings: `developer/` (not a Thema opties page)

Settings only the development team may change live in their own module,
`developer/` (`load.php` + `includes/` + `assets/admin.css`), not in an SCF
options page: Thema opties pages are opened per role via User Role Editor, so
a client admin could grant themselves access. Multisite: Netwerkbeheer →
Ontwikkelaar (one overview of every site). Single site: Gereedschap →
Ontwikkelaar. Access is `ownframework_is_developer()`: a super admin, narrowed
to the logins/e-mails in `OWNFRAMEWORK_DEVELOPERS` (wp-config.php) when that
constant is defined.

- **Koppelingen** — see "Credentials" above.
- **Site-wisselaar: toon ook niet-publieke sites** — network-wide site option,
  read via `ownframework_switcher_include_non_public_sites()` in
  `blocks/footer-topbar/render.php`. Used to be a field on Thema opties →
  Algemeen.
- **Contenttypes per site** — blog option `ownframework_disabled_post_types`
  (the DISABLED slugs, so a new CPT defaults to on). A disabled CPT stays
  registered but gets every public/UI/REST flag switched off via
  `acf/post_type/registration_args`, all its capabilities map to
  `do_not_allow` (beats super admin too), taxonomies that only belong to
  disabled types are hidden, its blocks leave the inserter and render `''`,
  its options page and `archive-`/`single-` templates disappear. A PAGE with
  the `archive-{type}` template (the archive's "vitrine", see
  `inc/functions/archive-template-post-states.php`) takes over the archive's
  URL once the real archive is gone, so it is forced to 404 too, and it and
  every menu item pointing at disabled content (plus its submenu items) are
  dropped from front-end menus, search and the sitemap. Content stays
  in the database; re-enabling restores everything. Saving deletes the site's
  `rewrite_rules` option instead of flushing, because inside
  `switch_to_blog()` the current request's post types are still registered.
  **A new CPT (or a new block belonging to one) must be added to
  `OWNFRAMEWORK_DEV_POST_TYPES`** (`developer/includes/settings.php`) to be
  switchable — with its blocks and options page slugs.

## Vacancies (the `vacancy` CPT: taxonomies, templates, five blocks)

`vacancy` is a full custom post type with seven taxonomies: `dienstverband`
(the original one, enabled by default) plus six added later — `salaris`,
`opleidingsniveau`, `fte`, `locatie`, `voorwaarde`, `school` (all disabled by
default). Whether a taxonomy is active at all, and which SVG icon represents
it, are both site-wide settings, not something to hardcode:

- **Thema opties → Vacatures** (`acf-json/ui_options_page_own_vacature_
taxonomies.json`, menu slug `vacature-taxonomieen` — merged from two
  originally separate options pages once it became clear both were "vacancy
  settings" to an editor) holds one "group" field per taxonomy
  (`acf-json/group_own_vacature_taxonomies.json`, an `enabled` toggle + a
  raw-SVG `icon` textarea) plus the site's application settings
  (`acf-json/group_own_vacature_application.json`): "Sollicitatiemethode"
  (`form`/`url`) and a Forminator form ID — one site-wide choice, not
  per-vacancy. A per-post `application_url` override still exists for the
  `url` method, read explicitly by `blocks/vacature-apply/render.php`.
- `inc/functions/vacature-taxonomy-settings.php` is where both settings
  actually take effect: `ownframework_vacature_taxonomy_enabled($slug)` reads
  the RAW option (`get_option("options_{$slug}_enabled")`), never
  `get_field()` — see Traps #27 for why `get_field()` isn't reliable at the
  hook this runs on. `ownframework_vacature_taxonomy_icon($slug)` is the
  safe-anywhere `get_field()` call for the icon, meant for render-time code
  (`blocks/vacatures/parts/card.php`, the filter sidebar). An `acf/taxonomy/
registration_args` filter toggles `show_ui`/`show_in_rest`/etc. per
  taxonomy from the `enabled` setting; a separate `acf/update_value` filter
  sanitizes the icon field's raw SVG with `wp_kses` on save, not on output.
- The archive's filter sidebar only ever lists a taxonomy that is BOTH
  enabled AND has at least one term actually assigned to a published
  vacancy — a disabled or genuinely unused taxonomy never appears, no matter
  how many terms exist in the database.

**Two templates**, both registered with a proper title/description via
`inc/functions/register-templates.php` (`register_block_template()` — there
is no template-_category_ grouping feature in the Site Editor's Templates
list; confirmed against `class-wp-rest-templates-controller.php` — the
category filter there only ever applies to _blocks_, not templates):

- `templates/archive-vacancy.html` — `acf/vacature-hero` (search) +
  `acf/vacatures` (filterable results grid), both with the theme's XL
  spacing preset top/bottom.
- `templates/single-vacancy.html` — `acf/vacature-single-hero`, then a
  `core/columns` split (66.66% / 33.33%) with `core/post-content` on the left
  and `acf/vacature-apply` on the right, same XL margin. This 2/3+1/3 layout
  lives directly IN the template file, not in a per-post default block
  template assigned via `register_post_type_args`' `template` argument —
  vacancies are imported via WP All Import, which writes `post_content`
  directly and never runs the block editor's own "new post" prefill, so
  anything that must apply to EVERY vacancy (imported or hand-created) has
  to live in the shared template, never in a per-post default.

**Once either template is edited via the Site Editor, WordPress persists a
full DB copy (`wp_template` post) that overrides the theme file from then
on** — see Traps #28. Expect further layout tweaks to happen there directly
rather than assuming a future edit to the `.html` file will reach the live
site on its own.

**Five blocks**, all under the `own-framework-vacatures` block category
rather than the generic `own-framework` one:

- **`acf/vacature-hero`** — archive hero with a live search field. The
  search button (`.own-vacature-hero-search-btn`) gets its own colour
  (background/text) and a per-corner pixel radius (linked or independent —
  four `RangeControl`s plus a "corners linked" toggle) via editor.js's
  "Zoekknop" panel — deliberately NOT the native Knop-block styling; an
  earlier iteration matched that styling and was reverted back to a plain
  button plus its own colour/radius controls.
- **`acf/vacatures`** — the filterable results grid. Card title/text/
  background colour and the "Vacature bekijken" link colour are separate
  attributes from the badge accent colour (`cardTitleColor`/`cardTextColor`/
  `cardBackgroundColor`/`cardLinkColor` vs. `accentColor`), all consumed by
  the shared `parts/card.php` as CSS custom properties on the block's own
  wrapper — same reasoning as Traps #7 (native `supports.color` only ever
  paints the wrapper, never a nested sub-element). `hooks/query.php` also
  fixes two things a plain block attribute can't reach on its own:
  - the archive's MAIN query (the first, no-JS page load) otherwise ignores
    the block's own "Aantal" attribute entirely — see Traps #29;
  - pagination's chevron buttons are sized to match the numbered buttons
    exactly (`h-9 w-9` vs. `h-9`), not left to `p-2`'s incidental sizing.
    Cards sit in a shared `.own-vacatures-surface` class (subtle border +
    light shadow, deliberately less prominent than the raw default Tailwind
    shadow it replaced) and share their equal-height behaviour with
    `acf/vacatures-uitgelicht` below — see Traps #30.
- **`acf/vacature-single-hero`** — the single-vacancy hero: left-aligned,
  breadcrumb on by default, title → description → taxonomy badges (in that
  order; badges sit under the date with a divider), each with its own
  colour attribute (`breadcrumbColor`/`titleColor`/`descriptionColor`/
  `badgeAccentColor`) in editor.js's "Kleuren" panel, plus a native
  `backgroundColor` support for the section itself. Uses
  `ownframework_full_bleed_class()`/`_background()` for its edge-to-edge
  background, same helper `hero` uses.
- **`acf/vacature-apply`** — the single-vacancy CTA card (right column).
  Reads the site-wide Sollicitatiemethode + Forminator form ID plus the
  per-post `application_url` override; renders a real `<InnerBlocks>` intro
  (heading/paragraph/knoppen, so the card's own copy is genuinely editable
  per vacancy), but the actual apply button is written directly in
  `render.php`, not via InnerBlocks — its behaviour (external link vs. a
  modal that triggers Forminator) is both per-vacancy AND shared across
  every vacancy via the same template, which a real Knop block instance
  can't express. It uses the NATIVE `wp-element-button wp-block-button__link`
  classes though (no custom colour/radius attributes of its own), so it
  visually matches the site's actual Knop styling and stays in sync if that
  ever changes via Ontwerp — chosen deliberately over keeping a bespoke
  colour panel for a button whose dynamic behaviour couldn't be a real Knop
  block anyway. Renders the company logo (explicit post ID — Traps #27)
  with a divider below it, shown only when a logo actually exists.
- **`acf/vacatures-uitgelicht`** ("Vacatures uitgelicht") — the one
  page-usable block, not tied to either template above: an `<InnerBlocks>`
  intro (heading/paragraph/knoppen) plus a grid or Swiper slider of
  vacancies, either the most recent N or a hand-picked selection. The
  hand-picked selection is a real ACF field attached DIRECTLY to the block
  (`acf-json/group_own_vacatures_uitgelicht_selectie.json`, `relationship`
  type, `location: "block == acf/vacatures-uitgelicht"`) — the first field
  in this theme attached to a block rather than expressed as a plain
  block.json attribute + editor.js control. Deliberate: "pick a handful of
  posts from a list" is exactly what ACF's own Relationship field UI already
  does, well and fully polished; a hand-rolled React picker would only
  reinvent it worse. Because the field belongs to the block itself, reading
  it with `get_field('geselecteerde_vacatures')` — no post ID — is CORRECT
  here, the mirror image of Traps #27's fix (which is for a field that
  belongs to the vacancy being looped over, not to the block rendering the
  loop). Nav arrows sit in the SAME row as the intro (intro left, arrows
  right), above the slider, not stacked in their own row below it — reuses
  `src/js/packages/nav-slider.js`'s `initNavSlider()` plus
  `initOnAppearance()`, dual-enqueued (`main.js` + `editor-scripts.js`) same
  as every other Swiper block (Traps #8).

## Agenda (the `agenda` CPT: taxonomy, sort meta, templates, three blocks)

`agenda` is for one-off/recurring school moments — an "eendaags" moment
(`event_date`, optionally with `start_time`/`end_time` unless "all_day") or a
"meerdaags" one (`start_date`/`end_date`, e.g. a vacation), plus optional
per-moment signup (`signup_enabled`/`signup_method`: `url` or `form`, mirrors
`vacancy`'s own Sollicitatiemethode but PER POST here, not a site-wide
setting — not every moment needs signup, unlike every vacancy needing an
apply flow). All in `acf-json/group_own9c3d5f1a0000.json`
(`position: "acf_after_title"`, so the date/signup fields sit right under
the title, before the content editor — the dates are the primary fact for
this CPT, ahead of the body copy). `inc/functions/agenda-helpers.php` holds
the cross-block-folder logic: `ownframework_agenda_format_date()` (one
Dutch-locale string covering all three date shapes), `ownframework_agenda_
get_signup()` (normalizes the four signup fields into one shape) and an
`acf/save_post` hook that denormalizes whichever date fields apply into two
plain postmeta keys, `agenda_sort_start`/`agenda_sort_end` (Ymd strings) —
needed because WP_Query can't `orderby`/`meta_query` "whichever of two meta
keys applies per row" (event_date vs. start_date), so every moment gets one
shared pair of keys to query against regardless of its own event_type.

Only one taxonomy so far (`agenda-categorie`, hierarchical, categorie-style)
— unlike `vacancy`'s seven, there's no enable/disable options page for it;
`blocks/agenda/hooks/query.php`'s `ownframework_agenda_get_taxonomies()`
still discovers it dynamically (`get_object_taxonomies('agenda', 'objects')`,
filtered by `show_ui`) so a second taxonomy added later via the same SCF UI
mechanism picks up automatically, same reasoning as vacancy's own dynamic
discovery, just without the extra per-taxonomy toggle layer that one has.

**Two templates**, both registered via `inc/functions/register-templates.php`
same as vacancy's own pair:

- `templates/archive-agenda.html` — `acf/agenda-hero` (title/intro, InnerBlocks
  heading+paragraph) plus `acf/agenda` (the filterable results grid) — a
  separate hero block rather than a bare heading/paragraph group directly in
  the template, kept as its OWN block (not folded into `acf/agenda`) for the
  same reason `acf/vacature-hero` is separate from `acf/vacatures`: independent
  settings (height, content alignment, breadcrumbs, background/text colour)
  and independent placement, without either block's own concerns leaking into
  the other's attributes.
- `templates/single-agenda.html` — `acf/agenda-single-hero`, then the same
  66.66%/33.33% `core/columns` split as `single-vacancy.html`, content left,
  `acf/agenda-aanmelden` right.

**Five blocks**, all under their own `own-framework-agenda` block category:

- **`acf/agenda-hero`** — title/intro (InnerBlocks heading+paragraph) on a
  plain colour/gradient background, structurally `acf/vacature-hero` minus
  its search bar (this CPT filters via `acf/agenda`'s own sidebar instead, no
  search need to justify a search field here). A single colourable zone (the
  whole section) is fully covered by WordPress' NATIVE "Kleuren" panel
  (`block.json`'s `supports.color`) — no custom colour attributes/panel
  needed, unlike `acf/agenda` itself (Traps #7 doesn't apply: there are no
  sub-elements to reach). `backgroundColor`/`textColor` DEFAULT to
  `"primary"`/`"base"` (not empty strings) specifically so a freshly inserted
  block already shows real colour contrast instead of black-on-white —
  editable afterwards via Ontwerp like any native colour, but never starting
  blank. `.prose-invert` is applied based on the CHOSEN text colour's
  lightness (`ownframework_color_is_light()`), same mechanism as `acf/
vacature-hero`, so the InnerBlocks heading/paragraph read correctly against
  either a light or dark background.
- **`acf/agenda`** — the filterable results grid, structurally a straight
  port of `acf/vacatures` (same accent/card colour attributes as plain block
  attributes, not `supports.color` — Traps #7 — same REST-backed Alpine
  filtering). Two things are genuinely different from vacancy's version: an
  extra **"Periode"** facet (aankomend/afgelopen/alle) that ISN'T a
  taxonomy — a single-select radio bound to `$store.agendaFilters.periode`
  (a plain string, not an array like the taxonomy checkboxes), backed by a
  `meta_query` against `agenda_sort_end` rather than a `tax_query` — and a
  **chronological ascending default sort** ("eerstvolgende eerst") instead
  of "nieuwste eerst": this is a calendar of upcoming moments, not a news
  feed, so both the initial main-query order and `src/js/scripts/
agenda-filter.js`'s own default `sortBy` reflect that. A "meerdaags" moment
  stays "aankomend" until its own END date passes (not its start date), so
  a multi-day vacation stays visible for its whole span instead of dropping
  out on day two — see `agenda_sort_end`'s own role above. A "Weergave"
  lijst/raster toggle (custom `media/icons/list.svg`/`grid.svg`) sits next to
  the sort dropdown — plain LOCAL `x-data` on the block's own `<section>`
  (not `$store.agendaFilters`), because it never needs to be read outside
  this one block instance and doesn't touch the query/REST params at all,
  only which Tailwind layout classes `render.php`'s own
  `data-own-agenda-results` div carries. Persisted in `localStorage` rather
  than the URL/REST call — a display preference, not a filter, so it's
  remembered across visits rather than made shareable/bookmarkable.
  `ownframework_agenda_render_cards()` (`hooks/query.php`) deliberately
  renders the cards with NO layout wrapper of its own, so the chosen view
  survives every filter/sort/page fetch (only that div's innerHTML is ever
  replaced, never the div itself). Each card's own `parts/card.php` leads
  with a fixed-shape "day / month" visual date badge
  (`ownframework_agenda_get_date_badge()`, `inc/functions/agenda-helpers.php`)
  rather than a plain text line — WHEN a moment happens is this CPT's single
  most important fact, more so than for a vacancy, so it gets a quick-scan
  shape instead of a sentence a visitor has to read. That helper is
  deliberately separate from `ownframework_agenda_format_date()`: the badge
  can only show a day number + month (a same-month "meerdaags" range renders
  as "18–22" in the same slot at a smaller font size; a range crossing a
  month/year anchors on the start date only), so the full precise text
  (time-of-day, a cross-month range) still appears as a small caption under
  the title — nothing the compact badge can't express is lost, just
  demoted to secondary. The badge's own colour is a SEPARATE attribute,
  `dateBadgeColor` → `--own-agenda-date-accent`, not the same `accentColor`
  the category badges/filters/pagination use — empty by default, so
  `agenda.css`'s `.own-agenda-date-badge` falls back to `--own-agenda-accent`
  (then the site-wide fallback) until an editor explicitly wants the date
  badge to stand out as a different colour from everything else.
- **`acf/agenda-single-hero`** — mirrors `acf/vacature-single-hero`'s
  layout/colour-attribute shape exactly (breadcrumb → title → badges, each
  its own colour attribute since the native "Kleuren" panel can't reach
  sub-elements — Traps #7), but reuses `acf/agenda`'s own "day / month"
  square date badge NEXT TO the title (identical markup/size, just read here
  too — see that block's own bullet above) instead of "X geleden geplaatst":
  when a moment happens matters far more here than when the post was
  authored, and the archive card and this detail page should read as the
  same design language rather than inventing a second date treatment. The
  small caption under the title (`ownframework_agenda_get_card_caption()`,
  shared with the card) only appears when the badge itself can't say
  everything — a time, or a range crossing a month/year. Has its own
  `dateBadgeColor` attribute too, same relationship to `badgeAccentColor`
  (the category badges' colour) as `acf/agenda`'s own `dateBadgeColor` has to
  `accentColor` — a separate, optional override rather than a forced split.
- **`acf/agenda-aanmelden`** — the signup CTA card, mirrors `acf/
vacature-apply`'s InnerBlocks-intro-plus-data-driven-button shape (external
  link vs. a Forminator-modal button, same `shortcode_exists('forminator_
form')` guard), but renders NOTHING at all when `signup_enabled` is off for
  that moment (`return;` before any markup) — deliberately, since not every
  moment needs signup, unlike vacancy's apply flow which is always on. This
  also means the block appears empty while editing `templates/
single-agenda.html` itself in the Site Editor (no real moment as context to
  read `signup_enabled` from) — expected, not a bug; preview against an
  actual agenda post with signup enabled to see it render.
- **`acf/agenda-uitgelicht`** — a structural 1:1 port of `acf/
vacatures-uitgelicht` (same InnerBlocks intro, grid/slider "Weergave" toggle,
  prev/next nav + optional pagination dots next to the intro, editor-vs-
  front-end slider split — see that block's own bullet above for the full
  reasoning behind each piece) — the page-usable "show some moments"
  block, not tied to either agenda template, with its own Relationship field
  (`acf-json/group_own_agenda_uitgelicht_selectie.json`, field name
  `geselecteerde_momenten`, `location: block == acf/agenda-uitgelicht`) for
  a hand-picked selection. Two deliberate departures from a blind copy, not
  oversights:
  - **"Bron" is "upcoming", not "recent"** — `acf/vacatures-uitgelicht`'s
    automatic mode orders by `post_date` (most recently published), which is
    meaningless for a calendar; this instead reuses `blocks/agenda/hooks/
query.php`'s own denormalized `agenda_sort_start` meta and
    `ownframework_agenda_build_periode_meta_query('aankomend')` — the exact
    same query the archive's own "Periode: Aankomend" filter runs — so
    "featured moments" on a landing page and "upcoming" on the archive can
    never quietly disagree about what counts as upcoming.
  - **Its own `dateBadgeColor` attribute**, absent from `acf/
vacatures-uitgelicht` (the vacancy card has no date badge to colour) — same
    "separate from `accentColor`, falls back to it when empty" relationship
    as `acf/agenda`'s and `acf/agenda-single-hero`'s own `dateBadgeColor`.
  Card markup is `blocks/agenda/parts/card.php` (`get_template_part()`,
  reused as-is — same "context-independent once `the_post()` has run"
  contract as the vacancy card), and the slider variant
  (`own-agenda-uitgelicht` — `src/js/scripts/agenda-uitgelicht-slider.js`,
  `src/css/components/swiper.css`) is the ported class-name pair to
  `acf/vacatures-uitgelicht`'s own, kept as two parallel sets rather than a
  shared one so the two slider blocks can never pick up each other's
  buttons/pagination.

## Verhalen (the `verhaal` CPT: taxonomy, quote field, templates, five blocks)

`verhaal` ("Verhalen"/"Verhaal") is for storytelling content — one
`verhalen-categorie` taxonomy (hierarchical, categorie-style, `show_ui`),
discovered dynamically the same way `agenda`'s own single taxonomy is
(`ownframework_verhalen_get_taxonomies()`, `blocks/verhalen/hooks/
query.php`) rather than hardcoded, so a second taxonomy added later via the
same SCF UI mechanism picks up automatically. One own field beyond the CPT's
native `title`/`editor`/`thumbnail`/`excerpt` support:
`acf-json/group_own_verhaal_fields.json`'s `quote` (`position:
"acf_after_title"`, a short pull-quote) — deliberately separate from the
native excerpt: `quote` is a punchy line shown on the archive card and over
the hero's photo, the excerpt is the actual intro paragraph shown in the
detail page's hero text column. Neither ever shows on both — see each
block's own docblock for exactly where.

**Filtering/sorting is a structural port of `acf/news`'s own live system**
(Alpine store, REST endpoint, faceted counts) — the same choice was
reconsidered and reversed once a real filter UI was actually requested: a
story archive turned out to want the same "filter by category, sort,
paginate without a full page reload" shape as the blog, not a plain static
grid. `blocks/verhalen/hooks/query.php` therefore has the same three-caller
split as `blocks/news/hooks/query.php` (initial render, REST endpoint,
`pre_get_posts`) — `parse_params()`/`build_tax_query()`/`facet_counts()` are
near-identical copies, same "duplicate per CPT family rather than share"
convention the other three archive blocks already use with each other. Two
differences from news, both because this taxonomy is DISCOVERED rather than
hardcoded: `ownframework_verhalen_get_taxonomies()` (not a fixed
`"category"` constant) feeds `get_filter_data()`/`parse_params()`, and the
`pre_get_posts` hook guards on `is_post_type_archive('verhaal')` rather than
`is_home()` — this CPT has its own real archive page, no
"Berichtenpagina"-style setting to fall back on. `src/js/scripts/
verhalen-filter.js`'s `$store.verhalenFilters` is a straight port of
`news-filter.js`'s own `$store.newsFilters` (same shape, minus a search
box — neither this CPT nor `news` has one), reading `acf/verhaal-hero`'s own
`data-own-verhalen-filters` config the same way. `window.OwnVerhalen`
(`wp_localize_script`, same file) resolves `archiveUrl` via
`get_post_type_archive_link('verhaal')` directly — no `page_for_posts`
fallback needed, unlike `news`'s own `window.OwnNews`.

**Two templates**, both registered via `inc/functions/register-templates.php`
same as the other two CPTs' pairs:

- `templates/archive-verhaal.html` — `acf/verhaal-hero` (title/intro,
  InnerBlocks heading+paragraph, plus the category filter bar — structurally
  `acf/news-hero`, not `acf/agenda-hero`'s richer periode/month toolbar)
  plus `acf/verhalen` (the results grid).
- `templates/single-verhaal.html` — `acf/verhaal-single-hero`, then the
  SAME 66.66%/33.33% `core/columns` split as `single-vacancy.html`/
`single-agenda.html`: `core/post-content` in the 2/3 column, `acf/
verhaal-share` in the sticky 1/3 column. This CPT has no per-post CTA block
  equivalent to `acf/vacature-apply`/`acf/agenda-aanmelden` to put in that
  sidebar slot, so the share bar fills it instead of sitting under the
  content the way `acf/news-share`/`acf/vacature-share`/`acf/agenda-share`
  each do in their own templates — see that block's own docblock.

**Five blocks**, all under their own `own-framework-verhalen` block category:

- **`acf/verhaal-hero`** — title/intro on a plain colour/gradient
  background (native "Kleuren" panel, no custom attribute needed for that
  one zone), plus the category filter bar (`parts/filter-bar.php`, a
  structural copy of `acf/news-hero`'s own) — its own `accentColor` plain
  attribute + panel colours the dropdown triggers/badges, a sub-element the
  native panel can't reach (Traps #7), independent from `acf/verhalen`'s own
  `accentColor` below (not synced, same precedent as `acf/vacature-hero`'s
  `searchButtonColor` vs. `acf/vacatures`' own `accentColor`).
- **`acf/verhalen`** — the results grid: sort dropdown + results count +
  cards + pagination, all wired to `$store.verhalenFilters` exactly like
  `acf/news`'s own render.php. **Portrait cards are the whole point of this
  CPT's design**, not a variant of `acf/news`'s landscape-image-on-top-of-a-
  white-card shape: `blocks/verhalen/parts/card.php`'s `aspect-[3/4]` card
  has its featured image filling the ENTIRE box (`absolute inset-0
  object-cover`), with title/quote/date/"Lees verhaal" link sitting on top
  of it, bottom-anchored, legible via a dark-to-transparent scrim
  (`.own-verhalen-card__scrim`, `verhalen.css`) rather than a separate white
  card body below the photo. Deliberately **no excerpt and no category
  badges on the card itself** — the excerpt belongs in the detail page's own
  hero (see below), and badges would compete with the quote for the same
  cramped bottom-of-card space; the category is still a real filterable
  facet, just surfaced on the hero's filter bar and the single hero's
  badges, not repeated on every card. The grid is capped at **1/2/3
  columns** (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`), never 4 like a
  wider landscape-card grid might use — portrait cards in a fourth column
  read as too narrow/tall. Its own `accentColor` attribute (checkboxes,
  pagination, sort panel — Traps #7) colours the filter/pagination chrome;
  `cardTextColor` is a SEPARATE attribute (also Traps #7) for the title/
  quote/date/link on every card, deliberately ONE shared colour rather than
  `acf/news`'s own four separate card colours — this card has no separately
  colourable background (that's the photo itself), so there's only one zone
  to colour. Defaults to white (`verhalen.css`'s `--own-verhalen-card-text-
color`), safe against the scrim regardless of photo or palette.

  **Three-tier card background**, not just photo-or-nothing: a story's own
  featured image, else `fallbackImage` (a plain image block attribute +
  `MediaUpload`, same picker contract as `acf/cta`'s own — a project-wide
  placeholder photo, e.g. a brand shot), else a flat colour fill
  (`.own-verhalen-card--fallback`) — never a broken/empty box. `blocks/
verhalen/parts/card.php` resolves `fallbackImage` via `ownframework_
verhalen_get_configured_fallback_image()` (`hooks/query.php`) rather than
  reading `$block` directly — same "re-parse the stored archive-verhaal
  template" mechanism as `ownframework_verhalen_get_configured_per_page()`
  (Traps #29), since the card template has no direct handle on the block
  instance that rendered it. That resolver is memoized with a `static`
  variable, unlike the `perPage` one — it's called once PER CARD WITHOUT A
  PHOTO in a loop, not once per page load, so skipping a repeat template
  fetch/parse on every such card actually matters here. The scrim/text
  overlay renders identically across all three tiers (even over a flat
  colour it adds a touch of depth), so a story never looks structurally
  different depending on which tier its card happens to use.
- **`acf/verhaal-single-hero`** — mirrors `acf/vacature-single-hero`'s
  colour-attribute-per-sub-element shape (breadcrumb/title/date/excerpt/
  badges, Traps #7) — `dateColor` (between title and excerpt, same
  `get_the_date()` the archive card itself shows, just given room to be
  prominent here instead of a small line under a photo) defaults to white
  like `titleColor`/`breadcrumbColor`/`descriptionColor`, for the same
  reason: it sits on the coloured background and needs contrast out of the
  box. The LAYOUT is a deliberate departure from all three other
  CPT single-heroes: the text column is `lg:w-2/3` (same ratio as
  `single-vacancy.html`'s/`single-agenda.html`'s own `core/columns` split
  further down those pages, here applied to the hero itself), and the photo
  is **`lg:absolute`** — positioned relative to the section (which already
  has `position:relative`), top- and right-aligned, with its own fixed
  height (`lg:h-112`, 28rem) rather than sharing a flex/grid row with the
  text. This is why it's absolute rather than a normal row item like the
  other three CPTs' hero photos: in a shared row the TALLEST child dictates
  the row's — and therefore the coloured section's — own height, so the
  background could never end up shorter than the photo. Taking the photo out
  of flow entirely means the section's height tracks the text ALONE, while
  the photo's own fixed height (deliberately taller than a typical title +
  excerpt + badges column) hangs independently over it and typically extends
  past the section's own bottom edge, over whatever content follows — the
  "photo breaks out of the hero" look from the reference design. No clipping
  precaution needed: an absolutely positioned element is never clipped by an
  ancestor without `overflow:hidden` (none here), and paints above a later,
  plain static sibling (`core/post-content`) regardless of DOM order — an
  explicit `z-20` on the photo is just for clarity, not a required fix. Only
  active from `lg` up; on mobile the photo is a normal, in-flow block below
  the text, no overlap (which would only ever obscure text on a narrow
  screen). The `quote` field (ACF) renders ON the photo itself — same
  `.own-verhalen-card__scrim`/`__quote` classes as the archive card, so both
  pages share one visual idiom for "photo with a quote on it" — not
  alongside or below it. `quoteColor` only comes into play in the one
  fallback path where there's no featured image to put the quote on: there
  it renders as a plain bold line in the text column instead of being
  silently dropped, defaulting to `inherit` (unlike `titleColor`/
`descriptionColor`, which force white by default since THEY sit on the
  coloured background and need contrast out of the box — this fallback
  quote sits in the ordinary page background instead).
- **`acf/verhaal-share`** — the share bar, a straight port of `acf/
news-share` minus its tags row (this CPT has no tags-equivalent concept,
  only the one category taxonomy, already shown in the hero above). Sits in
  `single-verhaal.html`'s own sticky 1/3 sidebar column rather than under
  the content — see that template's own bullet above for why.
- **`acf/verhalen-uitgelicht`** — the page-usable "show some stories" block,
  not tied to either `verhaal` template, structurally a 1:1 port of `acf/
vacatures-uitgelicht`/`acf/agenda-uitgelicht`/`acf/news-uitgelicht` (same
  InnerBlocks intro, grid/slider toggle, prev/next nav next to the intro,
  editor-vs-front-end slider split, hand-picked selection via its own
  Relationship field — `acf-json/group_own_verhalen_uitgelicht_selectie.
json`, field name `geselecteerde_verhalen`, `location: block == acf/
verhalen-uitgelicht`). Two departures from a blind copy:
  - **No `accentColor`.** The other three cards each have badges an
    `accentColor` attribute colours; `blocks/verhalen/parts/card.php`'s own
    portrait card has none (Traps #7 doesn't need a target here) — only
    `cardTextColor` (title/quote/date/link over the photo) is a real
    colourable zone, reusing the SAME `--own-verhalen-card-text-color`
    custom property `acf/verhalen` itself sets, just from this block's own
    wrapper instead.
  - **No equal-height Swiper fix.** `own-vacatures-uitgelicht`/`own-agenda-
uitgelicht`/`own-news-uitgelicht` each need their own `.swiper-slide {
display:flex }` pair (`swiper.css`, Traps #30) because their cards have
    variable, content-driven height (title + text + badges) that a
    percentage height can't reliably stretch. This card's height is instead
    fully determined by its own `aspect-[3/4]`, independent of any ancestor
    height — every slide already ends up equal height purely from sharing
    the same width per `slidesPerView` breakpoint, so the fix has nothing to
    do here (documented in `swiper.css` itself, right where a reader would
    expect to find the other three).
  - Card markup is `blocks/verhalen/parts/card.php` (`get_template_part()`,
    reused as-is) — which also means whatever `fallbackImage` is configured
    on the ARCHIVE's own `acf/verhalen` block automatically applies here
    too (that resolver re-reads the stored `archive-verhaal` template
    regardless of which block asked, see `acf/verhalen`'s own bullet above)
    — one site-wide placeholder photo, not a second setting to keep in sync.

## Archive filters: inside the hero

The filter bars of the news, agenda and verhalen archives live INSIDE their
hero (`blocks/{news,agenda,verhaal}-hero`), under the title and intro, as
white pills (`src/css/components/hero-filters.css`, shared by all three) —
not as a separate strip between hero and results, which read as floating.
Same idea as `acf/vacature-hero`'s search bar. The pills and their panels set
their own (contrast) text colour so they stay light UI on any hero colour;
"Wis filters" is a text link in the hero's own colour. The filter row carries
`x-data` (an Alpine root) and the `data-own-*-filters` config the stores read
by attribute: without that root, "Wis filters" — the one control not inside a
dropdown's own `x-data` — was never initialised and stayed hidden by x-cloak.

Colours are block settings in each hero's "Kleuren" panel: filter button
background/text (`filterButtonColor`, `filterButtonTextColor`, passed as
`--own-hero-filter-bg/-text`; empty = white pill) and `accentColor` for the
count badge and the checkboxes. A pill with a selection deliberately keeps its
colours — only the count badge shows that a filter is active. The news, agenda and verhalen heroes default to `contentAlign: "left"`:
content on the container's left edge, in line with the results below.
`vacature-hero` deliberately stays centred by default (its search bar is the
focus there); both keep the other option.

## Empty states

Every block that loads posts shows the same empty state when there are none:
`template-parts/components/empty-state.php` (+ `src/css/components/
empty-state.css`) — deliberately small: a title and one line of text in a
light box sized to its content, centred, optional text-link action; no
icon, no full-width panel. Colours derive from `currentColor`, so it works on
coloured sections too.
Used by the four archives (`news`, `vacatures`, `agenda`, `verhalen`), their
four `*-uitgelicht` blocks and `faqs`; a new post-listing block should use it
as well.

- **Archives** tell two situations apart in their own `*_render_cards()`
  (`hooks/query.php`), which takes the parsed filter params (the REST callback
  passes its own; the first page load reads `$_GET`): filters/search that
  match nothing → "Geen … gevonden" + a "Filters wissen" link that calls the
  block's Alpine store; nothing published → "Nog geen …". The agenda words the
  second one per Periode (aankomend/afgelopen/alle).
- **Uitgelicht**: the "nothing yet" variant; in the editor a handpicked
  selection with nothing chosen says where to pick items instead.
- **FAQs**: now also shows the empty state on the site (it used to render
  nothing there).

## Traps that fail silently

1. **Alpine attributes must be single-line, arrow-free, `esc_attr()`-wrapped.**
   Block output passes through `the_content`'s `wpautop()`/`wptexturize()`, which
   splice stray `<br>` into multi-line attribute values and mangle `=>`.
2. **Dynamic Tailwind class names are invisible to the scanner.** Blocks build
   them as `'bg-' . $token`, so Tailwind can't see them. A new colour _family_
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
   element to be a boxed _sub_-element rather than the root wrapper (e.g.
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
   inside the editor preview itself — `facts-slider.js` IS in that list (its
   slides are plain attributes, so the editor renders the same markup as the
   front end), while `content-slider.js` deliberately is NOT, because
   `blocks/content-blokken-slider` renders a plain grid instead of a slider
   inside the editor (Traps #13).
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
10. **A "constrained" block gets zero side padding on any viewport narrower
    than `settings.layout.contentSize` (1100px) unless
    `settings.useRootPaddingAwareAlignments` is on** — `theme.json` sets this
    plus a matching `styles.spacing.padding` (left/right only, using the
    same `spacing|s` preset as `blockGap`) precisely so a plain block gets a
    side gutter on mobile. This now covers `align:"full"` too, not just `""`/
    `"wide"` — "full" is a third, still-constrained width tier (see
    "Alignment, width and full-bleed backgrounds" above), not an edge-to-edge
    escape hatch any more, so it needs the same gutter treatment as the other
    two, no exceptions any more — `blocks/hero`/`/cta` used to be excluded
    from the "full" tier (an earlier, now-superseded version of this same
    section above) but that required deleting their own legacy
    `$use_container` code first (Traps #24); once that was gone, "full" on
    them covers exactly like `blocks/content-blok`/a bare `core/paragraph`
    always has. A block that genuinely needs an edge-to-edge BACKGROUND now
    does that itself (see
    `blocks/hero/render.php`'s `$bleed_class` docblock) rather than through
    core's `align:full`, which no longer means that.
11. **WordPress' native block-toolbar "Uitlijning" (wide/full) control never
    appears on a bare, top-level block inside a template part** (`blocks/
header`, `/header-topbar`, `/footer`, `/footer-bottom` — all 4 sit
    directly in `parts/header.html`/`parts/footer.html`, no wrapping
    `core/group`), **even with `supports.align` declared** — a block-based
    theme's default ("flow") layout deliberately skips offering `wide`/`full`
    at all (`packages/block-editor/src/layouts/flow.js`'s `getAlignments`
    only adds them for _non_-block-based/hybrid themes); only a
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
13. **SCF's `<InnerBlocks />` placeholder renders ALL children as one blob —
    there is no hook to wrap each child individually**, which is exactly what
    a slider needs (every slide must sit in its own `.swiper-slide`).
    `blocks/content-blokken-slider` solves this by rendering the two contexts
    differently, and any future "repeat a nested block" layout should copy
    that split rather than fight it:
    - **Front-end**: skip the placeholder entirely, loop
      `$wp_block->inner_blocks` and call `->render()` per child, wrapping each
      in its own element (same `$wp_block` access `blocks/header` already uses
      to repeat logo/CTA into the mobile menu). Nested SCF blocks resolve
      their OWN `<InnerBlocks />` correctly here — `$is_preview` is computed
      per block from `is_admin() && acf_is_block_editor()`, not inherited from
      the parent, so a child never renders its editor-only branch on the live
      site.
    - **Editor** (`$is_preview`): emit the real `<InnerBlocks />` placeholder.
      Only that is editable — `->render()` output is static HTML with no block
      handles. Don't wrap it in a Swiper either: Swiper hides/transforms every
      non-active slide, so precisely the cards an editor wants to click become
      unreachable. A CSS grid shows them all at once, stays editable, and
      still reads as "separate cards".
      `<InnerBlocks className="x" />` is honoured by SCF in both contexts, and
      REPLACES its default `acf-innerblocks-container` class rather than adding
      to it (`ACFInnerBlocksComponent`, plus the server-side regex in the
      plugin's `includes/blocks.php`) — so a custom `className` is the supported
      styling hook, but check nothing relies on the default class first.
14. **A `supports` flag written as a TOP-LEVEL `block.json` key is silently
    dropped** — `register_block_type()` only reads the block.json fields on
    its own known list, and ignores anything else without a warning or error.
    `blocks/hero` had `"multiple": false` at the top level for a while (the
    shape `acf_register_block_type()`'s old PHP array used), so a second hero
    stayed insertable despite the file "saying" otherwise, and despite
    `hooks/default-template.php`'s docblock claiming the pair worked together.
    It belongs under `"supports"`: the editor reads it as
    `hasBlockSupport(name, "multiple", true)`, which resolves against
    `supports` only. When porting anything from ACF's PHP-array registration
    style, check each key against the block.json schema rather than assuming
    it carries over — and verify the flag actually bites in the editor, since
    a wrong placement looks identical in the file.

15. **A hand-written `acf-json/*.json` with a `modified` timestamp in the
    future can never finish syncing** — SCF decides sync is available with
    `$modified > get_post_modified_time('U', true, $post['ID'])`
    (`includes/admin/admin-internal-post-type-list.php`). Syncing writes the
    DB record with the CURRENT time, so a future-dated JSON stays "newer"
    forever and the "Sync available" prompt reappears immediately, with no
    error anywhere. Same for a missing `modified` key on an already-synced
    group. When authoring one of these files by hand (a new options page, a
    new field group) set `modified` to `time()` or earlier — never a rounded-
    up placeholder. Once SCF itself saves the group it rewrites the file with
    the right value; the hand-written stamp only has to survive until then.

16. **An SCF options page's `position` is an insertion INDEX, not a sort
    order** — WordPress' `add_submenu_page()` appends outright whenever
    `$position >= count($submenu[$parent])`, and only really inserts when the
    number is smaller than the count at that moment
    (`wp-admin/includes/plugin.php`). SCF hands `position` straight through
    and registers pages in local-JSON load order (alphabetical by filename),
    so the final order depends on registration order, not on the numbers. It
    produced `Algemeen(0), Stijlen(1), Koppelingen(3), Live search(2)` — a 3
    above a 2 — with no way to fix it by picking better numbers, since every
    value stays hostage to registration order.
    `inc/functions/options-page-order.php` therefore re-sorts
    `$submenu[<parent>]` by each page's declared `position` on `admin_menu`
    priority 200 (SCF registers at 99). Keep using plain `0,1,2,3` in the
    page JSON; that hook is what makes them mean what they look like.
17. **`core/column`'s "Uitrekken om te vullen" (`is-vertically-aligned-stretch`,
    `align-self: stretch`) only stretches the COLUMN's own box to match its
    tallest sibling — WordPress does nothing to make whatever block sits
    inside it actually grow to fill that now-taller box.** A column is plain
    block flow internally, so a block just keeps its own natural height,
    leaving dead space below it — this is a real gap in core, not something
    misconfigured here. `src/css/components/columns.css` closes it: the
    stretched column becomes `display:flex;flex-direction:column`, and its
    direct child gets `flex:1 1 auto;min-height:0`, so a single block fills
    the column and several stacked ones share it.
    That generic fix can't reach INSIDE a block that additionally picks its
    own fixed height — `blocks/hero`'s "Hoogte" (`min-h-[Xvh]` on a
    `.own-hero-height` div, not the section root) and `blocks/google-maps`'s
    "Hoogte" (`h-[Ypx]` on the root) are the two so far. Both get an explicit
    `!important` override in the same file when inside a stretched column,
    because the whole point of stretching is to match the SIBLING's height,
    not to keep imposing one of the block's own regardless of what that
    sibling turns out to be. **Any future block with its own fixed-height
    attribute needs the same treatment added to `columns.css`** — without it,
    that block will silently keep ignoring "Uitrekken om te vullen".
    Those two overrides use a plain DESCENDANT selector (space), not
    direct-child (`>`), and that's deliberate, not sloppy: on the front end
    `.own-hero`/`.own-google-maps` IS the column's direct child, but in the
    editor it never is. SCF wraps every "blockVersion":3 block's `render.php`
    output in its OWN extra `<div {...useBlockProps()}>` first (confirmed by
    reading `assets/build/js/pro/acf-pro-blocks.js`'s `BlockBody` directly,
    not assumed) — that wrapper, not our block's own root, is what's actually
    adjacent to the column. The GENERIC rule (`> *`) still correctly uses
    direct-child: it targets "whichever element is the column's actual direct
    child", front end or editor, and a wrapper existing-or-not doesn't change
    that it's still correct there. Only a selector aimed at OUR OWN class
    needs to skip past a wrapper that might or might not be there.
18. **The Maps JavaScript API's `Map` has a `cameraControl` option
    (rotate/tilt/pan bundled behind one button) that is SEPARATE from
    `streetViewControl`/`mapTypeControl`/`fullscreenControl`/`zoomControl` and
    isn't mentioned alongside them in most guides — it defaults to visible
    regardless of what those four are set to.** `blocks/google-maps`'s
    `google-map.js` originally set only the four classic ones, "just the map,
    no extra chrome" per the block's own purpose — and got exactly that button
    anyway, sitting directly above the zoom control, confirmed by actually
    driving a headless browser against the live page rather than guessing
    from the rendered HTML (`el.querySelector('gmp-internal-camera-control')`
    was present and visible; disabling the classic four did nothing to it).
    Set `cameraControl: false` explicitly alongside the other four — don't
    assume disabling the named controls you know about is the complete list
    for "no extra map chrome" without checking the actual rendered DOM.
19. **`@tailwindcss/typography`'s `.prose img { margin: 2em 0 }` doesn't know
    or care where an `<img>` came from — it hits ANY image nested inside
    `.prose` (`core/post-content`'s own className, `templates/page.html`),
    including ones a THIRD-PARTY SCRIPT injects deep inside a block's own
    DOM long after render.php ran.** This is why every hand-written `<img>`
    in this theme's blocks (`hero`, `cta`, `content-blok`,
    `afbeeldingen-slider` — 13 occurrences) already carries `m-0!`: that
    `!important` utility beats the (non-`!important`) prose rule outright, a
    fix that's always been scattered across those files with no comment
    tying it to a cause, until now.
    `blocks/google-maps` hit the SAME rule a different way: Google's own JS
    injects the zoom button icons long after render.php returns, so there's
    no tag of ours to put `m-0!` on. At this block's 11px font-size context,
    `.prose`'s `2em` margin computed to a real 22px — which, on an icon
    that's absolutely positioned and centered via `transform` (a technique
    that assumes zero margin), pushed it 22px past where it was centered and
    straight into the button's own `overflow: hidden`, leaving only a sliver
    visible. Confirmed by walking the actual DOM in a live browser
    (`el.closest('.prose')`) and diffing computed `margin` before/after, not
    guessed from the screenshot. Fixed with `not-prose` on the block's own
    root (`own-google-maps`) — the theme's existing escape hatch, already
    used by `header`/`footer`/`hero`'s breadcrumbs — which excludes every
    descendant, including DOM that doesn't exist yet when render.php runs.
    **The two fixes are for two different situations, not interchangeable:**
    `m-0!` for an `<img>` YOU write in render.php; `not-prose` on the block's
    root for a block whose visual output isn't fully under your control
    (third-party JS, a plugin-rendered widget) — you can't add `m-0!` to a
    tag you never wrote.
20. **SCF's front-end `<InnerBlocks />` replacement wraps the children in a
    plain `<div>`, never in whatever list/semantic tag the surrounding markup
    might suggest** — confirmed by reading
    `acf_replace_inner_blocks_in_block_content()`/`acf_rendered_block_v3()`
    (`secure-custom-fields/includes/blocks.php`) directly: the wrapper is
    always `'<div class="' . $class . '">' . $content . '</div>'`, where
    `$class` comes from the `className`/`class` attribute written on the
    literal `<InnerBlocks />` placeholder string (falling back to
    `acf-innerblocks-container`), never from the block's own template markup
    around it. `blocks/proces` needed a `<ol>`-style stepper — writing
    `<ol><InnerBlocks ... /></ol>` would NOT have produced `<ol><li>…</li></ol>`,
    it produces `<ol><div class="own-proces-list">…</div></ol>`, an invalid
    nesting most browsers silently "fix" by hoisting the div's children, with
    unpredictable layout results. The fix used here: don't fight it — give the
    child blocks (`acf/proces-stap`) a plain `<div>` root too, and get the
    numbering/last-item styling from a NAMED CSS counter +
    `:last-child`(`src/css/components/proces.css`), neither of which cares
    whether the elements involved are `<li>`s or `<div>`s. Reach for a real
    `<ol>`/`<li>` only when nothing (front-end or editor) sits between the
    list and its items but plain text — anywhere a nested ACF block is one of
    the items, this wrapper div is unavoidable.
21. **A continuous "marquee" scroll doesn't need Swiper (or any JS) at all —
    a `@keyframes` animation over a track containing the content TWICE loops
    perfectly regardless of item count.** `blocks/logo-slider` was originally
    built on Swiper (autoplay tuned toward a near-0 delay to fake
    continuity), then rewritten to plain CSS: render.php outputs the logos
    array twice in a row inside one flex track, and
    `src/css/components/logo-slider.css` translates that track exactly `-50%`
    on a linear, infinite loop. Because the second half is a byte-for-byte
    copy of the first, the animation restarting at `0%` is visually
    indistinguishable from continuing into a third copy — no minimum item
    count needed (unlike Swiper's own `loop: true`, which needs enough real
    slides to fill the viewport more than once before it stops visibly
    jumping at the seam). The duplicate half gets `aria-hidden` plus an empty
    `alt` so a screen reader doesn't announce every logo twice. Reversing
    direction is `animation-direction: reverse` on the SAME `@keyframes`, not
    a second one. `animation-timing-function: linear` (not the browser
    default `ease`) is what actually makes it read as constant-speed rather
    than repeatedly slowing down and speeding back up — invisible on a short
    transition, obvious on one long continuous one. Reach for an actual
    slider library only when the block needs real interactivity (drag,
    per-slide navigation, pagination) that a passive decorative band doesn't.
22. **A block whose OWN moving content — not just its background — must
    reach 100vw needs a DIFFERENT full-bleed trick than
    `ownframework_full_bleed_class()`, and it has to sit on a CHILD of the
    block's section, never on the section itself.** That helper
    (`inc/functions/full-bleed.php`) is `position: absolute` on a layer
    nested inside a `position: relative` section, deliberately: it's for a
    decorative layer behind content that itself stays in normal,
    width-constrained flow (`hero`/`cta`'s background media). `blocks/
logo-slider`'s "Volledige schermbreedte" needed the scrolling band ITSELF
    to span 100vw while still occupying real space in the page (`absolute`
    would collapse it out of flow and let following content overlap it) —
    the fix keeps the same `left-1/2` + `-translate-x-1/2` + `w-screen`
    centering math but on a `position: relative` element, nested ONE level
    inside the section, exactly like the absolute layer is.
    **The first version of this got it wrong by putting those classes
    directly on the section (`<section>`) itself, and it silently bled only
    about HALF as far as the true viewport edge — no error, just visibly
    short of the screen edge.** The reason: a block sitting in
    `core/post-content` doesn't get its width/centering from a narrower
    ancestor wrapping it — WordPress' own "constrained" layout CSS
    (`.is-layout-constrained > * { max-width:…; margin-inline:auto }`)
    applies `margin-inline:auto` centering DIRECTLY to the section itself as
    a top-level child. Percentage `left` on a `position:relative` element
    resolves against its CONTAINING BLOCK — its parent — and is added ON TOP
    of the element's own already-computed static (in-flow) position. Put the
    bleed classes on the section, and its parent is the (wide, ~full-width)
    `core/post-content`, while the section's OWN static position is ALREADY
    offset by the very same centering margin the calculation assumes doesn't
    exist yet — the two centerings don't cancel, they stack, landing the
    bleed halfway between the section's normal edge and the real viewport
    edge. An `absolute` child doesn't have this problem because it has no
    static position to double up with — its `left:50%` is measured purely
    from its containing block's edge, and that containing block is the
    SECTION (its nearest positioned ancestor), which is why the existing
    `full-bleed.php` helper never had this bug. Putting the `relative`
    version on a plain (non-centered) CHILD of the section reproduces the
    same clean math: the child's containing block is the section (after ITS
    own centering already resolved), and the child itself has no competing
    `margin-inline:auto` of its own, so `left-1/2` + `-translate-x-1/2`
    lands it exactly at the true viewport edge regardless of `""` vs `"wide"`.
    One practical win from this structure: content that must NOT bleed (a
    heading above the band, say) just stays a sibling of the bleeding child
    inside the same section, never touched by the trick at all.
    Relies on the same existing `html { overflow-x: clip }`
    (`src/css/app.css`) to avoid a horizontal scrollbar, and don't reach for
    `.container` for this either (Traps #12) — that class is `hero`/`cta`'s
    legacy `align:"full"` content specifically, not a general-purpose
    full-width utility.
23. **`core/post-content` itself is deliberately `align:"full"` in
    `templates/single.html`/`page.html` — not a width choice, a structural
    escape hatch** — it's how the whole page's content area reaches the true
    viewport edge despite sitting inside `<main>`'s own constrained layout
    (`<main>` is `layout:{"type":"constrained"}` too, per `STRUCTURE.md`),
    which every full-bleed-background trick in this theme, and the
    gutter-parity math between page content and `header`/`footer`, quietly
    depends on. This collided directly with repurposing native `"full"`
    align into a third, constrained 1440px width tier (see "Alignment,
    width and full-bleed backgrounds" above): a blanket `.alignfull`
    override would ALSO cap `core/post-content` at 1440px, breaking that
    structural role on every single post/page — not a hypothetical, this
    would have silently regressed the entire site's layout on first load.
    Fixed by excluding `.wp-block-post-content` explicitly from the override
    selector (`src/css/app.css`) — the auto-added `wp-block-<name>` class
    every block gets is a stable, core-guaranteed hook for this, not a
    fragile implementation detail.
24. **Finding `align === 'full'` special-casing in a block's own render.php
    is a sign to DELETE that code, not to leave the block out of a width-tier
    change** — `blocks/hero`/`/cta` both had leftover `$use_container` logic
    from a much earlier, unrelated removal of "full" support, which
    special-cased any already-saved `align:"full"` content into an
    edge-to-edge `.container`-based layout. The first pass of repurposing
    native "full" into a third, constrained 1440px tier (Traps #23 above)
    left these two blocks OUT of `supports.align`'s widened array specifically
    to avoid reawakening that old code path via the toolbar — which felt
    safe, but wasn't: WordPress' align system doesn't actually care whether a
    block "supports" full in this narrow sense the way that exclusion
    assumed once "full" is offered anywhere nearby, so the safer fix wasn't
    exclusion at all, it was going back and actually DELETING the legacy
    branch, then including the block normally like every other one. Once
    `$use_container` was gone, `"full"` on `hero`/`cta` behaves identically
    to `"wide"`/`""` — just a different native align class — with no
    special-casing left anywhere. The general lesson: an "exclude this block
    from the new behavior" workaround for legacy code is rarely the actual
    fix; find out what the legacy code was FOR (here: edge-to-edge before
    "full" meant something else) and confirm it's genuinely obsolete under
    the new meaning before deciding whether to keep, delete, or migrate it —
    don't let it linger by routing around it.
25. **Relabelling the native alignment toolbar's text took FOUR attempts, each
    ruling out a different, genuinely reasonable-looking mechanism — the
    working fix is `ownframework_relabel_align_controls()`
    (`inc/functions/enqueue-scripts-styles.php`), not a PHP filter and not a
    standalone script.** In order:
    1. **PHP `add_filter('gettext', ...)`** (`inc/functions/align-labels.php`,
       since deleted): matched the right English source strings (confirmed by
       reading the compiled JS directly) and still did nothing. The toolbar's
       labels come from `@wordpress/i18n`'s OWN browser-side translation
       state — a pipeline PHP's `gettext` filter never touches, since that
       filter only fires for strings PHP itself echoes.
    2. **`wp.i18n.setLocaleData({ key: [translation] }, 'default')`** in a
       standalone JS file: the correct JS-side API for overriding translation
       DATA, but it MERGES into `@wordpress/i18n`'s locale-data object rather
       than intercepting the translation CALL — only correct if it runs after
       Gutenberg's own data for that domain has already loaded, and a script
       dependency on `wp-i18n`/`wp-block-editor` doesn't actually prove that
       ordering.
    3. **`wp.hooks.addFilter('i18n.gettext'/'i18n.gettext_with_context', ...)`**
       in that same standalone file: the JS-side sibling of PHP's own
       `gettext` filter — `__()`/`_x()` really do pass their result through
       these hooks on every call (confirmed in `wp-includes/js/dist/i18n.js`'s
       `createI18n()`). Registered correctly (`wp.hooks.hasFilter(...)` →
       `true`) and STILL did nothing, for TWO independent reasons, both
       confirmed live in a browser console before being understood:
       - **`domain` arrives as `undefined`, never the string `"default"`, for
         the overwhelmingly common case.** `@wordpress/i18n`'s `__`/`_x`
         (`packages/i18n/build-module/create-i18n.mjs`) pass their OWN,
         unmodified `domain` parameter straight into
         `hooks.applyFilters(hookName, translation, text, domain)` — only
         their INTERNAL `dcnpgettext` wrapper (the translation LOOKUP, a
         separate step) defaults a missing domain to `"default"`; that
         default never back-propagates to what the filter receives. Gutenberg
         calls `__("Wide width")` with no second argument, so every one of
         its own filter invocations arrives with `domain === undefined`.
         Checking `domain === "default"` therefore filters out exactly the
         calls that need filtering — guard with `!domain || domain ===
"default"` instead. (Manually testing `applyFilters(..., "default")`
         with the domain supplied by hand isn't a faithful reproduction of the
         real call and can pass while the real thing still fails — exactly
         what happened here, and why this took a live console session to
         actually pin down rather than reasoning from source alone.)
       - **Even with that fixed, a standalone script loaded via
         `enqueue_block_editor_assets` is fundamentally too late** — see
         Traps #26.
    4. **The actual fix**: `wp_add_inline_script('wp-hooks', $js, 'after')`,
       containing the exact same two `wp.hooks.addFilter()` calls from
       attempt 3 (fixed `domain` check included) — see Traps #26 for why
       attaching to `wp-hooks` specifically, rather than enqueueing a separate
       file, is what actually makes this reliable.
26. **A `wp.hooks.addFilter()` call can be 100% correct — registered,
    verified with `hasFilter()`, verified with a direct `applyFilters()` call
    returning the right value — and STILL never affect the rendered UI, if
    the text it's trying to change was already baked into a MODULE-LEVEL
    CONSTANT before the filter ever ran.** `BLOCK_ALIGNMENTS_CONTROLS` in
    `wp-includes/js/dist/block-editor.js` is built exactly once — `title:
__("Wide width")` etc. run the moment that module first evaluates, not on
    every render. A filter registered after that point can never retroactively
    change an already-computed string; there's no "re-run `__()` on render"
    to intercept, because that call already happened, permanently, long
    before. Confirmed the hard way (again): pasted the dropdown's actual
    rendered HTML mid-session and it showed the untranslated Dutch labels
    despite every isolated console test on the filter itself passing —
    the filter logic was never the remaining problem, timing was.
    A script enqueued via `enqueue_block_editor_assets` can't reliably win
    this — declaring `wp-hooks` as ITS dependency only proves it runs after
    `wp-hooks`, not before `wp-block-editor` (two independent scripts with no
    ordering relationship forced between them), and loading it as `type=
"module"` (this theme's own `script_loader_tag` filter forces this for
    `theme-scripts`/`theme-scripts-editor`) makes it worse: module scripts are
    always deferred relative to classic scripts regardless of tag position,
    so it's structurally guaranteed to run late.
    The fix that's actually guaranteed, not just probably-early-enough:
    `wp_add_inline_script('wp-hooks', $js, 'after')` piggybacks on a
    dependency chain WordPress itself enforces — `wp-i18n` (and everything
    built on it, including `wp-block-editor`) declares `wp-hooks` as ITS OWN
    dependency, so WordPress guarantees `wp-hooks` (and anything inlined onto
    it) prints and runs before either ever can. No race, no probably — the
    same guarantee any of `wp-hooks`'s other dependents already rely on.
    `ownframework_relabel_align_controls()` (`inc/functions/
enqueue-scripts-styles.php`) is the concrete example — matches this
    theme's own established `wp_add_inline_script()` pattern
    (`ownframework_editor_prose_class()`, `functions.php`), just targeting an
    EXISTING core handle instead of a virtual carrier script registered with
    `false` as its source.
27. **`get_field()` called WITHOUT an explicit post ID, from PHP executing
    DURING a live SCF/ACF block render, resolves against the BLOCK's own
    pseudo post ID — never `get_the_ID()`/whatever post the block happens to
    be rendered for.** Hit twice building the vacancy feature set, both
    times silently: `blocks/vacatures/parts/card.php`'s "logo" field came
    back empty on the archive's initial page load (a live block render) but
    worked once the filter bar re-rendered cards via the REST endpoint (a
    plain PHP loop with no active block context) — same template function,
    two different results, no error either time. The same root cause hit
    `blocks/vacature-apply`'s `application_url`. Fix: always pass an
    explicit ID (`get_field('logo', get_the_ID())`) for any field that
    belongs to the POST being looped over, not to the block. The exact
    mirror case is a field that genuinely belongs to the BLOCK itself
    (location `"block =="`, e.g. `acf/vacatures-uitgelicht`'s
    `geselecteerde_vacatures` Relationship field) — there, calling
    `get_field()` WITHOUT a post ID is the correct call; passing one would
    be the bug. Know which one you're dealing with before touching either.
28. **Once a `templates/*.html` file is edited via the Site Editor, WordPress
    persists a full copy into the database (`wp_template` post) that
    overrides the theme file from then on.** Hit while building both
    `archive-vacancy.html` and `single-vacancy.html`: editing the theme file
    stopped reaching the live site because a DB-stored copy had already been
    created and now took precedence. No error, no admin notice pointing at
    it — the front end just quietly kept showing the old layout. There is no
    safe automated fix for this from the codebase side (it would mean a raw
    database write); resolve it by having the client re-save the template
    via the Site Editor, or reset it back to the theme file from the Site
    Editor's own template list ("Solve"/reset to default) rather than
    editing the `.html` file and expecting it to take effect.
29. **A CPT archive's MAIN query (the first, no-JS page load) runs, via
    `pre_get_posts`, before any block on the page has rendered — so it can
    never read a block's own attribute (e.g. "posts per page") directly, no
    matter how that attribute is stored.** `acf/vacatures`' own "Aantal"
    setting had zero effect on the very first page load for exactly this
    reason (client-side re-renders via the REST filter endpoint DID respect
    it, since those run after the block already exists). Fixed in
    `hooks/query.php` by parsing the ACTUALLY RESOLVED archive template
    inside that same `pre_get_posts` callback —
    `get_block_template(get_stylesheet() . '//archive-vacancy', 'wp_template')`
    (itself already DB-override-aware, see Traps #28) walked with a small
    recursive `parse_blocks()` helper to find the `acf/vacatures` block and
    read its `perPage` attribute — then calling
    `$query->set('posts_per_page', ...)` with that value before the query
    actually runs.
30. **Equal-height Swiper slides need `display:flex` on the SLIDE, not a
    percentage height on the card inside it** — a percentage height needs a
    DEFINITE ancestor height to resolve against, which a flex-stretched
    `.swiper-slide` doesn't reliably provide, so `h-full`/`height:100%` on
    the card was visibly broken (shorter cards not stretching) even though
    Swiper itself sets `height:100%` on every slide. The theme's own
    `.own-content-slider`/`.own-facts-slider` pattern
    (`src/css/components/swiper.css`) already had the reliable fix —
    `.swiper-slide { display:flex }` plus `.swiper-slide > * { width:100% }`
    — which makes the card a flex CHILD that stretches via
    `align-items:stretch` instead; `.own-vacatures-uitgelicht`'s own slider
    (`acf/vacatures-uitgelicht`) replicates the exact same two rules rather
    than reintroducing the percentage-height approach. **Watch for
    `h-auto!` reappearing on the slide wrapper** — Tailwind's `!important`
    forces `height:auto`, which silently cancels this fix outright (no
    visual error, the cards just quietly go back to their own natural
    height); it crept back into `render.php` more than once during this
    build and has to be removed each time — it is not an intentional
    override.
31. \*\*A `blockVersion:3` block's own native padding renders TWICE in the
    editor — once on SCF's outer `<div {...useBlockProps()} class=
"acf-block-body ...">` (see Traps #17's own docblock in `columns.css` for
    this exact wrapper), once again on render.php's own root element via its
    own `get_block_wrapper_attributes()` call — because `useBlockProps()`
    automatically re-applies a block's native `supports.spacing`/`color`
    styling to WHATEVER it decorates, confirmed directly in the actual
    rendered DOM: both elements carried the identical
    `padding-top/padding-bottom: var(--wp--preset--spacing--xl)`. The front
    end never has this outer wrapper at all, so only one copy of the padding
    ever exists there — a block whose vertical spacing comes from real
    padding (`acf/vacatures-uitgelicht`, or any block placed with the
    vacancy templates' own XL spacing) showed this as extra dead space
    ABOVE the block's own full-bleed background
    (`ownframework_full_bleed_class()`), which lives INSIDE render.php's own
    element and can never reach into the outer wrapper's duplicate. Fixed
    editor-only, in `src/css/components/editor-layout.css`
    (auto-scoped under `.editor-styles-wrapper` via `add_editor_style()`):
    `.acf-block-body { padding: 0 !important; }` — `.acf-block-body` only
    ever exists on that outer SCF wrapper, never on render.php's own root, so
    this can't accidentally zero out the real, single copy. Margin is
    deliberately NOT touched by the same rule — unlike padding, a stacked
    block's margin can legitimately come from Gutenberg's own block-gap
    spacing between blocks rather than this block's own attributes, and
    forcing it to 0 would silently remove that too; padding has no such
    competing native mechanism, so zeroing it outright is safe.
32. **`core/icon` renders NOTHING — not a placeholder, an empty string — when
    its `"icon"` attribute is unset**, confirmed directly in WordPress' own
    `render_block_core_icon()`: `if ( empty( $attributes['icon'] ) ) { return; }`.
    `blocks/voordeel`'s first version left `icon` out of its `InnerBlocks`
    template on purpose (reasoning: don't guess a name that might not exist),
    which silently produced an invisible icon on every freshly inserted
    `acf/voordeel` — not a broken/placeholder icon, no icon markup AT ALL. Two
    fixes, verified against the real, installed core (not guessed from docs):
    the value is `"<collection>/<icon-name>"` (`"core/star-filled"` here,
    checked against this site's own `wp-includes/assets/
    icon-library-manifest.php` for a name that actually exists in the "core"
    collection), and its VISIBLE SIZE is a separate, second gotcha —
    `style.dimensions.width` (nested, not a top-level `width` attribute,
    confirmed by reading `render_block_core_icon()`'s own dimensions-styles
    branch), because without it the SVG renders at its own tiny intrinsic
    size, easy to mistake for "not rendering" when it's actually rendering at
    a couple of pixels. A pre-filled `InnerBlocks` template needs BOTH set to
    actually show something the moment a block is inserted — background
    colour and border-radius alone (this block's other pre-filled defaults)
    render a badge shape around nothing.
33. **Two SCF options-page fields with the SAME bare name silently return
    EACH OTHER'S values, on the SAME request, no matter which field key you
    call `get_field()` with.** SCF's runtime "values" store caches a loaded
    value under `"{$post_id}:{$field['name']}"`
    (`acf_get_value()`, `secure-custom-fields/includes/acf-value-functions.php`)
    — a bare field NAME, not the field key, and not scoped per field group.
    Thema opties → Live search, Melding(en) and Formulieren each had their
    own "Kleuren" `group` field literally named `colors` — 3 different field
    KEYS, but the same NAME, all reading via the same generic `'option'`
    `$post_id`. On any front-end page load where more than one of them loads
    (the header's search modal + the footer's notifications both run on
    EVERY page), whichever loaded FIRST won the single `option:colors` cache
    slot for the rest of the request; the other 2 silently got its colours
    back instead of their own — "the last-saved page's colours win
    everywhere", confirmed live by loading `blocks/header/parts/
search-modal.php`'s colours before `notifications/includes/render.php`'s in
    one request and getting identical (wrong) output for both. Reproduce with
    2 back-to-back `get_field()` calls for 2 same-named fields sharing a
    `$post_id` — no admin UI needed. Passing a field KEY instead of the bare
    name to `get_field()` looks like a fix (it resolves a DIFFERENT, milder
    ambiguity — which field DEFINITION a bare name matches) but does NOT
    touch this cache, since the cache key is always the field's raw `name`
    regardless of which selector reached it — confirmed the hard way, seeing
    the bug reproduce again even after switching every call site to field
    keys. **The actual fix: give every options-page field a NAME that's
    unique across the whole site**, not just within its own field group —
    `colors` became `live_search_colors`/`notifications_colors`/
    `forminator_colors`. Renaming a field's `name` also reshapes its own
    stored data (a `group` field's sub-fields are stored under
    `"{$parent_name}_{$sub_field_name}"`, `class-acf-field-group.php`'s
    `prepare_field_for_db()`), so an existing site's saved values need a
    matching `wp_options` rename (`options_colors_X` →
    `options_live_search_colors_X`, and same for both the `_options_`-
    prefixed reference row and any DB-synced `acf-field` post's own
    `post_excerpt`) — not just an edit to the `acf-json` file — or the
    rename orphans already-chosen colours. When adding a new options page
    with its own colour/settings group, check its top-level field names
    against every OTHER options page's, not just its own field group.

34. **A Tailwind text size on an `<h1>`–`<h6>` does nothing without `!`.**
    theme.json's heading rules print as plain, unlayered `h2 {…}`, and
    unlayered CSS beats anything in `@layer utilities` regardless of
    specificity — so `text-lg` on an `<h2>` card title silently rendered at
    the H2 size. Use the `!` modifier (`text-…!`, `leading-…!`), same as the
    existing `m-0!`/`text-(--…-color)!`. A plain size class on a heading is
    dead code — the Ontwerp size wins — so leave it off. To give an element a
    heading level's Ontwerp size, use `--own-hN-font-size`/`-line-height`
    (see "Font sizes follow Ontwerp too" above).

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
