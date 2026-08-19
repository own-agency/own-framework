<?php
defined('ABSPATH') || exit();

/**
 * Filterbalk — Periode + één dropdown per taxonomie, vervangt acf/agenda's
 * eigen sidebar/mobiele drawer. Elke dropdown is een kopie van blocks/
 * agenda/render.php's eigen sorteerdropdown-idioom (lokale
 * x-data="{ open: false }", x-show + @click.outside + @keydown.escape.window)
 * — geen nieuw mechanisme, gewoon dezelfde herhaald per filter. Elke
 * checkbox/radio-binding hieronder is IDENTIEK aan blocks/agenda/parts/
 * filter-groups.php (nu verwijderd): x-model/@change lezen en schrijven de
 * globale $store.agendaFilters (src/js/scripts/agenda-filter.js) — DOM-
 * positie maakt daar niets voor uit, dus dit blok kon zonder enige JS-
 * wijziging naar hier verhuizen.
 *
 * "Periode"'s trigger-label toont bewust de HUIDIGE keuze (i.p.v. altijd
 * kaal "Periode" te tonen) — de JS-store houdt geen label-lookup bij (alleen
 * de rauwe waarde), dus dit bouwt zelf een klein, lokaal Alpine-object
 * ($periode_label_js) van waarde → label, puur in dit template — geen
 * store-wijziging nodig voor iets dat evengoed hier, dicht bij de enige
 * plek die het gebruikt, kan blijven staan.
 *
 * Wraps met "flex-wrap": bewust GEEN aparte mobiele drawer (meer) — dezelfde
 * balk op elke breedte, die op een smal scherm gewoon naar een 2e regel
 * doorloopt in plaats van achter een aparte knop/paneel te verdwijnen.
 *
 * Geen eigen rand/achtergrond/afronding op de rij hieronder — dit template
 * wordt zelf al in blocks/agenda-hero/render.php's eigen ".own-agenda-hero-
 * toolbar" gerenderd, een volle-breedte balk MET een eigen achtergrond, net
 * ná (niet binnen) de gekleurde hero-sectie. Elke individuele trigger
 * (".own-agenda-hero-filter-trigger") heeft daarom ook bewust GEEN eigen
 * rand/achtergrond, alleen een hover-tint — een 2e, eigen wit doosje per
 * knop zou op die ene gedeelde balk-achtergrond weer als "wit-op-wit" ogen,
 * dezelfde valkuil die acf/content-blok's rand-om-een-afbeelding al eerder
 * had.
 *
 * "Maand" is GEEN taxonomie maar wél multi-select, net als de taxonomie-
 * dropdowns hieronder (eigen meta_query — blocks/agenda/hooks/query.php's
 * ownframework_agenda_build_month_meta_query(), jaar-onafhankelijk: "Augustus"
 * matcht elke augustus, niet één specifiek jaar) — daarom hetzelfde trigger/
 * paneel-idioom als een taxonomie-dropdown (checkboxes + een tel-badge op de
 * trigger, x-model op `$store.agendaFilters.month`, een array net als
 * `filters['<taxonomie>']`), niet "Periode"'s radio-idioom hierboven ("Periode"
 * blijft single-select: er is altijd precies één actieve waarde). De opties
 * zelf komen uit ownframework_agenda_get_month_options(), dus deze dropdown
 * toont vanzelf alleen maanden die ook echt agenda-momenten hebben — nooit
 * een lege, permanent-0-tellende maand.
 */

$filter_data = $args['filter_data'] ?? [];
$periode_options = $args['periode_options'] ?? [];
$month_options = $args['month_options'] ?? [];

$periode_label_pairs = [];
foreach ($periode_options as $value => $label) {
    $periode_label_pairs[] = "'" . esc_js($value) . "':'" . esc_js($label) . "'";
}
$periode_label_js = '{' . implode(',', $periode_label_pairs) . '}';
$periode_fallback_label = reset($periode_options) ?: __('Periode', 'own-framework');

