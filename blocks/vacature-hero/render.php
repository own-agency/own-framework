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

$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
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
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4">
            <?= ownframework_get_icon('search', 'w-4 h-4'); ?>
        </span>
        <input
            type="text"
            name="search"
            x-model="$store.vacaturesFilters.searchQuery"
            value="<?= esc_attr($initial_search); ?>"
            class="own-vacature-hero-search__input w-full pl-10 pr-4 py-3 sm:py-3.5 rounded-xl border-0 ring-1 ring-gray-200 text-sm placeholder:text-gray-400 bg-white focus:ring-2"
            placeholder="<?php esc_attr_e('Zoek een vacature', 'own-framework'); ?>">
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