<?php
defined('ABSPATH') || exit();

/**
 * Verhalen header — titel en tekst op een effen kleur- of verloopachtergrond
 * (WordPress' eigen native "Kleuren"-paneel, block.json's "supports.color").
 * Structureel een kopie van acf/agenda-hero, hier zonder diens filterbalk —
 * acf/verhalen filtert niet (geen taxonomie-dropdowns/periode/sortering,
 * alleen paginering, zie dat block's eigen docblock), dus is er geen tweede
 * volle-breedte balk nodig na de sectie.
 *
 * Met maar één te kleuren zone (de hele sectie) is er niets dat het native
 * "Kleuren"-paneel niet zelf al afdekt — geen eigen kleurattributen/-panelen
 * nodig. backgroundColor/textColor's eigen DEFAULTS ("primary"/"base") geven
 * een vers ingevoegd block meteen kleurcontrast i.p.v. een kale wit-op-wit
 * start.
 *
 * ".prose-invert" wordt toegepast op basis van de GEKOZEN tekstkleur
 * (ownframework_get_theme_json_colors()/ownframework_color_is_light(),
 * zelfde paar als acf/agenda-hero/acf/vacature-hero) — Tailwind Typography's
 * eigen kop/tekst-kleuren lopen via losse CSS-variabelen, niet via gewone
 * `color`-inheritance.
 *
 * Draagt sinds kort ook de filterbalk (parts/filter-bar.php — één
 * categorie-dropdown), zelfde reden/opzet als acf/news-hero's eigen
 * filterbalk: de resultatengrid (acf/verhalen) gebruikt de volle breedte,
 * met alleen de sortering nog naast de resultaten. Alle bindingen lezen/
 * schrijven de globale $store.verhalenFilters (src/js/scripts/
 * verhalen-filter.js) — DOM-positie maakt daar niets voor uit.
 * ownframework_verhalen_get_filter_data()/_parse_params() komen uit
 * blocks/verhalen/hooks/query.php, altijd geladen via de theme-brede
 * hooks-autoload glob (elk block's eigen "hooks"-map), dus cross-block
 * aanroepen hier is geen extra afhankelijkheid.
 *
 * De balk zit BEWUST BUITEN de "<section>" hierboven, als een eigen, volle-
 * breedte div erna — zelfde "inset-y-0 rekt zich uit tot de sectie's eigen
 * hoogte"-reden als acf/news-hero's/acf/agenda-hero's eigen docblock
 * beschrijft.
 *
 * Alleen insertable binnen de verhalen-archiefpagina (templates/
 * archive-verhaal.html) — hooks/restrict-to-archive-verhaal-template.php
 * restrict ook acf/verhalen zelf.
 */

$height = $block['height'] ?? 'auto';
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$content_align = ($block['contentAlign'] ?? 'center') === 'left' ? 'left' : 'center';
$accent = $block['accentColor'] ?? '';

$filter_data = ownframework_verhalen_get_filter_data();
$parsed_params = ownframework_verhalen_parse_params($_GET);

$filters_config = [
    'taxonomies' => $filter_data,
    'activeTerms' => $parsed_params['tax_terms'],
    // acf/verhalen's own "Aantal per pagina" attribute — this block has no
    // direct reference to that sibling block instance, so it's resolved the
    // same way hooks/query.php's own main-query fix does (Traps #29):
    // re-parsing the resolved archive-verhaal template for acf/verhalen's
    // own "perPage" value, rather than assuming a fixed default here too.
    'perPage' => ownframework_verhalen_get_configured_per_page(),
];

if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

if (!empty($block['style']['color']['gradient'])) {
    $gradient_value = $block['style']['color']['gradient'];
} elseif (!empty($block['gradient'])) {
    $gradient_value = 'var(--wp--preset--gradient--' . $block['gradient'] . ')';
} else {
    $gradient_value = '';
}

$text_color_hex = '';
if (!empty($block['style']['color']['text'])) {
    $text_color_hex = $block['style']['color']['text'];
    $text_value = $text_color_hex;
} elseif (!empty($block['textColor'])) {
    $palette = ownframework_get_theme_json_colors();
    $text_color_hex = $palette[$block['textColor']]['color'] ?? '';
    $text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
    $text_value = '';
}

$use_prose_invert = $text_color_hex !== '' && ownframework_color_is_light($text_color_hex);

$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
if ($accent !== '') {
    $style_parts[] = '--own-verhalen-accent:' . esc_attr($accent);
}
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative own-verhalen-hero');

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $section_class,
    'style' => implode(';', $style_parts),
]);

$bleed_class = ownframework_full_bleed_class();

$flat_bg_style_parts = [];
if ($bg_value !== '') {
    $flat_bg_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
if ($gradient_value !== '') {
    $flat_bg_style_parts[] = 'background-image:' . esc_attr($gradient_value);
}
if (!empty($flat_bg_style_parts)) {
    $flat_bg_style_parts[] = 'box-sizing:border-box';
}

// Buiten de max-w-2xl-kolom van de kop/tekst: een lang pad gebruikt zo de
// volle blockbreedte voordat het wrapt.
$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm' . ($content_align === 'center' ? ' [&>ol]:justify-center' : ''), !empty($is_preview))
    : '';

$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Verhalen"}],["core/paragraph",{"placeholder":"Lees de verhalen achter ons werk."}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' />';

$height_class = match ($height) {
    'medium' => 'min-h-[60vh] flex items-center py-12 sm:py-16 lg:py-20',
    'large' => 'min-h-[85vh] flex items-center py-12 sm:py-16 lg:py-20',
    'screen' => 'min-h-screen flex items-center',
    default => 'py-12 sm:py-16 lg:py-20',
};
?>
<section <?= $wrapper_attributes; ?>>
    <?php if (!empty($flat_bg_style_parts)) : ?>
        <div class="<?= $bleed_class; ?>" style="<?= esc_attr(implode(';', $flat_bg_style_parts)); ?>"></div>
    <?php endif; ?>

    <div class="<?= esc_attr(trim($height_class . ' own-verhalen-hero-height')); ?>">
        <div class="relative z-10 w-full">
            <?= $breadcrumbs; ?>
            <div class="max-w-2xl <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                <div class="prose <?= $use_prose_invert ? 'prose-invert' : ''; ?> max-w-none own-verhalen-hero-intro <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                    <?= $hero_inner_blocks; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php // Volle-breedte balk, buiten de sectie hierboven — zie de docblok
// bovenaan dit bestand voor waarom. Zelfde "align"-klasse als de sectie
// zelf, zodat 'm exact even breed is als de hero/resultatengrid eronder.
?>
<div class="<?= esc_attr(trim($align_class . ' own-verhalen-hero-toolbar')); ?>" style="<?= $accent !== '' ? esc_attr('--own-verhalen-accent:' . $accent) : ''; ?>" data-own-verhalen-filters="<?= esc_attr(wp_json_encode($filters_config)); ?>">
    <?php get_template_part('blocks/verhaal-hero/parts/filter-bar', null, [
        'filter_data' => $filter_data,
    ]); ?>
</div>
