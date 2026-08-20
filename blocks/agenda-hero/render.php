<?php
defined('ABSPATH') || exit();

/**
 * Agenda header — titel en tekst op een effen kleur- of verloopachtergrond
 * (WordPress' eigen native "Kleuren"-paneel, block.json's "supports.color").
 * Losgetrokken van acf/agenda zelf (voorheen een kaal heading/paragraph-
 * groepje rechtstreeks in templates/archive-agenda.html) zodat de intro-
 * tekst en de resultatengrid onafhankelijk van elkaar te beheren/verplaatsen/
 * stylen zijn — zelfde reden als waarom acf/vacature-hero een apart block is
 * naast acf/vacatures. Geen zoekbalk (in tegenstelling tot acf/vacature-hero)
 * — een schoolagenda heeft geen zoekbehoefte die dat rechtvaardigt; filteren
 * (categorie + periode) gebeurt via de filterbalk hieronder, zie die eigen
 * docblock verderop.
 *
 * Met maar één te kleuren zone (de hele sectie) is er niets dat het native
 * "Kleuren"-paneel niet zelf al afdekt — geen eigen kleurattributen/-panelen
 * nodig zoals acf/agenda zelf die wel heeft (sub-elementen: badges,
 * checkboxes, kaarten). backgroundColor/textColor's eigen DEFAULTS
 * ("primary"/"base") geven een vers ingevoegd block meteen kleurcontrast,
 * i.p.v. de kale wit-op-wit van een default-loos block — pas achteraf zelf
 * aan te passen via Ontwerp, maar nooit een lege start.
 *
 * ".prose-invert" wordt toegepast op basis van de GEKOZEN tekstkleur (zelfde
 * ownframework_get_theme_json_colors()/ownframework_color_is_light()-paar
 * als acf/vacature-hero) — @tailwindcss/typography's eigen kop/tekst-kleuren
 * lopen via losse CSS-variabelen, niet via gewone `color`-inheritance, dus
 * zonder dit zou witte tekst op een donkere achtergrond alsnog donkere
 * koppen tonen.
 *
 * ".own-agenda-hero-intro" (agenda.css) zet de laatste regel binnen deze
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
 * Alleen insertable binnen de agenda-archiefpagina (templates/
 * archive-agenda.html) — blocks/agenda-hero/hooks/
 * restrict-to-archive-agenda-template.php restrict ook acf/agenda zelf.
 *
 * Draagt sinds kort ook de filterbalk (parts/filter-bar.php — Periode +
 * taxonomie-dropdowns), verplaatst uit acf/agenda's eigen sidebar: de
 * resultatengrid gebruikt nu de volle breedte, met alleen de weergave-
 * toggle en sortering nog naast de resultaten. Alle bindingen lezen/
 * schrijven nog steeds de globale $store.agendaFilters (src/js/scripts/
 * agenda-filter.js) — DOM-positie maakt daar niets voor uit.
 * ownframework_agenda_get_filter_data()/_get_periode_options()/
 * _parse_params() komen uit blocks/agenda/hooks/query.php, altijd geladen
 * via de theme-brede hooks-autoload glob (elk block's eigen "hooks"-map),
 * dus cross-block aanroepen hier is geen extra afhankelijkheid.
 *
 * De balk zit BEWUST BUITEN de "<section>" hierboven, als een eigen, volle-
 * breedte div erna — niet binnen de sectie zelf: die sectie's eigen
 * gekleurde achtergrond (de "$bleed_class"-laag hieronder) is "inset-y-0"
 * op de sectie ZELF, dus rekt zich uit tot precies de sectie's eigen hoogte,
 * inclusief alles wat erin staat. Een balk binnen die sectie zou dus altijd
 * over de gekozen achtergrondkleur heen liggen, ongeacht welke kleur een
 * editor kiest — een puur witte/neutrale balk die zichtbaar bij de hero
 * hoort, maar niet IN de kleur zit, kan daarom alleen als eigen element na
 * de sectie, niet als een kind ervan. "data-own-agenda-filters" (de JSON-
 * config die $store.agendaFilters bij het laden inleest) staat om diezelfde
 * reden ook hier, niet meer op de sectie zelf.
 */

$height = $block['height'] ?? 'auto';
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$content_align = ($block['contentAlign'] ?? 'center') === 'left' ? 'left' : 'center';
$accent = $block['accentColor'] ?? '';

$filter_data = ownframework_agenda_get_filter_data();
$periode_options = ownframework_agenda_get_periode_options();
$month_options = ownframework_agenda_get_month_options();
$parsed_params = ownframework_agenda_parse_params($_GET);

$filters_config = [
    'taxonomies' => $filter_data,
    'activeTerms' => $parsed_params['tax_terms'],
    'periodeOptions' => $periode_options,
    'periode' => $parsed_params['periode'],
    'monthOptions' => $month_options,
    'month' => $parsed_params['month'],
    'search' => $parsed_params['search'],
    // acf/agenda's own "Momenten per pagina" attribute — this block has no
    // direct reference to that sibling block instance, so it's resolved the
    // same way hooks/query.php's own main-query fix does (Traps #29):
    // re-parsing the resolved archive template for acf/agenda's own
    // "perPage" value, rather than assuming a fixed default here too.
    'perPage' => ownframework_agenda_get_configured_per_page(),
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
    $style_parts[] = '--own-agenda-accent:' . esc_attr($accent);
}
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative own-agenda-hero');

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

$breadcrumbs = $show_breadcrumbs ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview)) : '';

$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Agenda"}],["core/paragraph",{"placeholder":"Een overzicht van belangrijke momenten door het jaar heen."}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' />';

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

    <div class="<?= esc_attr(trim($height_class . ' own-agenda-hero-height')); ?>">
        <div class="relative z-10 max-w-2xl <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
            <?= $breadcrumbs; ?>
            <div class="prose <?= $use_prose_invert ? 'prose-invert' : ''; ?> max-w-none own-agenda-hero-intro <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                <?= $hero_inner_blocks; ?>
            </div>
        </div>
    </div>
</section>

<?php // Volle-breedte balk, buiten de sectie hierboven — zie de docblok
// bovenaan dit bestand voor waarom. Zelfde "align"-klasse als de sectie
// zelf, zodat 'm exact even breed is als de hero/resultatengrid eronder,
// ondanks dat het een los element is. 
?>
<div class="<?= esc_attr(trim($align_class . ' own-agenda-hero-toolbar')); ?> mt-0!" style="<?= $accent !== '' ? esc_attr('--own-agenda-accent:' . $accent) : ''; ?>" data-own-agenda-filters="<?= esc_attr(wp_json_encode($filters_config)); ?>">
    <?php get_template_part('blocks/agenda-hero/parts/filter-bar', null, [
        'filter_data' => $filter_data,
        'periode_options' => $periode_options,
        'month_options' => $month_options,
    ]); ?>
</div>