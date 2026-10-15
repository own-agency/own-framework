<?php
defined('ABSPATH') || exit();

/**
 * Agenda-sectie voor gewone pagina's — een raster of slider van
 * agenda-momenten, óf de eerstvolgende óf een handmatig gekozen selectie.
 * Structurele 1:1-poort van blocks/vacatures-uitgelicht/render.php (zelfde
 * navigatie-/paginatie-opzet, zelfde reden om dit als apart, simpel "laat wat
 * momenten zien"-block te bouwen naast het volledig filterbare acf/agenda) —
 * zie dat bestand's eigen docblock voor de volledige redenering achter elk
 * stuk markup hieronder, inclusief WAAROM er geen eigen InnerBlocks-intro
 * meer in dit block zit (patterns/agenda-uitgelicht-intro.php in plaats
 * daarvan).
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
 * Alles verder — kleurvariabelen, navigatie-/paginatie-knoppen, editor- vs.
 * front-end-slidermarkup — is bewust identiek aan acf/vacatures-uitgelicht,
 * alleen de klassennamen zijn "own-agenda-uitgelicht" i.p.v.
 * "own-vacatures-uitgelicht" (src/css/components/swiper.css en
 * src/js/scripts/agenda-uitgelicht-slider.js's eigen "base").
 */

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$source = ($block['source'] ?? 'upcoming') === 'handpicked' ? 'handpicked' : 'upcoming';
$count = max(1, (int) ($block['count'] ?? 3));
$layout = ($block['layout'] ?? 'grid') === 'slider' ? 'slider' : 'grid';
$columns = (int) ($block['columns'] ?? 3);
if (!in_array($columns, [1, 2, 3], true)) {
    $columns = 3;
}
// Standaard true: zo bleef de slider zich gedragen vóór deze instelling
// bestond (kaarten lopen door tot de schermrand). Uit = binnen de eigen
// kolom/container blijven, bv. in een 66/33-kolommenblok.
$slider_bleed = $block['sliderBleed'] ?? true;

// "Aantal naast elkaar" is een maximum, geen vast aantal: de stappen hangen
// aan de breedte van het BLOCK zelf (Tailwind-containerqueries, "@container"
// op de wrapper hieronder) i.p.v. de viewport. Zo valt een raster van 3 in
// een smalle 33%-kolom vanzelf terug naar 1 kolom. Volledige klassennamen
// per waarde, zodat Tailwinds scanner ze in dit bestand terugvindt. De
// slider doet hetzelfde via Swiper's breakpointsBase: "container" (src/js/
// scripts/agenda-uitgelicht-slider.js), met dezelfde 36rem/56rem-stappen.
$grid_cols_class = [
    1 => 'grid-cols-1',
    2 => 'grid-cols-1 @xl:grid-cols-2',
    3 => 'grid-cols-1 @xl:grid-cols-2 @4xl:grid-cols-3',
][$columns];
$accent = $block['accentColor'] ?? '';
$date_badge_color = $block['dateBadgeColor'] ?? '';
$card_link_color = $block['cardLinkColor'] ?? '';
$nav_color = $block['navColor'] ?: 'var(--wp--preset--color--primary)';
$show_pagination = !empty($block['showPagination']);
$pagination_color = $block['paginationColor'] ?: 'var(--wp--preset--color--primary)';

// parts/card.php's eigen $args['title_level'] — zie acf/vacatures-uitgelicht/
// render.php's identieke $card_args voor de volle reden.
$card_args = ['title_level' => (int) ($block['cardTitleLevel'] ?? 3)];

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
    'class' => trim($align_class . ' relative @container own-agenda-uitgelicht'),
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
        get_template_part('blocks/agenda/parts/card', null, $card_args);
        if ($layout === 'slider') {
            echo '</div>';
        }
    }
}
wp_reset_postdata();
$cards_html = ob_get_clean();

$bleed_class = ownframework_full_bleed_class();
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php if ($has_results && $layout === 'slider') : ?>
            <div class="mb-8 flex items-center justify-end gap-2 [&_.swiper-button-disabled]:opacity-50">
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

        <?php if (!$has_results) : ?>
            <?php
            // Handmatige selectie zonder gekozen items: in de editor een hint
            // waar je ze kiest; op de site gewoon de lege staat.
            $empty = !empty($is_preview) && $source === 'handpicked'
                ? ['title' => __('Nog niets geselecteerd', 'own-framework'), 'text' => __('Kies in de blokinstellingen welke momenten je hier wilt tonen.', 'own-framework')]
                : ['title' => __('Er staan geen momenten gepland', 'own-framework'), 'text' => __('Nieuwe momenten verschijnen hier zodra ze in de agenda staan.', 'own-framework')];
            get_template_part('template-parts/components/empty-state', null, $empty);
            ?>
        <?php elseif ($layout === 'slider') : ?>
            <div class="swiper <?= $slider_bleed ? 'overflow-visible!' : ''; ?> select-none" data-own-agenda-uitgelicht-slider="<?= esc_attr((string) $columns); ?>">
                <div class="swiper-wrapper">
                    <?= $cards_html; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-agenda-uitgelicht-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="grid gap-6 <?= esc_attr($grid_cols_class); ?>">
                <?= $cards_html; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
