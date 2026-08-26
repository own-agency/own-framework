<?php
defined('ABSPATH') || exit();

/**
 * Verhalen resultaten — volle breedte (geen sidebar: de categorie-dropdown
 * zit op acf/verhaal-hero's eigen filterbalk, zie dat block's eigen
 * render.php/parts/filter-bar.php). Structureel een kopie van blocks/
 * news/render.php: dezelfde sorteerdropdown + resultatentelling, dezelfde
 * "data-own-verhalen-results/-pagination/-count"-koppeling met de globale
 * $store.verhalenFilters (src/js/scripts/verhalen-filter.js), en dezelfde
 * live REST-gedreven paginering — enige verschil is het kaarttype zelf
 * (parts/card.php, portretformaat i.p.v. news' liggende kaart, zie dat
 * bestand's eigen docblock) en het raster: 1/2/3 kolommen (nooit 4) —
 * portretkaarten in bredere kolommen zouden te breed/plat ogen, dus dit
 * blijft bewust bij het aantal dat news zelf ook gebruikt.
 *
 * "accentColor" is een eigen, plain block-attribute (niet het native
 * "Kleuren"-paneel) voor checkboxes/paginering/sorteerpaneel — sub-elementen
 * die dat paneel nooit bereikt (CLAUDE.md Traps #7), zelfde redenering als
 * acf/news' eigen accentColor. Losstaand van acf/verhaal-hero's eigen
 * accentColor (niet gesynchroniseerd) — zelfde "onafhankelijk per-block
 * kleurattribuut"-precedent als acf/vacature-hero's searchButtonColor t.o.v.
 * acf/vacatures' eigen accentColor.
 *
 * "cardTextColor" is dezelfde soort eigen attribute, voor de titel/quote/
 * datum/link die elke kaart over zijn eigen foto toont (parts/card.php) —
 * bewust ÉÉN attribute i.p.v. acf/news' aparte cardTitleColor/cardTextColor/
 * cardBackgroundColor/cardLinkColor: deze kaart heeft geen eigen te kleuren
 * achtergrond (dat IS de foto), dus is er maar één kleurzone. Wordt hier als
 * CSS custom property op DIT block's eigen wrapper gezet (niet op de kaart
 * zelf) — cascadeert naar elke kaart binnen "data-own-verhalen-results",
 * inclusief kaarten die later via een REST-fetch (verhalen-filter.js) alsnog
 * in diezelfde div terechtkomen: alleen de div's eigen innerHTML wordt
 * vervangen, de wrapper zelf (en dus deze property) blijft gewoon staan.
 *
 * "fallbackImage" (block.json, editor.js's "Kaart zonder foto"-paneel) wordt
 * NIET hier gelezen — parts/card.php haalt 'm zelf op via
 * ownframework_verhalen_get_configured_fallback_image() (hooks/query.php),
 * zelfde "herlees de opgeslagen archive-verhaal-template"-truc als
 * ownframework_verhalen_get_configured_per_page() (CLAUDE.md Traps #29): de
 * kaart-template kent de huidige blok-instantie niet, en dit block bestaat
 * hoe dan ook maar één keer per archiefpagina (block.json's "multiple":
 * false).
 *
 * Rijdt mee op WordPress' eigen hoofd-query zodra dit block daadwerkelijk op
 * de verhalen-archiefpagina staat — alleen de editor-preview (acf's
 * "mode":"preview") krijgt een eigen losse WP_Query.
 *
 * Alleen insertable binnen de verhalen-archiefpagina (templates/
 * archive-verhaal.html) — blocks/verhaal-hero/hooks/
 * restrict-to-archive-verhaal-template.php restrict ook dit block.
 */

$accent = $block['accentColor'] ?? '';
$card_text_color = $block['cardTextColor'] ?? '';
$per_page = max(1, (int) ($block['perPage'] ?? 12));

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$style_parts = ['box-sizing:border-box'];
if ($accent !== '') {
    $style_parts[] = '--own-verhalen-accent:' . esc_attr($accent);
}
if ($card_text_color !== '') {
    $style_parts[] = '--own-verhalen-card-text-color:' . esc_attr($card_text_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' own-verhalen'),
    'style' => implode(';', $style_parts),
]);

global $wp_query;
$uses_main_query = empty($is_preview) && is_post_type_archive('verhaal') && $wp_query->is_main_query();

if ($uses_main_query) {
    $query = $wp_query;
    $current_page = max(1, (int) get_query_var('paged'));
} else {
    $current_page = 1;
    $query = ownframework_verhalen_new_query($_GET, $current_page, $per_page);
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
        <p class="text-sm text-gray-600" data-own-verhalen-count>
            <?= (int) $found_posts; ?> <?= $found_posts === 1 ? esc_html__('verhaal', 'own-framework') : esc_html__('verhalen', 'own-framework'); ?> <?php esc_html_e('gevonden', 'own-framework'); ?>
        </p>

        <div class="flex items-center gap-2">
            <div x-data="{ sortOpen: false }" @keydown.escape.window="sortOpen = false" class="relative">
                <button
                    type="button"
                    @click="sortOpen = !sortOpen"
                    class="own-verhalen-sort-trigger own-verhalen-surface inline-flex h-9 items-center gap-2 rounded-xl px-3 text-sm text-gray-700 bg-white">
                    <span x-text="$store.verhalenFilters.sortLabel"></span>
                    <span :class="{ 'rotate-180': sortOpen }" class="transition-transform duration-200">
                        <?= ownframework_get_icon('chevron-down', 'w-4 h-4 text-gray-500'); ?>
                    </span>
                </button>
                <div
                    x-show="sortOpen"
                    x-cloak
                    @click.outside="sortOpen = false"
                    class="own-verhalen-sort-panel absolute right-0 top-full z-20 mt-2 min-w-56 rounded-xl bg-white p-1">
                    <?php foreach ($sort_options as $value => $label) : ?>
                        <button
                            type="button"
                            @click="$store.verhalenFilters.sortBy = '<?= esc_js($value); ?>'; $store.verhalenFilters.onFilterChange(); sortOpen = false"
                            class="own-verhalen-sort-option flex w-full items-center rounded-lg px-3 py-2 text-sm text-gray-700">
                            <?= esc_html($label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div data-own-verhalen-results class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?= ownframework_verhalen_render_cards($query); ?>
    </div>

    <div data-own-verhalen-pagination>
        <?= ownframework_verhalen_render_pagination($current_page, $query->max_num_pages); ?>
    </div>
</section>
