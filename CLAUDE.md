# own-framework

Full block theme (`templates/index.html` exists, so `wp_is_block_theme()` is
`true`). Tailwind CSS v4 + Vite, Alpine.js, Secure Custom Fields. UI strings
are Dutch. Full layout reference: `STRUCTURE.md`.

This is a stripped starter: no custom blocks exist yet, `template-parts/`,
`patterns/` and `parts/` are currently empty, and Header/Footer are blank
Site Editor template parts waiting to be designed. Everything below is the
convention to follow once you start adding things, not a description of
content that currently exists.

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

A custom block category is already registered — use `"category":
"own-framework"` in `block.json`. Custom WordPress-bundled patterns are
disabled (`inc/functions/block-patterns.php`), so the inserter's Patterns tab
only ever shows this theme's own.

Currently available helper: `ownframework_get_icon($name, $class)` (reads
`media/icons/<name>.svg`) — see `inc/functions/icons.php`. There is no
`bg_class`/`pattern_color_class`/`split_content_blocks`/`research_query`
helper anymore; those existed in a previous iteration of this theme and were
removed along with the content that used them. Add equivalents to
`inc/functions/` yourself if a new block needs that kind of shared logic —
don't assume they still exist.

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