$month_label = __('Maand', 'own-framework');
?>
<div class="flex flex-wrap items-center gap-2">
    <span class="text-sm font-medium text-gray-500"><?php esc_html_e('Filter op:', 'own-framework'); ?></span>

    <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
        <button
            type="button"
            @click="open = !open"
            class="own-agenda-hero-filter-trigger inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700">
            <span x-text="(<?= $periode_label_js; ?>)[$store.agendaFilters.periode] || '<?= esc_js($periode_fallback_label); ?>'"></span>
            <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
            </span>
        </button>
        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            class="own-agenda-sort-panel absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
            <?php foreach ($periode_options as $value => $label) : ?>
                <label class="own-agenda-sort-option flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700">
                    <input
                        type="radio"
                        name="own-agenda-hero-periode"
                        value="<?= esc_attr($value); ?>"
                        x-model="$store.agendaFilters.periode"
                        @change="$store.agendaFilters.onFilterChange(); open = false"
                        class="own-agenda-radio border-gray-400">
                    <span><?= esc_html($label); ?></span>
                    <span class="ml-auto text-xs text-gray-500" x-text="$store.agendaFilters.periodeCounts['<?= esc_js($value); ?>'] ?? ''"></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($month_options)) : ?>
        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
            <button
                type="button"
                @click="open = !open"
                class="own-agenda-hero-filter-trigger inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700">
                <span><?= esc_html($month_label); ?></span>
                <span
                    x-show="$store.agendaFilters.month.length > 0"
                    x-text="$store.agendaFilters.month.length"
                    class="own-agenda-hero-filter-badge"></span>
                <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                    <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                </span>
            </button>
            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                class="own-agenda-sort-panel absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                <?php foreach ($month_options as $value => $label) : ?>
                    <label class="own-agenda-sort-option flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            value="<?= esc_attr($value); ?>"
                            x-model="$store.agendaFilters.month"
                            @change="$store.agendaFilters.onFilterChange()"
                            class="own-agenda-checkbox rounded border-gray-400">
                        <span><?= esc_html($label); ?></span>
                        <span class="ml-auto text-xs text-gray-500" x-text="$store.agendaFilters.monthCounts['<?= esc_js($value); ?>'] ?? ''"></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($filter_data as $taxonomy_slug => $taxonomy) : ?>
        <?php $slug_js = esc_js($taxonomy_slug); ?>
        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
            <button
                type="button"
                @click="open = !open"
                class="own-agenda-hero-filter-trigger inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700">
                <span><?= esc_html($taxonomy['label']); ?></span>
                <span
                    x-show="<?= esc_attr('$store.agendaFilters.getSelectedCount(\'' . $slug_js . '\') > 0'); ?>"
                    x-text="<?= esc_attr('$store.agendaFilters.getSelectedCount(\'' . $slug_js . '\')'); ?>"
                    class="own-agenda-hero-filter-badge"></span>
                <span :class="{ 'rotate-180': open }" class="transition-transform duration-200">
                    <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                </span>
            </button>
            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                class="own-agenda-sort-panel absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                <?php foreach ($taxonomy['options'] as $option) : ?>
                    <label class="own-agenda-sort-option flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            value="<?= esc_attr($option['value']); ?>"
                            x-model="<?= esc_attr("\$store.agendaFilters.filters['{$slug_js}']"); ?>"
                            @change="$store.agendaFilters.onFilterChange()"
                            class="own-agenda-checkbox rounded border-gray-400">
                        <span><?= esc_html($option['label']); ?></span>
                        <span class="ml-auto text-xs text-gray-500"><?= (int) $option['count']; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button
        type="button"
        x-show="$store.agendaFilters.hasTaxonomyFilters()"
        x-cloak
        @click="$store.agendaFilters.clearTaxonomyFilters()"
        class="own-agenda-icon-btn inline-flex h-9 items-center gap-1.5 rounded-xl px-3 text-sm text-gray-500">
        <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
        <span><?php esc_html_e('Wis filters', 'own-framework'); ?></span>
    </button>
</div>
