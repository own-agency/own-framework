<?php
defined('ABSPATH') || exit();

/**
 * Vacatures resultaten — filteren (per taxonomie op het vacature post type,
 * automatisch gevonden door hooks/query.php's
 * ownframework_vacatures_get_taxonomies()), sorteren en paginering. De
 * zoekbalk zelf staat op acf/vacature-hero (een apart block, meestal erboven
 * in templates/archive-vacancy.html) — beide delen hun staat via de globale
 * $store.vacaturesFilters (src/js/scripts/vacatures-filter.js), niet via
 * lokale x-data, want dat moet vanuit twee losse blocks/DOM-subtrees
 * bereikbaar zijn (zelfde reden als blocks/header/parts/search-modal.php's
 * eigen $store.searchDialog/$store.liveSearch). Dit block's eigen "data-
 * own-vacatures-filters"-attribuut hieronder (JSON) is waar de store haar
 * initiële taxonomie-data + actieve filters vandaan leest.
 *
 * "filterMenuOpen" (mobiel filterpaneel open/dicht) is wél lokale x-data,
 * niet in de store: puur UI-state voor dit block's eigen mobiele paneel,
 * niet iets wat acf/vacature-hero ooit moet lezen — zelfde onderscheid als
 * header's eigen lokale "mobileMenuOpen" naast de globale search-stores
 * (search-modal.php's eigen docblock).
 *
 * Eén eigen kleur-attribute ("accentColor") i.p.v. het native "Kleuren"-
 * paneel (block.json heeft daarom bewust geen "supports.color") — badges,
 * checkboxes en paginering zijn allemaal sub-elementen die dat paneel nooit
 * bereikt (zelfde beperking als acf/cta's "cardBackgroundColor", CLAUDE.md
 * Traps #7). Als CSS custom property op de wrapper (src/css/components/
 * vacatures.css leest 'm), niet per element doorgegeven.
 *
 * Alleen insertable binnen de vacature-archiefpagina (templates/
 * archive-vacancy.html) — blocks/vacature-hero/hooks/
 * restrict-to-archive-vacancy-template.php restrict ook dit block.
 */

$accent = $block['accentColor'] ?? '';
$per_page = max(1, (int) ($block['perPage'] ?? 10));

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$style_parts = ['box-sizing:border-box'];
if ($accent !== '') {
    $style_parts[] = '--own-vacatures-accent:' . esc_attr($accent);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' own-vacatures'),
    'style' => implode(';', $style_parts),
]);

$filter_data = ownframework_vacatures_get_filter_data();
$has_filters = !empty($filter_data);

$parsed_params = ownframework_vacatures_parse_params($_GET);

global $wp_query;
$uses_main_query = empty($is_preview) && is_post_type_archive(OWNFRAMEWORK_VACATURES_POST_TYPE) && $wp_query->is_main_query();

if ($uses_main_query) {
    $query = $wp_query;
    $current_page = max(1, (int) get_query_var('paged'));
} else {
    $current_page = 1;
    $query = ownframework_vacatures_new_query($_GET, $current_page, $per_page);
}

$found_posts = $query->found_posts;

$sort_options = [
    'date_desc' => __('Datum (nieuwste eerst)', 'own-framework'),
    'date_asc' => __('Datum (oudste eerst)', 'own-framework'),
    'title_asc' => __('Titel (A-Z)', 'own-framework'),
    'title_desc' => __('Titel (Z-A)', 'own-framework'),
];

$filters_config = [
    'taxonomies' => $filter_data,
    'activeTerms' => $parsed_params['tax_terms'],
    'search' => $parsed_params['search'],
    'perPage' => $per_page,
];
?>
<section <?= $wrapper_attributes; ?> x-data="{ filterMenuOpen: false }" data-own-vacatures-filters="<?= esc_attr(wp_json_encode($filters_config)); ?>">
    <div class="py-10 sm:py-12 lg:py-16">
        <div class="grid grid-cols-1 <?= $has_filters ? 'lg:grid-cols-4 gap-8 lg:gap-10' : ''; ?>">
            <?php if ($has_filters) : ?>
                <div class="lg:col-span-1">
                    <?php get_template_part('blocks/vacatures/parts/filters', null, ['filter_data' => $filter_data]); ?>
                </div>
            <?php endif; ?>

            <div class="<?= $has_filters ? 'lg:col-span-3' : ''; ?>">
                <div class="flex items-center justify-between mb-6">
                    <p class="text-sm text-gray-600" data-own-vacatures-count>
                        <?= (int) $found_posts; ?> <?= $found_posts === 1 ? esc_html__('vacature', 'own-framework') : esc_html__('vacatures', 'own-framework'); ?> <?php esc_html_e('gevonden', 'own-framework'); ?>
                    </p>

                    <select x-model="$store.vacaturesFilters.sortBy" @change="$store.vacaturesFilters.onFilterChange()" class="own-vacatures-sort text-sm text-gray-700 border border-gray-200 rounded-xl px-3 py-2 bg-white">
                        <?php foreach ($sort_options as $value => $label) : ?>
                            <option value="<?= esc_attr($value); ?>"><?= esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div data-own-vacatures-results>
                    <?= ownframework_vacatures_render_cards($query); ?>
                </div>

                <div data-own-vacatures-pagination>
                    <?= ownframework_vacatures_render_pagination($current_page, $query->max_num_pages); ?>
                </div>
            </div>
        </div>
    </div>
</section>