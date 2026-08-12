---
name: new-block
description: Scaffold or extend a custom block in the own-framework theme — block.json, render.php and the matching SCF field group. Use when asked to create, add, generate or extend a block or page section.
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
---

Block work for the own-framework theme. `CLAUDE.md` holds the rules that apply
everywhere; this skill is the block-specific procedure.

`blocks/hero` and `blocks/cta` are the best reference implementations —
both use SCF's `<InnerBlocks />`-string substitution trick (see either
block's own docblock for the full explanation), a card-style outer/inner
radius convention (`rounded-2xl` outer, `rounded-xl` inner), and a custom
`PanelColorSettings` panel for any colour that targets a boxed sub-element
rather than the block's root wrapper. `blocks/content-blok` is the reference
for a block meant to be nested inside columns/containers instead of being
its own section — no `supports.align`, and Tailwind v4 container queries
(`@container`/`@lg:`) instead of viewport breakpoints for any layout switch
that should react to the block's own rendered width, not the screen's.
Follow the structure below for a new block; reach for whichever of these
three matches its shape.

A custom `PanelColorSettings` panel isn't only for the CTA situation (a
colour targeting a sub-element the native picker can't reach) — it's also
worth adding whenever the *native* background/gradient controls would be
easy to miss. WordPress renders `supports.color` controls under a separate
"Styles" (paintbrush) tab in the sidebar, not merged into the "Block" (gear)
tab where a block's own custom panels live; `blocks/content-blok`'s root is
its only boxed element, yet it still has its own "Kleur" panel purely so the
background colour sits next to the block's other settings instead of hiding
in the other tab. When you add one for this reason, turn the matching native
`supports.color` key off in `block.json` (see `content-blok`'s
`"background": false, "gradients": false`) — two controls writing to the
same visual background is confusing, not convenient.

---

# A. Creating a new block

Slug rules: lowercase, hyphens. **Pick carefully — the slug becomes
`acf/<slug>`, which is written into `post_content` and can never be renamed
afterwards without breaking every existing instance.**

## 1. `blocks/<slug>/block.json`

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "acf/<slug>",
  "title": "<Titel>",
  "description": "<Korte beschrijving.>",
  "category": "own-framework",
  "textdomain": "own-framework",
  "icon": "<dashicon>",
  "keywords": ["..."],
  "supports": { "align": false, "spacing": { "margin": true } },
  "acf": {
    "blockVersion": 3,
    "renderTemplate": "render.php",
    "hideFieldsInSidebar": true,
    "expandedEditorButtonText": "Blok bewerken"
  }
}
```

- No `"render"` key — SCF resolves `renderTemplate` relative to the block folder.
- No registration code — `functions.php` globs `blocks/*/block.json`.
- `"category": "own-framework"` — the theme's own block category, already
  registered in `functions.php`.
- `"textdomain": "own-framework"` — matches `load_theme_textdomain()` in
  `functions.php`. Function/variable names still use `ownframework_` (no
  hyphen — PHP identifiers can't contain one); only the text-domain string
  and slugs use the hyphenated form.
- `spacing.margin: true` is enough; `functions.php` narrows every block's margin
  support to top/bottom globally, so never spell out sides.
- Add `"multiple": false` for a block that may appear once per page.
- Nesting: use `"parent"` for a direct-child-only block, `"ancestor"` for a
  descendant.

## 2. `blocks/<slug>/render.php`

Fields **and** markup in one file. Never create a matching
`template-parts/components/<slug>.php` — that indirection was deliberately
removed from this theme.

```php
<?php

/**
 * <Wat dit blok rendert, in het Nederlands. Eén of twee regels.>
 */
$title    = get_field('<slug>_title') ?? '';
$content  = get_field('<slug>_content') ?? '';
$bg_color = get_field('<slug>_background_color') ?: 'white';
?>
<div <?= get_block_wrapper_attributes(['class' => 'relative bg-' . esc_attr($bg_color)]) ?>>
    <?php if ($title) : ?>
        <h2 class="mt-0!"><?= esc_html($title) ?></h2>
    <?php endif; ?>
    <?php if ($content) : ?>
        <div class="prose max-w-none"><?= $content ?></div>
    <?php endif; ?>
