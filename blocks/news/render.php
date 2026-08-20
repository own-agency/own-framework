<?php
defined('ABSPATH') || exit();

/**
 * Nieuws resultaten — volle breedte (geen sidebar: de categorie-dropdown zit
 * op acf/news-hero's eigen filterbalk, zie dat block's render.php/parts/
 * filter-bar.php). Zelfde opzet als blocks/agenda/render.php, met twee
 * inhoudelijke verschillen: geen "Periode"-achtige tweede facet (nieuws heeft
 * geen kalenderconcept) en de standaard-sortering is "nieuwste eerst"
 * (net als acf/vacatures) in plaats van agenda's chronologisch-oplopende
 * standaard — dit is een nieuwsfeed, geen kalender van aankomende momenten.
 *
 * Filter/sorteer-state leeft in de globale $store.newsFilters (src/js/
 * scripts/news-filter.js) — een globale store (niet lokale x-data), zodat
 * acf/news-hero's filterbalk en dit block's eigen sorteerdropdown/
 * resultatengrid dezelfde state delen ondanks dat het twee losse blocks
 * zijn. "data-own-news-filters" (de JSON-config die die store bij het laden
 * inleest) staat daarom ook niet hier, maar op acf/news-hero's eigen
 * wrapper — moet precies één keer op de pagina bestaan.
 *
 * In tegenstelling tot acf/agenda/acf/vacatures heeft dit block geen
 * lijst/raster-toggle — de resultaten staan altijd in een vast raster, geen
 * losse view-state om bij te houden.
 *
 * Eigen kleur-attributes ("accentColor" en de kaart's eigen
 * "cardTitleColor"/"cardTextColor"/"cardBackgroundColor"/"cardLinkColor")
 * i.p.v. het native "Kleuren"-paneel — badges, checkboxes, paginering én de
 * berichtkaarten (parts/card.php) zijn allemaal sub-elementen die dat paneel
 * nooit bereikt (CLAUDE.md Traps #7, zelfde redenering als acf/vacatures).
 *
 * Alleen insertable binnen de nieuwsarchiefpagina (templates/home.html) —
 * blocks/news-hero/hooks/restrict-to-home-template.php restrict ook dit
 * block.
 */

$accent = $block['accentColor'] ?? '';
$per_page = max(1, (int) ($block['perPage'] ?? 10));

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$card_title_color = $block['cardTitleColor'] ?? '';
$card_text_color = $block['cardTextColor'] ?? '';
$card_bg_color = $block['cardBackgroundColor'] ?? '';
$card_link_color = $block['cardLinkColor'] ?? '';

$style_parts = ['box-sizing:border-box'];
if ($accent !== '') {
    $style_parts[] = '--own-news-accent:' . esc_attr($accent);
}
if ($card_title_color !== '') {
    $style_parts[] = '--own-news-card-title-color:' . esc_attr($card_title_color);
}
if ($card_text_color !== '') {
    $style_parts[] = '--own-news-card-text-color:' . esc_attr($card_text_color);
}
if ($card_bg_color !== '') {
    $style_parts[] = '--own-news-card-bg-color:' . esc_attr($card_bg_color);
}
if ($card_link_color !== '') {
    $style_parts[] = '--own-news-card-link-color:' . esc_attr($card_link_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' own-news'),
    'style' => implode(';', $style_parts),
]);

global $wp_query;
$uses_main_query = empty($is_preview) && is_home() && $wp_query->is_main_query();

if ($uses_main_query) {
    $query = $wp_query;
    $current_page = max(1, (int) get_query_var('paged'));
} else {
    $current_page = 1;
    $query = ownframework_news_new_query($_GET, $current_page, $per_page);
}

$found_posts = $query->found_posts;

$sort_options = [
    'date_desc' => __('Datum (nieuwste eerst)', 'own-framework'),
    'date_asc' => __('Datum (oudste eerst)', 'own-framework'),
    'title_asc' => __('Titel (A-Z)', 'own-framework'),
    'title_desc' => __('Titel (Z-A)', 'own-framework'),
];
?>
<section <?= $wrapper_attributes; ?>>
    <div class="flex items-center justify-between gap-4 mb-6">
        <p class="text-sm text-gray-600" data-own-news-count>
            <?= (int) $found_posts; ?> <?= $found_posts === 1 ? esc_html__('bericht', 'own-framework') : esc_html__('berichten', 'own-framework'); ?> <?php esc_html_e('gevonden', 'own-framework'); ?>
        </p>

        <div class="flex items-center gap-2">
            <div x-data="{ sortOpen: false }" @keydown.escape.window="sortOpen = false" class="relative">
                <button
                    type="button"
                    @click="sortOpen = !sortOpen"
                    class="own-news-sort-trigger own-news-surface inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700 bg-white">
                    <span x-text="$store.newsFilters.sortLabel"></span>
                    <span :class="{ 'rotate-180': sortOpen }" class="transition-transform duration-200">
                        <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                    </span>
                </button>
                <div
                    x-show="sortOpen"
                    x-cloak
                    @click.outside="sortOpen = false"
                    class="own-news-sort-panel absolute right-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                    <?php foreach ($sort_options as $value => $label) : ?>
                        <button
                            type="button"
                            @click="$store.newsFilters.sortBy = '<?= esc_js($value); ?>'; $store.newsFilters.onFilterChange(); sortOpen = false"
                            class="own-news-sort-option flex w-full items-center rounded-lg px-3 py-2 text-sm text-gray-700">
                            <?= esc_html($label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div data-own-news-results class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?= ownframework_news_render_cards($query); ?>
    </div>

    <div data-own-news-pagination>
        <?= ownframework_news_render_pagination($current_page, $query->max_num_pages); ?>
    </div>
</section>
