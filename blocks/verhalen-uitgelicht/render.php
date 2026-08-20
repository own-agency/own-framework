<?php
defined('ABSPATH') || exit();

/**
 * Verhalen-sectie voor gewone pagina's — een intro (InnerBlocks: titel/tekst/
 * knop) plus een raster of slider van verhalen, óf de meest recente óf een
 * handmatig gekozen selectie. Bedoeld als het "laat wat verhalen zien"-block
 * buiten templates/archive-verhaal.html om (die heeft zijn eigen, volledig
 * filterbare acf/verhalen) — dit block is bewust simpel: geen filters, geen
 * paginering, gewoon een vaste selectie. Structureel een 1-op-1 kopie van
 * acf/vacatures-uitgelicht/acf/agenda-uitgelicht/acf/news-uitgelicht.
 *
 * "Bron"/"Aantal"/"Weergave" zijn plain block-attributes + editor.js, zoals
 * ieder ander block in dit theme. De handmatige selectie zelf is de
 * vertrouwde uitzondering hierop (net als bij vacatures/agenda/nieuws): een
 * echt ACF-veld (type "relationship", acf-json/group_own_verhalen_uitgelicht_
 * selectie.json, location "block == acf/verhalen-uitgelicht") i.p.v. een
 * zelfgebouwde React-picker. get_field() hier ZONDER post-ID is met opzet
 * correct: dit veld hoort bij het BLOK zelf (location "block =="), dus ACF's
 * block-render-context resolvet 'm naar de juiste plek (CLAUDE.md Traps
 * #27's eigen mirror-case).
 *
 * Kaarten worden hergebruikt via get_template_part('blocks/verhalen/parts/
 * card') — dezelfde portretkaart als de archiefpagina (foto/quote/datum/
 * link, incl. de fallback-afbeelding/-kleur voor een verhaal zonder eigen
 * foto), contextonafhankelijk zolang the_post() al gedraaid heeft. Die
 * kaart-template leest zijn fallback-afbeelding altijd via
 * ownframework_verhalen_get_configured_fallback_image() (blocks/verhalen/
 * hooks/query.php) — d.w.z. WELKE fallback-foto hier verschijnt, is dus altijd
 * degene die op de verhalen-archiefpagina zelf is ingesteld (acf/verhalen's
 * eigen "fallbackImage"), niet een aparte instelling van dít block. Bewust
 * geen eigen "accentColor" (zoals acf/news-uitgelicht/acf/vacatures-uitgelicht
 * wél hebben, voor hun kaart-badges) — blocks/verhalen/parts/card.php's kaart
 * heeft geen badges, alleen titel/quote/datum/link over de foto, dus alleen
 * "cardTextColor" (dezelfde CSS custom property als acf/verhalen's eigen
 * attribuut, hier op DIT block's eigen wrapper gezet) is relevant.
 *
 * De vorige/volgende-knoppen staan NAAST de intro (dezelfde rij, rechts
 * uitgelijnd), boven de slider — exact dezelfde layout-reden als acf/
 * vacatures-uitgelicht's eigen docblock.
 *
 * Raster op 1/2/3 kolommen (nooit 4), zelfde grens als acf/verhalen's eigen
 * archiefraster — portretkaarten in een vierde kolom zouden te smal/hoog
 * ogen. Geen "swiper-slide"-hoogtetruc nodig in Slider-weergave (in
 * tegenstelling tot acf/vacatures-uitgelicht/acf/agenda-uitgelicht/acf/
 * news-uitgelicht, die elk hun eigen ".own-*-uitgelicht .swiper-slide {
 * display:flex }" hebben, src/css/components/swiper.css): die kaarten hebben
 * een VARIABELE hoogte (titel+tekst+badges) die met een percentage-hoogte
 * niet vanzelf gelijk zou blijven (CLAUDE.md Traps #30), maar deze kaart
 * heeft een VASTE beeldverhouding ("aspect-[3/4]") — de hoogte volgt daardoor
 * altijd al automatisch uit de (bij gelijke "slidesPerView" altijd gelijke)
 * breedte, dus elke slide is al even hoog zonder enige extra CSS.
 */

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

