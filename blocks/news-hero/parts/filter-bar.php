<?php
defined('ABSPATH') || exit();

/**
 * Filterbalk — één dropdown per taxonomie (in de praktijk alleen "category",
 * zie blocks/news/hooks/query.php's eigen ownframework_news_get_taxonomies()),
 * zelfde dropdown-idioom als acf/agenda-hero's eigen parts/filter-bar.php
 * (lokale x-data="{ open: false }", x-show + @click.outside +
 * @keydown.escape.window). Elke checkbox-binding is IDENTIEK aan die van
 * agenda: x-model/@change lezen en schrijven de globale $store.newsFilters
 * (src/js/scripts/news-filter.js) — DOM-positie maakt daar niets voor uit.
 *
 * Wraps met "flex-wrap": dezelfde balk op elke breedte, geen aparte mobiele
 * drawer.
 *
 * Geen eigen rand/achtergrond/afronding op de rij hieronder — dit template
 * wordt zelf al in blocks/news-hero/render.php's eigen ".own-news-hero-
 * toolbar" gerenderd, een volle-breedte balk MET een eigen achtergrond, net
 * ná (niet binnen) de gekleurde hero-sectie. Elke individuele trigger
 * (".own-news-hero-filter-trigger") heeft daarom ook bewust GEEN eigen
 * rand/achtergrond, alleen een hover-tint.
 */

$filter_data = $args['filter_data'] ?? [];
?>
<div class="flex flex-wrap items-center gap-2">
    <span class="text-sm font-medium text-gray-500"><?php esc_html_e('Filter op:', 'own-framework'); ?></span>

    <?php foreach ($filter_data as $taxonomy_slug => $taxonomy) : ?>
        <?php $slug_js = esc_js($taxonomy_slug); ?>
        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
            <button
                type="button"
                @click="open = !open"
                class="own-news-hero-filter-trigger inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700">
                <span><?= esc_html($taxonomy['label']); ?></span>
                <span
                    x-show="<?= esc_attr('$store.newsFilters.getSelectedCount(\'' . $slug_js . '\') > 0'); ?>"
                    x-text="<?= esc_attr('$store.newsFilters.getSelectedCount(\'' . $slug_js . '\')'); ?>"
                    class="own-news-hero-filter-badge"></span>
                <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                    <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                </span>
            </button>
            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                class="own-news-sort-panel absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                <?php foreach ($taxonomy['options'] as $option) : ?>
                    <label class="own-news-sort-option flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            value="<?= esc_attr($option['value']); ?>"
                            x-model="<?= esc_attr("\$store.newsFilters.filters['{$slug_js}']"); ?>"
                            @change="$store.newsFilters.onFilterChange()"
                            class="own-news-checkbox rounded border-gray-400">
                        <span><?= esc_html($option['label']); ?></span>
                        <span class="ml-auto text-xs text-gray-500"><?= (int) $option['count']; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button
        type="button"
        x-show="$store.newsFilters.hasTaxonomyFilters()"
        x-cloak
        @click="$store.newsFilters.clearTaxonomyFilters()"
        class="own-news-icon-btn inline-flex h-9 items-center gap-1.5 rounded-xl px-3 text-sm text-gray-500">
        <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
        <span><?php esc_html_e('Wis filters', 'own-framework'); ?></span>
    </button>
</div>