</div>
```

Requirements:

- `get_field()`, never `get_sub_field()` — there is no flexible content field.
- `get_block_wrapper_attributes()` on the root element, or editor
  alignment/spacing/position controls silently do nothing.
- `?? ''` on any field fed to `esc_html()`/`esc_attr()` — `get_field()` returns
  null when unset, and passing null to those is deprecated in PHP 8.1+.
- WYSIWYG output goes in `<div class="prose max-w-none">` (`prose-invert` on a
  dark background).
- Icons: `ownframework_get_icon('<name>', 'size-5')` (`inc/functions/icons.php`).
  **Confirm `media/icons/<name>.svg` exists** — a missing file returns `''`
  silently.
- There is no `bg_class()`/`pattern_color_class()` helper anymore — a previous
  iteration of this theme had one and it's gone. If several blocks end up
  repeating the same colour-to-class logic (e.g. the `transparent` →
  bordered-box special case above), add a small helper to
  `inc/functions/` yourself once that pattern actually repeats — don't
  hand-roll it per block, and don't assume a helper already exists.
- Sub-templates used only by this block → `blocks/<slug>/parts/`.
  Filters, REST routes, editor config → `blocks/<slug>/hooks/` (autoloaded;
  `render.php` and `parts/` are not).

**Alpine.js trap.** Block output passes through `the_content`'s
`wpautop()`/`wptexturize()`, which splice stray `<br>` into multi-line attribute
values and mangle `=>`. Keep every Alpine attribute single-line and arrow-free,
and wrap it in `esc_attr()`:

```php
<div x-data="<?= esc_attr('{ open: false, items: ' . (int) $count . ' }') ?>">
```

**Tailwind trap.** A class name built dynamically (`'bg-' . $token`) is invisible
to Tailwind's scanner. Existing colour families are already safelisted via
`@source inline(...)` in `src/css/app.css`; a *new* family must be added there or
its utilities get tree-shaken out of the build.

**Colour-support trap.** `get_block_wrapper_attributes()` auto-injects native
`supports.color` styling onto whatever element it decorates — the root
wrapper, always. If the block needs a *different*, boxed sub-element to carry
its own colour (a card inset inside the block, not the full-width section),
`supports.color` can't reach it. Give that sub-element a plain string
attribute instead, plus a custom `PanelColorSettings` panel in `editor.js`
(see `blocks/cta`'s `cardBackgroundColor` + "Kleur kaart" panel). Keep
`supports.color` on the root for the cases where it's actually wanted — the
two are independent, non-conflicting layers once you stop expecting one to
do both jobs.

**Editor-preview JS trap.** A block whose front-end script must also run
inside the block editor's own preview (Swiper sliders, anything using
`initOnAppearance()` from `src/js/packages/init-on-appearance.js`) needs that
script imported by `src/js/editor-scripts.js`, loaded via
`enqueue_block_editor_assets` — the editor iframe never sees `main.js`'s own
`wp_enqueue_scripts` load. Don't just point the editor hook at `main.js`
itself: it also runs `Alpine.start()` plus `header.js`/`live-search.js`,
which misbehave against the editor's reduced single-block DOM (this is how
the `MediaUpload` "choose image" button broke once already). Add the new
script to `editor-scripts.js`'s existing imports, and `pnpm build` after —
`vite.config.ts` shares a chunk between the two entries, so a genuinely new
shared dependency may need its own look at `chunkFileNames` there.

**Site-wide radius/spacing.** Don't hardcode a specific radius value that
should scale with the site (`rounded-2xl` for a card-style outer element,
`rounded-xl` for inner media, matching `blocks/cta`/`blocks/hero`) — those
values already respond to the "Stijlen" options page's Radius dial via CSS
variables (`inc/functions/radius-scale.php`), no extra work needed per block.
Padding/margin already respond the same way to the Spacing dial via
theme.json (`inc/functions/spacing-scale.php`) as long as they're set through
the block's native spacing support, not a hand-rolled Tailwind `p-*`/`m-*`
class.

## 3. `acf-json/group_block_<slug_with_underscores>.json`

Keys are human-readable here, not hex — no collision check needed:

- filename + `"key"`: `group_block_<slug_with_underscores>`
- `"title"`: `"Blok: <Naam>"`
- `"location"`: `[[{"param":"block","operator":"==","value":"acf/<slug>"}]]`
- field `"key"`: `field_<slug_with_underscores>_<fieldname>`
- field `"name"`: `<slug_with_underscores>_<fieldname>` — this is the string
  `get_field()` reads and it becomes a `postmeta` key, so get it right first time
- all `"label"` values in Dutch

Colour fields use `"type": "theme_color_selector"` — this theme's own field type
(`inc/scf/color-selector.php`). It builds its swatch list from `theme.json`'s
`settings.color.palette` at runtime, so it needs no `choices` array. Do not
hand-roll a `button_group` of colours. Without an explicit `"allowed_colors"`
field setting it shows every standard (white/black/transparent) and theme
colour that currently exists in `theme.json` — narrow it per-field only when
a specific block genuinely needs fewer options.

---

# B. Extending an existing block

- **Adding a field**: add it to the existing group JSON with a new
  `field_<slug>_<name>` key, then read it in `render.php`. Never renumber or
  rename existing keys.
- **Renaming a field**: don't. The `name` is a live `postmeta` key — renaming
  orphans every saved value. Add a new field and migrate deliberately if it
  really matters.
- **Renaming the block or its folder**: don't. See `CLAUDE.md`.
- **Markup now needed by a second block or template**: *now* extract it to
  `template-parts/components/`, give it an `$args` contract docblock, and update
  both callers.

---

# C. Verify before finishing

```sh
php -l blocks/<slug>/render.php
php -r 'json_decode(file_get_contents("blocks/<slug>/block.json"), true) ?: exit(1);'
php -r 'json_decode(file_get_contents("acf-json/group_block_<slug>.json"), true) ?: exit(1);'
pnpm build
```

Then cross-check that every `get_field('...')` string in `render.php` matches a
`"name"` in the field group exactly — a typo there fails silently as an empty
block, which is the most common way this goes wrong:

```sh
grep -oE "get_field\('[^']+'" blocks/<slug>/render.php | sed "s/get_field('//;s/'//"
grep -oE '"name": "[^"]+"' acf-json/group_block_<slug>.json
```

Finally, load the block in the editor and on the front end. Field-name and
Alpine problems both look fine in PHP and only show up when rendered.
