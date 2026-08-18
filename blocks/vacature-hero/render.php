<?php
defined('ABSPATH') || exit();

/**
 * Vacature header — titel, tekst en een zoekbalk op een effen kleur- of
 * verloopachtergrond (WordPress' eigen native "Kleuren"-paneel, block.json's
 * "supports.color"). Had eerder acf/hero's volledige media-mechaniek
 * (afbeelding/slideshow/video) erbij gekopieerd, bewust weer verwijderd: dit
 * block heeft altijd een zoekbalk als eigen invulling, en een foto/video
 * erachter voegde voor dit specifieke gebruik (vacature-archief) alleen
 * complexiteit toe zonder een concreet doel. Zie acf/hero zelf voor die
 * media-opties, mocht een latere versie ze hier alsnog terug willen.
 *
 * "contentAlign" — "center" (default) of "left" — bepaalt of titel, tekst en
 * de zoekbalk samen gecentreerd staan of links uitgelijnd. Default is nu
 * "center": zonder een afbeelding naast de inhoud (die niet meer bestaat)
 * oogt gecentreerde tekst boven een gecentreerde zoekbalk als een eenvoudige
 * "hero met zoekbalk" beter dan links uitgelijnd tegen een lege rest van de
 * sectie.
 *
 * De zoekbalk zelf is bewust in stijl gebracht met blocks/header/parts/
 * search-modal.php's eigen "own-search-modal__input" (de live search) — zelfde
 * icoonmaat (w-5 h-5, niet w-4 h-4) en tekstgrootte (text-base, niet
 * text-sm), plus een wis-knop die pas verschijnt zodra er iets getypt is,
 * met dezelfde currentColor-hover-tint (own-vacatures-icon-btn, src/css/
 * components/vacatures.css) als own-header-icon-btn. Twee blocks voor
 * dezelfde soort "zoeken"-interactie moeten er ook hetzelfde uitzien, ook al
 * lopen ze via losse Alpine-stores (searchDialog/liveSearch vs.
 * vacaturesFilters) en een andere backend (REST vs. eigen WP_Query).
 *
 * Achtergrond-/tekstkleur komen uit WordPress' NATIVE colour support (zelfde
 * mechanisme als acf/cta's section-achtergrond) — geen eigen kleurattributen/
 * -panelen meer nodig zoals de vorige (media-)versie had: met maar één te
 * kleuren zone is er niets meer dat het native "Kleuren"-paneel niet zelf al
 * afdekt (vergelijk met acf/vacatures' eigen "accentColor", dat wél een eigen
 * paneel nodig heeft omdat het meerdere SUB-elementen kleurt).
 *
 * ".prose-invert" wordt toegepast op basis van de GEKOZEN tekstkleur, niet
 * (zoals bij acf/hero) op basis van "staat er media achter" — dat bestaat
 * hier niet meer. @tailwindcss/typography's eigen kop/tekst-kleuren lopen via
 * losse CSS-variabelen (--tw-prose-headings etc., src/css/components/
 * typography.css), niet via gewone `color`-inheritance: een `color:wit` op de
 * wrapper hieronder bereikt die koppen dus NOOIT vanzelf, en zonder
 * ".prose-invert" blijven dit theme's eigen, op de merkkleuren afgestemde
 * ".prose"-tokens gewoon van kracht — ontworpen voor gewone, donkere
 * paginatekst, niet voor lichte tekst op een gekleurde hero-achtergrond.
 * ownframework_get_theme_json_colors() (inc/scf/custom-color-palette.php)
 * resolvet een gekozen paletslug naar zijn echte hex, zodat
 * ownframework_color_is_light() daarop kan rekenen — dezelfde twee helpers
 * als inc/scf/color-palette.php's eigen swatch-checkmark-logica.
 *
 * Alleen insertable binnen de vacature-archiefpagina (templates/
 * archive-vacancy.html) — hooks/restrict-to-archive-vacancy-template.php.
 */

$height = $block['height'] ?? 'auto';
$show_breadcrumbs = !empty($block['showBreadcrumbs']);
$content_align = ($block['contentAlign'] ?? 'center') === 'left' ? 'left' : 'center';

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

