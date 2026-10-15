<?php
defined('ABSPATH') || exit();

/**
 * Nieuws-sectie voor gewone pagina's — een raster of slider van
 * nieuwsberichten, óf de meest recente óf een handmatig gekozen selectie.
 * Bedoeld als het "laat wat nieuws zien"-block buiten templates/home.html om
 * (die heeft z'n eigen, volledig filterbare acf/news) — dit block is bewust
 * simpel: geen filters, geen paginering, gewoon een vaste selectie.
 * Structureel een 1-op-1 kopie van acf/vacatures-uitgelicht/acf/
 * agenda-uitgelicht, hier toegepast op de NATIVE "post" post type — zelfde
 * "hergebruik wat WordPress al biedt"-keuze als blocks/news/hooks/query.php's
 * eigen docblock beschrijft voor acf/news.
 *
 * GEEN eigen InnerBlocks-intro (titel/tekst/knop) meer — zie acf/
 * vacatures-uitgelicht/render.php's eigen docblock voor de volle reden.
 * patterns/news-uitgelicht-intro.php is het vervangende, los plaatsbare
 * titel/tekst/knop-startpunt.
 *
 * "Bron"/"Aantal"/"Weergave"/"Accentkleur" zijn plain block-attributes +
 * editor.js, zoals ieder ander block in dit theme. De handmatige selectie
 * zelf is de vertrouwde uitzondering hierop (net als bij vacatures/agenda):
 * een echt ACF-veld (type "relationship", acf-json/group_own_news_
 * uitgelicht_selectie.json, location "block == acf/news-uitgelicht") i.p.v.
 * een zelfgebouwde React-picker. get_field() hier ZONDER post-ID is met
 * opzet correct: dit veld hoort bij het BLOK zelf (location "block =="), dus
 * ACF's block-render-context resolvet 'm naar de juiste plek (CLAUDE.md
 * Traps #27's eigen mirror-case).
 *
 * Kaarten worden hergebruikt via get_template_part('blocks/news/parts/
 * card') — dezelfde markup als de archiefpagina (incl. uitgelichte
 * afbeelding, categorie-badges en tags), contextonafhankelijk zolang
 * the_post() al gedraaid heeft. Kleurvariabelen (--own-news-card-*) vallen
 * hier terug op news.css' eigen :root-defaults, behalve "cardLinkColor"
 * (eigen attribute + "Kleuren"-paneel) — bewust LOS van "accentColor"
 * (badges), zelfde reden als acf/vacatures-uitgelicht's eigen scheiding.
 *
 * Geen CPT-archief-link voor "post" (in tegenstelling tot vacancy/agenda,
 * die wél get_post_type_archive_link() hebben) — de "Berichtenpagina"-
 * instelling (Instellingen → Lezen) is hier de juiste bron, zelfde
 * resolutie als blocks/news/hooks/query.php's eigen wp_enqueue_scripts-hook
 * (OwnNews.archiveUrl): page_for_posts, met home_url() als fallback wanneer
 * er geen aparte berichtenpagina is ingesteld.
 *
 * De vorige/volgende-knoppen staan rechts uitgelijnd BOVEN de slider, in hun
 * eigen rij — sinds de intro hiernaast verdween is er niets meer om ze een
 * rij mee te delen, zelfde als acf/vacatures-uitgelicht's eigen docblock.
 */

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$source = ($block['source'] ?? 'recent') === 'handpicked' ? 'handpicked' : 'recent';
$count = max(1, (int) ($block['count'] ?? 3));
$layout = ($block['layout'] ?? 'grid') === 'slider' ? 'slider' : 'grid';
$accent = $block['accentColor'] ?? '';
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
    $style_parts[] = '--own-news-accent:' . esc_attr($accent);
}
if ($card_link_color !== '') {
    $style_parts[] = '--own-news-card-link-color:' . esc_attr($card_link_color);
}
$style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-news-uitgelicht'),
    'style' => implode(';', $style_parts),
]);

if ($source === 'handpicked') {
    $selected_ids = array_values(array_filter(array_map('intval', (array) (get_field('geselecteerde_berichten') ?: []))));

    $query = new WP_Query(empty($selected_ids) ? ['post__in' => [0]] : [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post__in' => $selected_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($selected_ids),
    ]);
} else {
    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
}

$has_results = $query->post_count > 0;

ob_start();
if ($has_results) {
    while ($query->have_posts()) {
        $query->the_post();
        if ($layout === 'slider') {
            // Equal-height cards: src/css/components/swiper.css's own
            // ".own-news-uitgelicht .swiper-slide { display: flex }" (this
            // section's own wrapper class) is what stretches the card to
            // match its tallest sibling — see blocks/news/parts/card.php's
            // own docblock for why that's a flex-child stretch, not a
            // percentage height on the card. No "h-auto" override here: that
            // would force this slide's own height back to its content size
            // (Tailwind's "!" wins over any stretch, important or not),
            // undoing the equal-height effect entirely.
            echo '<div class="swiper-slide h-auto!">';
        }
        get_template_part('blocks/news/parts/card', null, $card_args);
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
                <div role="button" tabindex="0" class="own-news-uitgelicht-prev flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                    <svg class="m-auto h-4 w-4 rotate-180 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
                <div role="button" tabindex="0" class="own-news-uitgelicht-next flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
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
                ? ['title' => __('Nog niets geselecteerd', 'own-framework'), 'text' => __('Kies in de blokinstellingen welke berichten je hier wilt tonen.', 'own-framework')]
                : ['title' => __('Nog geen nieuws', 'own-framework'), 'text' => __('Nieuwe berichten verschijnen hier zodra ze gepubliceerd zijn.', 'own-framework')];
            get_template_part('template-parts/components/empty-state', null, $empty);
            ?>
        <?php elseif ($layout === 'slider') : ?>
            <div class="swiper overflow-visible! select-none" data-own-news-uitgelicht-slider>
                <div class="swiper-wrapper">
                    <?= $cards_html; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-news-uitgelicht-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?= $cards_html; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
