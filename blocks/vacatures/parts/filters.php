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
// The mobile drawer is teleported out of the block's <section>, so it no
// longer inherits the --own-vacatures-accent the section sets; repeat it there.
$accent = (string) ($args['accent'] ?? '');
$drawer_style = $accent !== '' ? '--own-vacatures-accent:' . $accent : '';
?>
<div class="own-vacatures-filters space-y-4">
    <?php // Same look as the sort button next to the results (own-vacatures-
    // sort-trigger in render.php): white surface, rounded-xl, gray text. The
    // count badge in the accent colour only shows with an active filter. ?>
    <div class="lg:hidden">
        <button
            type="button"
            @click="filterMenuOpen = true"
            :aria-expanded="filterMenuOpen.toString()"
            class="own-vacatures-filter-trigger own-vacatures-surface inline-flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-gray-700 bg-white">
            <?= ownframework_get_icon('filter', 'w-4 h-4 shrink-0 text-gray-500'); ?>
            <span><?php esc_html_e('Filters', 'own-framework'); ?></span>
            <span
                x-show="<?= esc_attr('$store.vacaturesFilters.getTaxonomyFilterCount() > 0'); ?>"
                x-cloak
                x-text="$store.vacaturesFilters.getTaxonomyFilterCount()"
                class="own-vacatures-filter-count ml-auto"></span>
        </button>
    </div>

    <?php // Mobile drawer: same behaviour and build as the header's off-canvas
    // menu (blocks/header/parts/mobile-menu.php), mirrored: slides in from the
    // left (it opens from a control on the left, the menu from the right),
    // fading backdrop, full height, Escape/backdrop closes, page scroll locked.
    // x-teleport to <body>: inside the page content the drawer can end up in
    // a stacking context below the sticky header (z-40); at the end of <body>
    // its z-50 covers the header. Teleported content keeps this block's Alpine
    // scope (filterMenuOpen, render.php's x-data). The scroll lock is the same
    // overflow-clip toggle as the menu's; see that file for why clip, not hidden.
    ?>
    <template x-teleport="body">
        <div class="own-vacatures-drawer lg:hidden" style="<?= esc_attr($drawer_style); ?>" x-effect="<?= esc_attr("document.documentElement.classList.toggle('overflow-clip', filterMenuOpen); document.body.classList.toggle('overflow-clip', filterMenuOpen)"); ?>">
            <div
                x-show="filterMenuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="filterMenuOpen = false"
                class="own-vacatures-drawer__backdrop fixed inset-x-0 z-50 bg-black/50"
                style="top: var(--wp-admin--admin-bar--height, 0px); bottom: 0;"
                aria-hidden="true"></div>

            <div
                x-show="filterMenuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                @keydown.escape.window="filterMenuOpen = false"
                class="own-vacatures-drawer__panel fixed left-0 z-50 flex w-full max-w-sm flex-col bg-white text-gray-900 shadow-lg"
                style="top: var(--wp-admin--admin-bar--height, 0px); bottom: 0;"
                role="dialog"
                aria-modal="true"
                aria-label="<?php esc_attr_e('Filters', 'own-framework'); ?>">

                <div class="flex shrink-0 items-center justify-between px-4 sm:px-6 py-3 border-b border-black/5">
                    <h2 class="m-0! text-(length:--own-h3-font-size)! leading-(--own-h3-line-height)! font-semibold"><?php esc_html_e('Filters', 'own-framework'); ?></h2>
                    <button type="button" @click="filterMenuOpen = false" class="own-vacatures-icon-btn -me-2 inline-flex items-center justify-center rounded-full p-2" aria-label="<?php esc_attr_e('Filters sluiten', 'own-framework'); ?>">
                        <?= ownframework_get_icon('close', 'w-5 h-5'); ?>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-5">
                    <?php get_template_part('blocks/vacatures/parts/filter-groups', null, ['filter_data' => $filter_data]); ?>
                </div>

                <div class="shrink-0 border-t border-black/5 p-4 sm:p-6">
                    <button
                        type="button"
                        @click="filterMenuOpen = false"
                        class="own-vacatures-btn-accent w-full justify-center inline-flex items-center rounded-full px-(--wp--preset--spacing--m) py-(--wp--preset--spacing--xs) text-sm font-semibold">
                        <?php esc_html_e('Toepassen', 'own-framework'); ?>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <div class="hidden lg:block">
        <?php get_template_part('blocks/vacatures/parts/filter-groups', null, ['filter_data' => $filter_data]); ?>
    </div>
</div>