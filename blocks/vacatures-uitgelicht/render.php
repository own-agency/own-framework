<?php
defined('ABSPATH') || exit();

/**
 * Vacatures-sectie voor gewone pagina's — een raster of slider van
 * vacatures, óf de meest recente óf een handmatig gekozen selectie. Bedoeld
 * als het "laat wat vacatures zien"-block buiten templates/
 * archive-vacancy.html/single-vacancy.html om (die twee hebben hun eigen,
 * volledig filterbare acf/vacatures resp. acf/vacature-apply) — dit block is
 * bewust simpel: geen filters, geen paginering, gewoon een vaste selectie.
 *
 * GEEN eigen InnerBlocks-intro (titel/tekst/knop) meer — dat zat hiervoor
 * hardgecodeerd in dit block (vaste volgorde/uitlijning, altijd links van de
 * navigatieknoppen), wat een editor nooit kon herschikken (knop rechtsboven
 * i.p.v. onder de tekst, intro in een aparte kolom naast de resultaten, etc.)
 * — exact wat core/group/core/columns wél toestaan voor gewone content. De
 * intro is nu patterns/vacatures-uitgelicht-intro.php: gewone, losse
 * heading/paragraph/buttons-blocks vóór dit block, door een editor vrij te
 * verplaatsen, herschikken of in een core/columns te zetten — dit block zelf
 * rendert alleen nog de resultaten. Bestaande pagina's die de oude, geneste
 * intro al bevatten blijven ongewijzigd totdat iemand dat block opnieuw
 * opslaat (dan verdwijnt de niet meer ondersteunde InnerBlocks-content).
 *
 * "Bron"/"Aantal"/"Weergave"/"Accentkleur" zijn plain block-attributes +
 * editor.js, zoals ieder ander block in dit theme. De handmatige selectie
 * zelf is de EERSTE uitzondering hierop in dit theme: een echt ACF-veld
 * (type "relationship", acf-json/group_own_vacatures_uitgelicht_selectie.
 * json, location "block == acf/vacatures-uitgelicht") in plaats van een
 * zelfgebouwde React-picker — ACF's eigen zoek/sleep-UI is voor "kies een
 * paar posts uit een lijst" simpelweg al kant-en-klaar en gepolijst, een
 * eigen versie bouwen zou dat alleen slechter opnieuw uitvinden. get_field()
 * hier ZONDER post-ID is met opzet correct or (niet de valkuil van blocks/
 * vacatures/parts/card.php's eigen "logo"-veld): dit veld hoort bij het BLOK
 * zelf (location "block =="), dus ACF's block-render-context resolvet 'm
 * precies naar de juiste plek.
 *
 * Kaarten worden hergebruikt via get_template_part('blocks/vacatures/parts/
 * card') — dezelfde markup als de archiefpagina, contextonafhankelijk zolang
 * the_post() al gedraaid heeft (zie dat bestand se eigen docblock). Kleur-
 * variabelen (--own-vacatures-card-*) vallen hier terug op vacatures.css'
 * eigen :root-defaults, behalve "cardLinkColor" (eigen attribute + "Kleuren"-
 * paneel, editor.js) — bewust LOS van "accentColor" (badges), exact dezelfde
 * reden als acf/vacatures' eigen cardLinkColor/accentColor-scheiding: zo kan
 * de link een andere kleur krijgen dan de badges, zonder dat de een de ander
 * meesleept.
 *
 * De vorige/volgende-knoppen staan rechts uitgelijnd BOVEN de slider, in hun
 * eigen rij — sinds de intro hiernaast verdween (zie hierboven) is er niets
 * meer om ze een rij mee te delen.
 *
 * De paginatie-bolletjes ("Paginatie tonen", editor.js) volgen het
 * gebruikelijke patroon (own-vacatures-uitgelicht-pagination, src/css/
 * components/swiper.css) en blijven bij de slider zelf.
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

// parts/card.php's eigen $args['title_level'] — H3 default (blocks/vacatures/
// parts/card.php's docblock), ondergeschikt aan patterns/vacatures-uitgelicht-
// intro.php's eigen H2.
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
    $style_parts[] = '--own-vacatures-accent:' . esc_attr($accent);
}
if ($card_link_color !== '') {
    $style_parts[] = '--own-vacatures-card-link-color:' . esc_attr($card_link_color);
}
$style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-vacatures-uitgelicht'),
    'style' => implode(';', $style_parts),
]);

if ($source === 'handpicked') {
    $selected_ids = array_values(array_filter(array_map('intval', (array) (get_field('geselecteerde_vacatures') ?: []))));

    $query = new WP_Query(empty($selected_ids) ? ['post__in' => [0]] : [
        'post_type' => 'vacancy',
        'post_status' => 'publish',
        'post__in' => $selected_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($selected_ids),
    ]);
} else {
    $query = new WP_Query([
        'post_type' => 'vacancy',
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
            // ".own-vacatures-uitgelicht .swiper-slide { display: flex }"
            // (this section's own wrapper class) is what stretches the card
            // to match its tallest sibling, same recipe as
            // ".own-content-slider .swiper-slide" there — see parts/card.php's
            // own docblock for why that's a flex-child stretch, not a
            // percentage height on the card. No "h-auto" override here: that
            // would force this slide's own height back to its content size
            // (Tailwind's "!" wins over any stretch, important or not),
            // undoing the equal-height effect entirely.
            echo '<div class="swiper-slide h-auto!">';
        }
        get_template_part('blocks/vacatures/parts/card', null, $card_args);
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
                <div role="button" tabindex="0" class="own-vacatures-uitgelicht-prev flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                    <svg class="m-auto h-4 w-4 rotate-180 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
                <div role="button" tabindex="0" class="own-vacatures-uitgelicht-next flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                    <svg class="m-auto h-4 w-4 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                    </svg>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$has_results) : ?>
            <p class="text-center text-sm"><?php esc_html_e('Er zijn op dit moment geen vacatures om te tonen.', 'own-framework'); ?></p>
        <?php elseif ($layout === 'slider') : ?>
            <div class="swiper overflow-visible! select-none" data-own-vacatures-uitgelicht-slider>
                <div class="swiper-wrapper">
                    <?= $cards_html; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-vacatures-uitgelicht-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?= $cards_html; ?>
            </div>
        <?php endif; ?>
    </div>
</section>