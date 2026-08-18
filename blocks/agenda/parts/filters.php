<?php
defined('ABSPATH') || exit();

/**
 * Filter sidebar — a "Periode" group (radio, single-select) always shown
 * first, then one collapsible group per taxonomy in $args['filter_data']
 * (hooks/query.php's ownframework_agenda_get_filter_data()). Same mobile-
 * drawer + desktop-sidebar split as blocks/vacatures/parts/filters.php;
 * state itself lives in the global $store.agendaFilters (src/js/scripts/
 * agenda-filter.js) — only "filterMenuOpen" is local, read from this
 * block's own root <section> (render.php's "x-data" attribute).
 */

$filter_data = $args['filter_data'] ?? [];
$periode_options = $args['periode_options'] ?? [];
?>
<div class="own-agenda-filters space-y-4">
    <div class="lg:hidden">
        <button
            type="button"
            @click="filterMenuOpen = true"
            class="own-agenda-btn-accent w-full justify-center inline-flex items-center gap-2 rounded-full px-(--wp--preset--spacing--m) py-(--wp--preset--spacing--xs) text-sm font-semibold">
            <?= ownframework_get_icon('chevron-down', 'w-4 h-4'); ?>
            <?php esc_html_e('Filters', 'own-framework'); ?>
            <span x-show="$store.agendaFilters.getTaxonomyFilterCount() > 0" x-text="'(' + $store.agendaFilters.getTaxonomyFilterCount() + ')'"></span>
        </button>
    </div>

    <div
        x-show="filterMenuOpen"
        x-cloak
        class="lg:hidden fixed inset-0 z-50 bg-black/50"
        @click="filterMenuOpen = false"
        style="display:none;">
    </div>

    <div
        x-show="filterMenuOpen"
        x-cloak
        @click.outside="filterMenuOpen = false"
        class="lg:hidden fixed inset-y-0 left-0 z-50 w-full max-w-sm overflow-y-auto bg-white px-4 py-5"
        style="display:none;">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Filters', 'own-framework'); ?></h2>
            <button type="button" @click="filterMenuOpen = false" class="own-agenda-icon-btn rounded-lg p-2 text-gray-600">
                <?= ownframework_get_icon('close', 'w-5 h-5'); ?>
                <span class="sr-only"><?php esc_html_e('Sluiten', 'own-framework'); ?></span>
            </button>
        </div>
        <?php get_template_part('blocks/agenda/parts/filter-groups', null, ['filter_data' => $filter_data, 'periode_options' => $periode_options]); ?>
        <button
            type="button"
            @click="filterMenuOpen = false"
            class="own-agenda-btn-accent w-full justify-center inline-flex items-center mt-6 rounded-full px-(--wp--preset--spacing--m) py-(--wp--preset--spacing--xs) text-sm font-semibold">
            <?php esc_html_e('Toepassen', 'own-framework'); ?>
        </button>
    </div>

    <div class="hidden lg:block">
        <?php get_template_part('blocks/agenda/parts/filter-groups', null, ['filter_data' => $filter_data, 'periode_options' => $periode_options]); ?>
    </div>
</div>