// "Zoekknop" (InspectorControls, editor.js) — plain block attributes + eigen
// CSS custom properties, exact dezelfde reden als acf/vacatures' eigen
// "accentColor": de knop is een sub-element dat het native "Kleuren"-paneel
// (hierboven, voor de sectie-achtergrond/tekst) nooit bereikt (CLAUDE.md
// Traps #7).
//
// "searchButtonRadius" is een object (topLeft/topRight/bottomRight/
// bottomLeft, elk in px), niet een keuzelijst — border-radius accepteert al
// een 4-waarden shorthand, dus deze losse getallen worden hier gewoon
// achter elkaar gezet i.p.v. naar een vaste rem-waarde per keuze te mappen.
// 100px op een knop die zelf nooit zo hoog wordt klemt vanzelf af tot een
// volledig ronde/pil-vorm (CSS' eigen gedrag voor een radius groter dan de
// helft van de eigen hoogte), dus dat blijft de standaardwaarde — zelfde
// resultaat als de oude vaste "9999px", nu gewoon het bovenste puntje van de
// schuifregelaar in plaats van een apart "Volledig rond"-keuze.
$search_button_color = $block['searchButtonColor'] ?? '';
$search_button_text_color = $block['searchButtonTextColor'] ?? '';

// is_array() guard: an older saved block could still carry the previous
// "none"/"small"/.../"full" string attribute (before this became an object)
// — falls back to the same "fully round" default rather than a PHP warning
// on a string array-offset access.
$radius_corners = is_array($block['searchButtonRadius'] ?? null) ? $block['searchButtonRadius'] : [];
$search_button_radius = implode(' ', array_map(
    fn(string $corner): string => max(0, (int) ($radius_corners[$corner] ?? 100)) . 'px',
    ['topLeft', 'topRight', 'bottomRight', 'bottomLeft']
));

$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
if ($search_button_color !== '') {
    $style_parts[] = '--own-vacatures-search-btn-color:' . esc_attr($search_button_color);
}
if ($search_button_text_color !== '') {
    $style_parts[] = '--own-vacatures-search-btn-text-color:' . esc_attr($search_button_text_color);
}
$style_parts[] = '--own-vacatures-search-btn-radius:' . esc_attr($search_button_radius);
$style_parts[] = 'box-sizing:border-box';

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';
$section_class = trim($align_class . ' relative own-vacature-hero');

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

$hero_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":1,"placeholder":"Kom bij ons werken"}],["core/paragraph",{"placeholder":"Beschrijving…"}]]\' allowedBlocks=\'["core/heading","core/paragraph"]\' templateLock=\'false\' />';

// ownframework_vacatures_parse_params() — blocks/vacatures/hooks/query.php,
// autoloaded regardless of which block's render.php calls it (functions.php's
// "blocks/*/hooks/*.php" glob). Used here only so the search input's initial
// value (a bookmarked/shared "?search=…" URL) is correct on first paint,
// before $store.vacaturesFilters (x-model) takes over.
$initial_search = function_exists('ownframework_vacatures_parse_params')
    ? ownframework_vacatures_parse_params($_GET)['search']
    : '';

ob_start();
?>
<form method="get" class="own-vacature-hero-search relative mt-6 sm:mt-8 <?= $content_align === 'center' ? 'mx-auto' : ''; ?>" x-data @submit.prevent="$store.vacaturesFilters.submitSearch()">
    <div class="relative">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 w-5 h-5">
            <?= ownframework_get_icon('search', 'w-5 h-5'); ?>
        </span>
        <input
            type="text"
            name="search"
            x-model="$store.vacaturesFilters.searchQuery"
            value="<?= esc_attr($initial_search); ?>"
            class="own-vacature-hero-search__input own-vacatures-surface w-full pl-12 pr-32 py-3 sm:py-3.5 rounded-xl text-base placeholder:text-gray-500 bg-white focus:ring-2"
            placeholder="<?php esc_attr_e('Zoek een vacature', 'own-framework'); ?>">
        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
            <button
                type="button"
                x-show="$store.vacaturesFilters.searchQuery.trim() !== ''"
                x-cloak
                @click="$store.vacaturesFilters.searchQuery = ''; $store.vacaturesFilters.submitSearch()"
                class="own-vacatures-icon-btn rounded-lg p-2 text-gray-500"
                aria-label="<?php esc_attr_e('Zoekterm wissen', 'own-framework'); ?>">
                <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
            </button>
            <button
                type="submit"
                class="own-vacature-hero-search-btn px-4 py-2 text-sm font-semibold cursor-pointer">
                <?php esc_html_e('Zoeken', 'own-framework'); ?>
            </button>
        </div>
    </div>
</form>
<?php
$search_form_html = ob_get_clean();

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

    <div class="<?= esc_attr(trim($height_class . ' own-vacature-hero-height')); ?>">
        <div class="relative z-10 max-w-2xl <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
            <?= $breadcrumbs; ?>
            <div class="prose <?= $use_prose_invert ? 'prose-invert' : ''; ?> max-w-none <?= $content_align === 'center' ? 'mx-auto' : ''; ?>">
                <?= $hero_inner_blocks; ?>
            </div>
            <?= $search_form_html; ?>
        </div>
    </div>
</section>