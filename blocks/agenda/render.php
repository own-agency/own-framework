<?php
defined('ABSPATH') || exit();

/**
 * Agenda resultaten — filteren (op "agenda-categorie", automatisch gevonden
 * door hooks/query.php's ownframework_agenda_get_taxonomies(), plus het
 * eigen "periode"-facet: aankomend/afgelopen/alle), sorteren op datum en
 * paginering. Zelfde opzet als blocks/vacatures/render.php, met twee
 * inhoudelijke verschillen: er is geen apart "hero met zoekbalk"-block (een
 * schoolagenda heeft geen zoekbehoefte die dat rechtvaardigt) — dit block
 * bevat daarom zijn eigen (optionele) zoekveld niet, en de standaard-
 * sortering is chronologisch oplopend (eerstvolgende moment eerst) in
 * plaats van "nieuwste eerst": dit is een kalender van momenten, geen
 * nieuwsfeed van berichten.
 *
 * Filter/sorteer-state leeft in de globale $store.agendaFilters (src/js/
 * scripts/agenda-filter.js) — een globale store (niet lokale x-data) omdat
 * de REST-aangedreven filtering pagina-breed werkt (checkboxes, periode-
 * radio's, sorteerknop, paginering), zelfde reden als $store.vacaturesFilters.
 * "filterMenuOpen" (mobiel filterpaneel) én "view" (lijst/raster-toggle)
 * zijn wél lokale x-data, puur UI-state voor dit block's eigen instantie: ze
 * raken de query/REST-parameters nooit, alleen welke layout-classes de eigen
 * "data-own-agenda-results"-div hieronder krijgt. "view" wordt bewust in
 * localStorage bewaard (niet in de URL/REST-call) — een weergavekeuze is
 * geen filter, en hoeft dus niet deelbaar/bookmarkbaar te zijn, wel te
 * onthouden tussen bezoeken. ownframework_agenda_render_cards() (hooks/
 * query.php) rendert de kaarten zelf zonder eigen layout-wrapper — precies
 * zodat die keuze intact blijft over elke filter/sorteer/pagina-fetch heen
 * (alleen de INHOUD van "data-own-agenda-results" wordt vervangen, nooit de
 * div zelf).
 *
 * Eigen kleur-attributes ("accentColor", "dateBadgeColor", en de kaart's
 * eigen "cardTitleColor"/"cardTextColor"/"cardBackgroundColor"/
 * "cardLinkColor") i.p.v. het native "Kleuren"-paneel — badges, checkboxes,
 * paginering én de agenda-kaarten (parts/card.php) zijn allemaal
 * sub-elementen die dat paneel nooit bereikt (CLAUDE.md Traps #7, zelfde
 * redenering als acf/vacatures). "dateBadgeColor" is bewust een EIGEN
 * attribute, los van "accentColor": zo kan een editor de datum-badge (het
 * "dag/maand"-vierkant, parts/card.php) een andere kleur geven dan de
 * categorie-badges/filters/paginering — het is per slot een aparte CSS
 * custom property ("--own-agenda-date-accent" resp. "--own-agenda-accent"),
 * maar agenda.css's ".own-agenda-date-badge" valt terug op
 * "--own-agenda-accent" (en die weer op de site-brede fallback) zolang
 * "dateBadgeColor" niet apart is ingesteld — een verse installatie ziet dus
 * nog gewoon één samenhangende accentkleur, tot iemand bewust kiest om de
 * datum-badge te laten afwijken.
 *
 * Alleen insertable binnen de agenda-archiefpagina (templates/
 * archive-agenda.html) — hooks/restrict-to-archive-agenda-template.php.
 */

$accent = $block['accentColor'] ?? '';
$date_badge_color = $block['dateBadgeColor'] ?? '';
$per_page = max(1, (int) ($block['perPage'] ?? 10));

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$card_title_color = $block['cardTitleColor'] ?? '';
$card_text_color = $block['cardTextColor'] ?? '';
$card_bg_color = $block['cardBackgroundColor'] ?? '';
$card_link_color = $block['cardLinkColor'] ?? '';

$style_parts = ['box-sizing:border-box'];
if ($accent !== '') {
    $style_parts[] = '--own-agenda-accent:' . esc_attr($accent);
}
if ($date_badge_color !== '') {
    $style_parts[] = '--own-agenda-date-accent:' . esc_attr($date_badge_color);
}
if ($card_title_color !== '') {
    $style_parts[] = '--own-agenda-card-title-color:' . esc_attr($card_title_color);
}
if ($card_text_color !== '') {
    $style_parts[] = '--own-agenda-card-text-color:' . esc_attr($card_text_color);
}
if ($card_bg_color !== '') {
    $style_parts[] = '--own-agenda-card-bg-color:' . esc_attr($card_bg_color);
}
if ($card_link_color !== '') {
    $style_parts[] = '--own-agenda-card-link-color:' . esc_attr($card_link_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' own-agenda'),
    'style' => implode(';', $style_parts),
]);

$filter_data = ownframework_agenda_get_filter_data();
$periode_options = ownframework_agenda_get_periode_options();

$parsed_params = ownframework_agenda_parse_params($_GET);

global $wp_query;
$uses_main_query = empty($is_preview) && is_post_type_archive(OWNFRAMEWORK_AGENDA_POST_TYPE) && $wp_query->is_main_query();

if ($uses_main_query) {
    $query = $wp_query;
    $current_page = max(1, (int) get_query_var('paged'));
} else {
    $current_page = 1;
    $query = ownframework_agenda_new_query($_GET, $current_page, $per_page);
}

$found_posts = $query->found_posts;

