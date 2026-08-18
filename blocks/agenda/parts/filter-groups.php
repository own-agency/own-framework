<?php
defined('ABSPATH') || exit();

/**
 * The actual filter accordion — shared markup for the mobile drawer and the
 * desktop sidebar (parts/filters.php's two consumers). Every binding reads/
 * writes the global $store.agendaFilters (src/js/scripts/agenda-filter.js).
 *
 * "Periode" is rendered first and separately from the taxonomy loop below —
 * it's a single-select radio group (aankomend/afgelopen/alle) backed by a
 * meta_query, not a taxonomy, so it can't reuse the checkbox markup the
 * taxonomy groups use.
 */

$filter_data = $args['filter_data'] ?? [];
$periode_options = $args['periode_options'] ?? [];
?>
<div x-show="$store.agendaFilters.hasTaxonomyFilters()" class="rounded-xl own-agenda-surface overflow-hidden mb-4">
    <div class="flex items-center justify-between px-4 py-2.5 gap-3">
        <p class="text-sm text-gray-600">
            <span class="font-medium text-gray-900" x-text="$store.agendaFilters.getTaxonomyFilterCount()"></span> <?php esc_html_e('filter(s) actief', 'own-framework'); ?>
        </p>
        <button type="button" @click="$store.agendaFilters.clearTaxonomyFilters()" class="own-agenda-icon-btn rounded-lg p-2 text-gray-500 hover:text-gray-600 shrink-0">
            <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
        </button>
    </div>
</div>

<div class="bg-white rounded-xl own-agenda-surface overflow-hidden mb-4">
    <div class="px-4 py-3 bg-gray-50">
        <span class="text-sm font-medium text-gray-900"><?php esc_html_e('Periode', 'own-framework'); ?></span>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 space-y-2">
        <?php foreach ($periode_options as $value => $label) : ?>
            <label class="flex items-center gap-3 text-sm text-gray-700">
                <input
                    type="radio"
                    name="own-agenda-periode"
                    value="<?= esc_attr($value); ?>"
                    x-model="$store.agendaFilters.periode"
                    @change="$store.agendaFilters.onFilterChange()"
                    class="own-agenda-radio border-gray-400">
                <span><?= esc_html($label); ?></span>
                <span class="text-xs text-gray-500 ml-auto" x-text="$store.agendaFilters.periodeCounts['<?= esc_js($value); ?>'] ?? ''"></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($filter_data as $taxonomy_slug => $taxonomy) : ?>
    <?php $slug_js = esc_js($taxonomy_slug); ?>
    <div class="bg-white rounded-xl own-agenda-surface overflow-hidden mb-4" x-data="{ open: true }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full bg-gray-50 px-4 py-3">
            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-900">
                <?= esc_html($taxonomy['label']); ?>
                <span x-show="<?= esc_attr('$store.agendaFilters.getSelectedCount(\'' . $slug_js . '\') > 0'); ?>" x-text="<?= esc_attr('\'(\' + $store.agendaFilters.getSelectedCount(\'' . $slug_js . '\') + \')\''); ?>"></span>
            </span>
            <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-600'); ?>
            </span>
        </button>
        <div x-show="open" class="px-4 py-3 border-t border-gray-200 space-y-2">
            <?php foreach ($taxonomy['options'] as $option) : ?>
                <label class="flex items-center gap-3 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        value="<?= esc_attr($option['value']); ?>"
                        x-model="<?= esc_attr("\$store.agendaFilters.filters['{$slug_js}']"); ?>"
                        @change="$store.agendaFilters.onFilterChange()"
                        class="own-agenda-checkbox rounded border-gray-400">
                    <span><?= esc_html($option['label']); ?></span>
                    <span class="text-xs text-gray-500 ml-auto"><?= (int) $option['count']; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
