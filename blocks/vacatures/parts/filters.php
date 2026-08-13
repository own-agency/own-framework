<?php
defined('ABSPATH') || exit();

/**
 * Filter sidebar — one collapsible group per taxonomy in $args['filter_data']
 * (hooks/query.php's ownframework_vacatures_get_filter_data(), called from
 * render.php). Filter/search/sort state itself lives in the global
 * $store.vacaturesFilters (src/js/scripts/vacatures-filter.js, shared with
 * acf/vacature-hero's search bar) — only "filterMenuOpen" (the mobile
 * drawer) is local, read from the block's own root <section> (render.php's
 * "x-data" attribute), since no other block ever needs it.
 *
 * The two ".own-vacatures-btn-accent" buttons below copy core/button's own
 * shape (theme.json's "styles.elements.button": rounded-full, fluid
 * spacing-preset padding, font-weight 600) rather than a hand-picked
 * rounded-xl/px-4 — a button should look like a button everywhere on the
 * site, block-rendered or not. Only the shape is copied, not the classes
 * themselves (wp-block-button__link/wp-element-button): those pull in the
 * NATIVE colour support's own background, which would fight with this
 * button's own accent-colour variable.
 */

$filter_data = $args['filter_data'] ?? [];
?>
<div class="own-vacatures-filters sticky top-24 space-y-4">
    <div class="lg:hidden">
        <button
            type="button"
            @click="filterMenuOpen = true"
            class="own-vacatures-btn-accent w-full justify-center inline-flex items-center gap-2 rounded-full px-(--wp--preset--spacing--m) py-(--wp--preset--spacing--xs) text-sm font-semibold">
            <?= ownframework_get_icon('chevron-down', 'w-4 h-4'); ?>
            <?php esc_html_e('Filters', 'own-framework'); ?>
            <span x-show="$store.vacaturesFilters.getTaxonomyFilterCount() > 0" x-text="'(' + $store.vacaturesFilters.getTaxonomyFilterCount() + ')'"></span>
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
            <button type="button" @click="filterMenuOpen = false" class="own-vacatures-icon-btn rounded-lg p-2 text-gray-500">
                <?= ownframework_get_icon('close', 'w-5 h-5'); ?>
                <span class="sr-only"><?php esc_html_e('Sluiten', 'own-framework'); ?></span>
            </button>
        </div>
        <?php get_template_part('blocks/vacatures/parts/filter-groups', null, ['filter_data' => $filter_data]); ?>
        <button
            type="button"
            @click="filterMenuOpen = false"
            class="own-vacatures-btn-accent w-full justify-center inline-flex items-center mt-6 rounded-full px-(--wp--preset--spacing--m) py-(--wp--preset--spacing--xs) text-sm font-semibold">
            <?php esc_html_e('Toepassen', 'own-framework'); ?>
        </button>
    </div>

    <div class="hidden lg:block">
        <?php get_template_part('blocks/vacatures/parts/filter-groups', null, ['filter_data' => $filter_data]); ?>
    </div>
</div>
