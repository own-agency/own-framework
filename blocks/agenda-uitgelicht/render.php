<?php
defined('ABSPATH') || exit();

/**
 * Agenda-sectie voor gewone pagina's — een intro (InnerBlocks: titel/tekst/
 * knop) plus een raster of slider van agenda-momenten, óf de eerstvolgende
 * óf een handmatig gekozen selectie. Structurele 1:1-poort van blocks/
 * vacatures-uitgelicht/render.php (zelfde intro/navigatie/paginatie-opzet,
 * zelfde reden om dit als apart, simpel "laat wat momenten zien"-block te
 * bouwen naast het volledig filterbare acf/agenda) — zie dat bestand's eigen
 * docblock voor de volledige redenering achter elk stuk markup hieronder.
 *
 * Twee inhoudelijke afwijkingen, geen kopieerfout:
 * - "Bron" heet hier "upcoming" i.p.v. "recent": acf/vacatures-uitgelicht's
 *   "meest recent GEPUBLICEERD" (orderby date) is voor een kalender zinloos
 *   — een editor wil hier de eerstvolgende, nog niet verlopen momenten zien,
 *   dus dit hergebruikt blocks/agenda/hooks/query.php's eigen
 *   "agenda_sort_start"/"agenda_sort_end" meta (ownframework_agenda_build_
 *   periode_meta_query('aankomend'), dezelfde functie die de archiefpagina's
 *   "Periode"-filter ook gebruikt) i.p.v. WP_Query's standaard "date"-sort.
 * - De kaart zelf is blocks/agenda/parts/card.php (met zijn eigen "dag/
 *   maand"-datumbadge), dus dit block heeft ook zijn eigen "dateBadgeColor"
 *   attribute naast "accentColor" — exact dezelfde scheiding als acf/agenda/
 *   acf/agenda-single-hero al hebben, hier voor consistentie hergebruikt.
 *
 * Alles verder — kleurvariabelen, InnerBlocks-intro, navigatie-/paginatie-
 * knoppen, editor- vs. front-end-slidermarkup — is bewust identiek aan
 * acf/vacatures-uitgelicht, alleen de klassennamen zijn "own-agenda-
 * uitgelicht" i.p.v. "own-vacatures-uitgelicht" (src/css/components/
 * swiper.css en src/js/scripts/agenda-uitgelicht-slider.js's eigen "base").
 */

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$source = ($block['source'] ?? 'upcoming') === 'handpicked' ? 'handpicked' : 'upcoming';
$count = max(1, (int) ($block['count'] ?? 3));
$layout = ($block['layout'] ?? 'grid') === 'slider' ? 'slider' : 'grid';
$accent = $block['accentColor'] ?? '';
$date_badge_color = $block['dateBadgeColor'] ?? '';
$card_link_color = $block['cardLinkColor'] ?? '';
$nav_color = $block['navColor'] ?: 'var(--wp--preset--color--primary)';
$show_pagination = !empty($block['showPagination']);
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';

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

$style_parts = [];
if ($text_value !== '') {
    $style_parts[] = 'color:' . esc_attr($text_value);
}
if ($accent !== '') {
    $style_parts[] = '--own-agenda-accent:' . esc_attr($accent);
}
if ($date_badge_color !== '') {
    $style_parts[] = '--own-agenda-date-accent:' . esc_attr($date_badge_color);
}
if ($card_link_color !== '') {
    $style_parts[] = '--own-agenda-card-link-color:' . esc_attr($card_link_color);
}
$style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-agenda-uitgelicht'),
    'style' => implode(';', $style_parts),
]);

if ($source === 'handpicked') {
    $selected_ids = array_values(array_filter(array_map('intval', (array) (get_field('geselecteerde_momenten') ?: []))));

    $query = new WP_Query(empty($selected_ids) ? ['post__in' => [0]] : [
        'post_type' => OWNFRAMEWORK_AGENDA_POST_TYPE,
        'post_status' => 'publish',
        'post__in' => $selected_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($selected_ids),
    ]);
} else {
    $query = new WP_Query([
        'post_type' => OWNFRAMEWORK_AGENDA_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'meta_key' => 'agenda_sort_start',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => ownframework_agenda_build_periode_meta_query('aankomend'),
    ]);
}

$has_results = $query->post_count > 0;

ob_start();
if ($has_results) {
    while ($query->have_posts()) {
        $query->the_post();
        if ($layout === 'slider') {
            // Equal-height cards: src/css/components/swiper.css's own
            // ".own-agenda-uitgelicht .swiper-slide { display: flex }" is
            // what stretches the card to match its tallest sibling — see
            // blocks/vacatures-uitgelicht/render.php's own docblock for the
            // full reasoning (identical mechanism, ported class name).
            echo '<div class="swiper-slide h-auto!">';
        }
        get_template_part('blocks/agenda/parts/card');
        if ($layout === 'slider') {
            echo '</div>';
        }
    }
}
wp_reset_postdata();
$cards_html = ob_get_clean();

$archive_url = get_post_type_archive_link(OWNFRAMEWORK_AGENDA_POST_TYPE) ?: '';

$inner_blocks_template = wp_json_encode([
    ['core/heading', ['level' => 2, 'placeholder' => __('Agenda', 'own-framework')]],
    ['core/paragraph', ['placeholder' => __('Bekijk de eerstvolgende momenten in onze agenda.', 'own-framework')]],
    ['core/buttons', (object) [], [
        ['core/button', ['text' => __('Bekijk de hele agenda', 'own-framework'), 'url' => $archive_url]],
    ]],
]);

$inner_blocks = '<InnerBlocks template=\'' . $inner_blocks_template . '\' '
    . 'allowedBlocks=\'["core/heading","core/paragraph","core/buttons"]\' '
    . 'templateLock=\'false\' '
    . 'className=\'own-agenda-uitgelicht-intro [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0! max-w-2xl\' />';

$bleed_class = ownframework_full_bleed_class();
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <?= $inner_blocks; ?>
            <?php if ($has_results && $layout === 'slider') : ?>
                <div class="mt-auto flex shrink-0 items-center gap-2 [&_.swiper-button-disabled]:opacity-50">
                    <div role="button" tabindex="0" class="own-agenda-uitgelicht-prev flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                        <svg class="m-auto h-4 w-4 rotate-180 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                        </svg>
                    </div>
                    <div role="button" tabindex="0" class="own-agenda-uitgelicht-next flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                        <svg class="m-auto h-4 w-4 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$has_results) : ?>
            <p class="text-center text-sm"><?php esc_html_e('Er zijn op dit moment geen agenda-momenten om te tonen.', 'own-framework'); ?></p>
        <?php elseif ($layout === 'slider') : ?>
            <div class="swiper overflow-visible! select-none" data-own-agenda-uitgelicht-slider>
                <div class="swiper-wrapper">
                    <?= $cards_html; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-agenda-uitgelicht-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?= $cards_html; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