$source = ($block['source'] ?? 'recent') === 'handpicked' ? 'handpicked' : 'recent';
$count = max(1, (int) ($block['count'] ?? 3));
$layout = ($block['layout'] ?? 'grid') === 'slider' ? 'slider' : 'grid';
$card_text_color = $block['cardTextColor'] ?? '';
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
if ($card_text_color !== '') {
    $style_parts[] = '--own-verhalen-card-text-color:' . esc_attr($card_text_color);
}
$style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-verhalen-uitgelicht'),
    'style' => implode(';', $style_parts),
]);

if ($source === 'handpicked') {
    $selected_ids = array_values(array_filter(array_map('intval', (array) (get_field('geselecteerde_verhalen') ?: []))));

    $query = new WP_Query(empty($selected_ids) ? ['post__in' => [0]] : [
        'post_type' => 'verhaal',
        'post_status' => 'publish',
        'post__in' => $selected_ids,
        'orderby' => 'post__in',
        'posts_per_page' => count($selected_ids),
    ]);
} else {
    $query = new WP_Query([
        'post_type' => 'verhaal',
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
            echo '<div class="swiper-slide">';
        }
        get_template_part('blocks/verhalen/parts/card');
        if ($layout === 'slider') {
            echo '</div>';
        }
    }
}
wp_reset_postdata();
$cards_html = ob_get_clean();

$archive_url = get_post_type_archive_link('verhaal') ?: '';

$inner_blocks_template = wp_json_encode([
    ['core/heading', ['level' => 2, 'placeholder' => __('Uitgelichte verhalen', 'own-framework')]],
    ['core/paragraph', ['placeholder' => __('Lees de verhalen achter ons werk.', 'own-framework')]],
    ['core/buttons', (object) [], [
        ['core/button', ['text' => __('Bekijk alle verhalen', 'own-framework'), 'url' => $archive_url]],
    ]],
]);

$inner_blocks = '<InnerBlocks template=\'' . $inner_blocks_template . '\' '
    . 'allowedBlocks=\'["core/heading","core/paragraph","core/buttons"]\' '
    . 'templateLock=\'false\' '
    . 'className=\'own-verhalen-uitgelicht-intro [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0! max-w-2xl\' />';
?>
<section <?= $wrapper_attributes; ?>>
    <?= ownframework_full_bleed_background($bg_value); ?>
    <div class="relative z-10">
        <?php // Intro links, vorige/volgende-knoppen rechts ernaast — zelfde
        // rij, niet de intro erboven en de knoppen op een eigen regel eronder
        // (dat zou de intro als los blok BOVEN de navigatie laten ogen i.p.v.
        // ernaast). "items-start": de intro kan meerdere regels beslaan (titel
        // + tekst + knop), de navigatieknoppen blijven dan bovenaan uitgelijnd
        // i.p.v. verticaal gecentreerd tegen de volle hoogte.
        ?>
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <?= $inner_blocks; ?>
            <?php if ($has_results && $layout === 'slider') : ?>
                <div class="mt-auto flex shrink-0 items-center gap-2 [&_.swiper-button-disabled]:opacity-50">
                    <div role="button" tabindex="0" class="own-verhalen-uitgelicht-prev flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Vorige', 'own-framework'); ?>">
                        <svg class="m-auto h-4 w-4 rotate-180 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                        </svg>
                    </div>
                    <div role="button" tabindex="0" class="own-verhalen-uitgelicht-next flex h-9 w-9 cursor-pointer rounded-full sm:h-10 sm:w-10" style="background-color:<?= esc_attr($nav_color); ?>;" aria-label="<?php esc_attr_e('Volgende', 'own-framework'); ?>">
                        <svg class="m-auto h-4 w-4 text-white sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M14 6L20 12L14 18M19 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="square" />
                        </svg>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$has_results) : ?>
            <p class="text-center text-sm"><?php esc_html_e('Er zijn op dit moment geen verhalen om te tonen.', 'own-framework'); ?></p>
        <?php elseif ($layout === 'slider') : ?>
            <div class="swiper overflow-visible! select-none" data-own-verhalen-uitgelicht-slider>
                <div class="swiper-wrapper">
                    <?= $cards_html; ?>
                </div>
                <?php if ($show_pagination) : ?>
                    <div class="own-verhalen-uitgelicht-pagination mt-2 w-full text-center" style="--own-pagination-color:<?= esc_attr($pagination_color); ?>;"></div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?= $cards_html; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
