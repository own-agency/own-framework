<?php

defined('ABSPATH') || exit();

/**
 * FAQ's — een uitklapbare lijst (accordion), uit drie mogelijke bronnen
 * ("source", editor.js's "Bron"-SelectControl):
 *
 * - "manual": rechtstreeks in dit blok ingevoerde vraag/antwoord-paren
 *   (ACF-repeater "faqs_items", acf-json/group_block_faqs.json) — geen
 *   InnerBlocks-kinderen zoals acf/proces-stap, want elk item hier heeft maar
 *   twee simpele tekstvelden (vraag + antwoord) en de vraag moet ook nog de
 *   klikbare accordion-trigger zijn; een los child-block per item zou dat
 *   alleen ingewikkelder maken zonder dat de editor er iets mee wint.
 * - "handpicked": een ACF relationship-veld ("faqs_geselecteerd") naar het
 *   "faq" post type — zelfde reden als acf/vacatures-uitgelicht's eigen
 *   "geselecteerde_vacatures" (dat blok's docblock): ACF's eigen zoek/sleep-UI
 *   is voor "kies een paar posts uit een lijst" al kant-en-klaar.
 * - "category": een ACF taxonomy-veld ("faqs_categorie") naar de
 *   "faq-categorie" taxonomie — toont alle gepubliceerde FAQ's in die
 *   categorie, geen aparte "aantal"-instelling (een FAQ-sectie hoort de hele
 *   categorie te tonen, niet een afgekapte selectie).
 *
 * Alle drie de bronnen monden uit in dezelfde platte `$items`-lijst
 * (`question`/`answer`), zodat de accordion-markup zelf nergens hoeft te
 * weten welke bron erachter zat. Bij "handpicked"/"category" is de vraag de
 * post-titel en het antwoord de post-content (het "faq" CPT heeft alleen
 * title+editor, geen eigen velden — acf-json/post_type_6a7f064bf39a6.json).
 *
 * "Bron" ("faqs_source") is zelf ook een ACF-veld (select), geen block-
 * attribute — zo kan ACF's eigen conditional_logic de drie bron-specifieke
 * velden hieronder een voor een tonen/verbergen in de "FAQ's beheren"-knop
 * i.p.v. alle drie tegelijk (zie editor.js's eigen docblock voor de volle
 * afweging t.o.v. acf/vacatures-uitgelicht).
 *
 * De accordion zelf is bewust GEEN Alpine collapse-plugin (niet geïnstalleerd
 * in dit theme, src/js/packages/alpine.js) maar een pure CSS-grid-truc
 * (`grid-template-rows` 0fr→1fr) — animeert naar "auto" hoogte zonder JS die
 * de content-hoogte moet meten. "open" is lokale x-data per item, geen
 * gedeelde store: elk item hoort onafhankelijk open/dicht te kunnen, anders
 * dan bv. blocks/vacatures' gedeelde $store.vacaturesFilters (dat moet wél
 * over meerdere blocks heen gedeeld state hebben, dit niet).
 */

$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

$title = trim($block['title'] ?? '');

$source = get_field('faqs_source') ?: 'manual';
if (!in_array($source, ['manual', 'handpicked', 'category'], true)) {
    $source = 'manual';
}

$items = [];

if ($source === 'manual') {
    $rows = get_field('faqs_items') ?: [];
    foreach ($rows as $row) {
        $question = trim($row['vraag'] ?? '');
        $answer = $row['antwoord'] ?? '';
        if ($question === '' || $answer === '') {
            continue;
        }
        $items[] = ['question' => $question, 'answer' => $answer];
    }
} elseif ($source === 'handpicked') {
    $selected_ids = array_values(array_filter(array_map('intval', (array) (get_field('faqs_geselecteerd') ?: []))));

    if (!empty($selected_ids)) {
        $query = new WP_Query([
            'post_type' => 'faq',
            'post_status' => 'publish',
            'post__in' => $selected_ids,
            'orderby' => 'post__in',
            'posts_per_page' => count($selected_ids),
        ]);
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = ['question' => get_the_title(), 'answer' => apply_filters('the_content', get_the_content())];
        }
        wp_reset_postdata();
    }
} else {
    $term_id = (int) (get_field('faqs_categorie') ?: 0);

    if ($term_id > 0) {
        $query = new WP_Query([
            'post_type' => 'faq',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'tax_query' => [[
                'taxonomy' => 'faq-categorie',
                'field' => 'term_id',
                'terms' => $term_id,
            ]],
        ]);
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = ['question' => get_the_title(), 'answer' => apply_filters('the_content', get_the_content())];
        }
        wp_reset_postdata();
    }
}

$has_items = !empty($items);

if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

if (!empty($block['style']['color']['text'])) {
    $text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
    $text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
    $text_value = '';
}

// Geen background-color hier zelf: die gaat naar het full-bleed laagje
// hieronder, zelfde reden als acf/proces (CLAUDE.md "Alignment, width and
// full-bleed backgrounds").
$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
$style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim('own-faqs relative ' . $align_class),
    'style' => implode(';', $style_parts),
]);

$block_id = $block['id'] ?? uniqid('faqs_');
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php if ($title !== '') : ?>
            <h2 class="mb-8! sm:mb-10! mt-0!"><?= esc_html($title); ?></h2>
        <?php elseif (!empty($is_preview)) : ?>
            <h2 class="opacity-50 mb-8! sm:mb-10! mt-0!"><?php esc_html_e('Titel…', 'own-framework'); ?></h2>
        <?php endif; ?>

        <?php if (!$has_items) : ?>
            <?php
            get_template_part('template-parts/components/empty-state', null, !empty($is_preview)
                ? ['title' => __('Nog geen FAQ’s voor deze bron', 'own-framework'), 'text' => __('Kies in de blokinstellingen een categorie of selecteer losse vragen.', 'own-framework')]
                : ['title' => __('Nog geen veelgestelde vragen', 'own-framework'), 'text' => __('Vragen en antwoorden verschijnen hier zodra ze zijn toegevoegd.', 'own-framework')]);
            ?>
        <?php else : ?>
            <div class="own-faqs-list divide-y divide-current/15">
                <?php foreach ($items as $index => $item) :
                    $panel_id = 'own-faqs-' . esc_attr($block_id) . '-' . $index;
                ?>
                    <div class="own-faqs-item" x-data="{ open: false }">
                        <h3 class="m-0!">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 py-5 text-left"
                                @click="open = !open"
                                :aria-expanded="open.toString()"
                                aria-controls="<?= $panel_id; ?>">
                                <span class="text-(length:--own-h3-font-size)! leading-(--own-h3-line-height)! font-medium"><?= esc_html($item['question']); ?></span>
                                <span class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }">
                                    <?= ownframework_get_icon('chevron-down', 'w-5 h-5'); ?>
                                </span>
                            </button>
                        </h3>
                        <div
                            id="<?= $panel_id; ?>"
                            class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-in-out"
                            :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="prose max-w-none pb-5"><?= $item['answer']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