$sort_options = [
    'date_asc' => __('Datum (eerstvolgende eerst)', 'own-framework'),
    'date_desc' => __('Datum (laatste eerst)', 'own-framework'),
    'title_asc' => __('Titel (A-Z)', 'own-framework'),
    'title_desc' => __('Titel (Z-A)', 'own-framework'),
];

$filters_config = [
    'taxonomies' => $filter_data,
    'activeTerms' => $parsed_params['tax_terms'],
    'periodeOptions' => $periode_options,
    'periode' => $parsed_params['periode'],
    'search' => $parsed_params['search'],
    'perPage' => $per_page,
];
?>
<section <?= $wrapper_attributes; ?> x-data="{ filterMenuOpen: false, view: (localStorage.getItem('own-agenda-view') || 'list') }" data-own-agenda-filters="<?= esc_attr(wp_json_encode($filters_config)); ?>">
    <div class="grid grid-cols-1 lg:grid-cols-[20rem_1fr] gap-8 lg:gap-10">
        <div>
            <?php get_template_part('blocks/agenda/parts/filters', null, ['filter_data' => $filter_data, 'periode_options' => $periode_options]); ?>
        </div>

        <div>
            <div class="flex items-center justify-between gap-4 mb-6">
                <p class="text-sm text-gray-600" data-own-agenda-count>
                    <?= (int) $found_posts; ?> <?= $found_posts === 1 ? esc_html__('moment', 'own-framework') : esc_html__('momenten', 'own-framework'); ?> <?php esc_html_e('gevonden', 'own-framework'); ?>
                </p>

                <div class="flex items-center gap-2">
                    <?php // "Weergave" (lijst/raster) — pure client-side UI state, local
                    // x-data on the section (same reasoning as "filterMenuOpen"'s own
                    // docblock further up), not the global $store.agendaFilters: it
                    // never needs to be read from anywhere outside this one block
                    // instance, and doesn't affect the query/REST params at all — only
                    // which layout classes render.php's own "data-own-agenda-results"
                    // div carries. Persisted in localStorage (not a REST/URL param) so
                    // a visitor's choice survives a reload without a round trip. ?>
                    <?php // "h-9" on the wrapper + "h-7" buttons (with the wrapper's own
                    // "p-1" = 4px top/bottom padding, 28px + 4px + 4px = 36px) is a
                    // deliberate pixel-match against the sort trigger button just below,
                    // which is also explicitly "h-9" now rather than relying on its
                    // "py-2" padding to happen to produce the same height — two controls
                    // sitting side by side read as a mismatched pair the moment their
                    // heights differ by even a pixel. ?>
                    <div class="own-agenda-view-toggle own-agenda-surface inline-flex h-9 items-center gap-1 rounded-xl bg-white p-1" role="group" aria-label="<?php esc_attr_e('Weergave', 'own-framework'); ?>">
                        <button
                            type="button"
                            @click="view = 'list'; localStorage.setItem('own-agenda-view', 'list')"
                            :class="view === 'list' ? 'own-agenda-view-toggle__btn own-agenda-view-toggle__btn--active' : 'own-agenda-view-toggle__btn'"
                            :aria-pressed="view === 'list' ? 'true' : 'false'"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                            aria-label="<?php esc_attr_e('Lijstweergave', 'own-framework'); ?>">
                            <?= ownframework_get_icon('list', 'w-4 h-4'); ?>
                        </button>
                        <button
                            type="button"
                            @click="view = 'grid'; localStorage.setItem('own-agenda-view', 'grid')"
                            :class="view === 'grid' ? 'own-agenda-view-toggle__btn own-agenda-view-toggle__btn--active' : 'own-agenda-view-toggle__btn'"
                            :aria-pressed="view === 'grid' ? 'true' : 'false'"
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
                            aria-label="<?php esc_attr_e('Rasterweergave', 'own-framework'); ?>">
                            <?= ownframework_get_icon('grid', 'w-4 h-4'); ?>
                        </button>
                    </div>

                    <div x-data="{ sortOpen: false }" @keydown.escape.window="sortOpen = false" class="relative">
                        <button
                            type="button"
                            @click="sortOpen = !sortOpen"
                            class="own-agenda-sort-trigger own-agenda-surface inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700 bg-white">
                            <span x-text="$store.agendaFilters.sortLabel"></span>
                            <span :class="{ 'rotate-180': sortOpen }" class="transition-transform duration-200">
                                <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                            </span>
                        </button>
                        <div
                            x-show="sortOpen"
                            x-cloak
                            @click.outside="sortOpen = false"
                            class="own-agenda-sort-panel absolute right-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                            <?php foreach ($sort_options as $value => $label) : ?>
                                <button
                                    type="button"
                                    @click="$store.agendaFilters.sortBy = '<?= esc_js($value); ?>'; $store.agendaFilters.onFilterChange(); sortOpen = false"
                                    class="own-agenda-sort-option flex w-full items-center rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <?= esc_html($label); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php // The grid/list layout classes live HERE, on this div, on purpose —
            // this element itself is never replaced (only its innerHTML, by
            // src/js/scripts/agenda-filter.js's own apply()), while
            // ownframework_agenda_render_cards() (hooks/query.php) renders the
            // cards with no layout wrapper of their own. So the chosen "view"
            // survives every filter/sort/page fetch without needing to travel
            // through the REST response at all. ?>
            <div data-own-agenda-results :class="view === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 gap-6' : 'flex flex-col gap-4'">
                <?= ownframework_agenda_render_cards($query); ?>
            </div>

            <div data-own-agenda-pagination>
                <?= ownframework_agenda_render_pagination($current_page, $query->max_num_pages); ?>
            </div>
        </div>
    </div>
</section>
