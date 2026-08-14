<?php
defined('ABSPATH') || exit();

/**
 * The actual filter accordion — shared markup for the mobile drawer and the
 * desktop sidebar (parts/filters.php's two consumers), extracted here rather
 * than duplicated between them. Every binding reads/writes the global
 * $store.vacaturesFilters (src/js/scripts/vacatures-filter.js) — this part
 * introduces no Alpine scope of its own.
 *
 * $taxonomy['icon'] (hooks/query.php's ownframework_vacatures_get_filter_data(),
 * via ownframework_vacature_taxonomy_icon()) is echoed unescaped on purpose —
 * it's raw SVG sanitized with wp_kses on save
 * (inc/functions/vacature-taxonomy-settings.php), not at output time.
 */

$filter_data = $args['filter_data'] ?? [];
?>
<div x-show="$store.vacaturesFilters.searchQuery.trim() !== '' || $store.vacaturesFilters.hasTaxonomyFilters()" class="rounded-xl own-vacatures-surface overflow-hidden mb-4">
    <div x-show="$store.vacaturesFilters.searchQuery.trim() !== ''" class="flex items-center justify-between px-4 py-2.5 gap-3">
        <p class="text-sm text-gray-500 truncate">
            <span class="font-medium text-gray-900"><?php esc_html_e('Zoekterm:', 'own-framework'); ?></span> <span x-text="$store.vacaturesFilters.searchQuery"></span>
        </p>
        <button type="button" @click="$store.vacaturesFilters.searchQuery = ''; $store.vacaturesFilters.submitSearch()" class="own-vacatures-icon-btn rounded-lg p-2 text-gray-400 hover:text-gray-600 shrink-0">
            <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
        </button>
    </div>
    <div x-show="$store.vacaturesFilters.searchQuery.trim() !== '' && $store.vacaturesFilters.hasTaxonomyFilters()" class="h-px bg-gray-100 mx-4"></div>
    <div x-show="$store.vacaturesFilters.hasTaxonomyFilters()" class="flex items-center justify-between px-4 py-2.5 gap-3">
        <p class="text-sm text-gray-500">
            <span class="font-medium text-gray-900" x-text="$store.vacaturesFilters.getTaxonomyFilterCount()"></span> <?php esc_html_e('filter(s) actief', 'own-framework'); ?>
        </p>
        <button type="button" @click="$store.vacaturesFilters.clearTaxonomyFilters()" class="own-vacatures-icon-btn rounded-lg p-2 text-gray-400 hover:text-gray-600 shrink-0">
            <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
        </button>
    </div>
</div>

<?php foreach ($filter_data as $taxonomy_slug => $taxonomy) : ?>
    <?php $slug_js = esc_js($taxonomy_slug); ?>
    <div class="bg-white rounded-xl own-vacatures-surface overflow-hidden mb-4" x-data="{ open: true }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full bg-gray-50 px-4 py-3">
            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                <?php if (!empty($taxonomy['icon'])) : ?>
                    <span class="own-vacatures-tax-icon w-4 h-4 shrink-0" aria-hidden="true"><?= $taxonomy['icon']; ?></span>
                <?php endif; ?>
                <?= esc_html($taxonomy['label']); ?>
                <span x-show="<?= esc_attr('$store.vacaturesFilters.getSelectedCount(\'' . $slug_js . '\') > 0'); ?>" x-text="<?= esc_attr('\'(\' + $store.vacaturesFilters.getSelectedCount(\'' . $slug_js . '\') + \')\''); ?>"></span>
            </span>
            <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
            </span>
        </button>
        <div x-show="open" class="px-4 py-3 border-t border-gray-100 space-y-2">
            <?php foreach ($taxonomy['options'] as $option) : ?>
                <label class="flex items-center gap-3 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        value="<?= esc_attr($option['value']); ?>"
                        x-model="<?= esc_attr("\$store.vacaturesFilters.filters['{$slug_js}']"); ?>"
                        @change="$store.vacaturesFilters.onFilterChange()"
                        class="own-vacatures-checkbox rounded border-gray-300">
                    <span><?= esc_html($option['label']); ?></span>
                    <span class="text-xs text-gray-400 ml-auto"><?= (int) $option['count']; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
