<?php
defined('ABSPATH') || exit();

/**
 * Nieuws header — titel en tekst op een effen kleur- of verloopachtergrond
 * (WordPress' eigen native "Kleuren"-paneel, block.json's "supports.color"),
 * structureel een kopie van acf/agenda-hero. Geen zoekbalk (in tegenstelling
 * tot acf/vacature-hero) — filteren gebeurt alleen op categorie, via de
 * filterbalk hieronder.
 *
 * Met maar één te kleuren zone (de hele sectie) is er niets dat het native
 * "Kleuren"-paneel niet zelf al afdekt. backgroundColor/textColor's eigen
 * DEFAULTS ("primary"/"base") geven een vers ingevoegd block meteen
 * kleurcontrast, i.p.v. de kale wit-op-wit van een default-loos block.
 *
 * ".prose-invert" wordt toegepast op basis van de GEKOZEN tekstkleur (zelfde
 * ownframework_get_theme_json_colors()/ownframework_color_is_light()-paar
 * als acf/agenda-hero/acf/vacature-hero).
 *
 * ".own-news-hero-intro" (news.css) zet de laatste regel binnen deze
 * ".prose"-wrapper op "margin-bottom: 0" — Tailwind Typography geeft die
 * standaard ook een margin-bottom, wat hier, met niets anders meer onder de
 * intro in deze sectie, als een onbedoelde lege ruimte tussen tekst en
 * sectierand oogde i.p.v. een bewuste marge. Een gewone, benoemde class i.p.v.
 * een Tailwind arbitrary-variant ("[&>*:last-child]:mb-0"): die laatste bleek
 * op de live site samen te vallen met een breed layout-probleem elders op de
 * pagina (de archiefpagina's filterbalk/resultaten kwamen edge-to-edge te
 * staan) — geen aangetoond causaal verband gevonden, maar deze eenvoudiger,
 * conventionelere vorm sluit een eventuele wisselwerking met build-/CSS-
 * optimalisatietools uit zonder de fix zelf op te geven.
 *
 * Alleen insertable binnen de nieuwsarchiefpagina (templates/home.html) —
 * blocks/news-hero/hooks/restrict-to-home-template.php restrict ook acf/news
 * zelf.
 *
 * Draagt ook de filterbalk (parts/filter-bar.php — één categorie-dropdown),
 * zelfde reden/opzet als acf/agenda-hero's eigen filterbalk: de resultatengrid
 * (acf/news) gebruikt de volle breedte, met alleen de weergave-toggle en
 * sortering nog naast de resultaten. Alle bindingen lezen/schrijven de
 * globale $store.newsFilters (src/js/scripts/news-filter.js) — DOM-positie
 * maakt daar niets voor uit. ownframework_news_get_filter_data()/
 * _parse_params() komen uit blocks/news/hooks/query.php, altijd geladen via
 * de theme-brede hooks-autoload glob.
 *
 * De balk zit BEWUST BUITEN de "<section>" hierboven, als een eigen, volle-
 * breedte div erna — zelfde "inset-y-0 rekt zich uit tot de sectie's eigen
 * hoogte"-reden als acf/agenda-hero's eigen docblock (blocks/agenda-hero/
 * render.php) beschrijft.
 */

$height = $block['height'] ?? 'auto';
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$content_align = ($block['contentAlign'] ?? 'center') === 'left' ? 'left' : 'center';
$accent = $block['accentColor'] ?? '';

$filter_data = ownframework_news_get_filter_data();
$parsed_params = ownframework_news_parse_params($_GET);

$filters_config = [
    'taxonomies' => $filter_data,
    'activeTerms' => $parsed_params['tax_terms'],
    // acf/news' own "Berichten per pagina" attribute — this block has no
    // direct reference to that sibling block instance, so it's resolved the
    // same way hooks/query.php's own main-query fix does (Traps #29):
    // re-parsing the resolved "home" template for acf/news' own "perPage"
    // value, rather than assuming a fixed default here too.
    'perPage' => ownframework_news_get_configured_per_page(),
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
    $style_parts[] = '--own-news-accent:' . esc_attr($accent);
}
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative own-news-hero');

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

$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Nieuws"}],["core/paragraph",{"placeholder":"Het laatste nieuws en de laatste updates."}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' />';

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

    <div class="<?= esc_attr(trim($height_class . ' own-news-hero-height')); ?>">
        <div class="relative z-10 w-full">
            <?= $breadcrumbs; ?>
            <div class="max-w-2xl <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                <div class="prose <?= $use_prose_invert ? 'prose-invert' : ''; ?> max-w-none own-news-hero-intro <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
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
<div class="<?= esc_attr(trim($align_class . ' own-news-hero-toolbar')); ?>" style="<?= $accent !== '' ? esc_attr('--own-news-accent:' . $accent) : ''; ?>" data-own-news-filters="<?= esc_attr(wp_json_encode($filters_config)); ?>">
    <?php get_template_part('blocks/news-hero/parts/filter-bar', null, [
        'filter_data' => $filter_data,
    ]); ?>
</div>
