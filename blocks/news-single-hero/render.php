<?php
defined('ABSPATH') || exit();

/**
 * Detailpagina-hero van één nieuwsbericht — broodkruimelpad, titel,
 * publicatiedatum en categorie-badges van DIT bericht. Structureel een kopie
 * van acf/vacature-single-hero (links uitgelijnd, broodkruimelpad standaard
 * AAN, dezelfde kleurattribuut-per-sub-element opzet — CLAUDE.md Traps #7),
 * met één inhoudelijk verschil: geen samenvatting/excerpt hier (die staat al
 * — voluit — in de post-content eronder; een kort stukje ervan nog eens
 * herhalen in de hero voegde niks toe), dus "descriptionColor" heet hier
 * "dateColor" en kleurt in plaats daarvan de publicatiedatum-regel.
 *
 * Badges lezen get_the_category() (native, geen taxonomie-loop zoals
 * vacatures/agenda's eigen dynamisch-ontdekte taxonomieën) — dit CPT filtert/
 * tagt bewust alleen op WordPress' eigen "Categorie", zie blocks/news/hooks/
 * query.php's eigen docblock voor de volledige reden.
 *
 * Alleen insertable op een echte `post` of binnen templates/single.html —
 * hooks/restrict-to-single-post.php.
 *
 * De achtergrondkleur (native "Kleuren"-paneel) loopt tot de volle
 * schermbreedte via ownframework_full_bleed_class()/_background() — zelfde
 * reden als acf/vacature-single-hero's eigen docblock.
 */

$align = $block['align'] ?? 'wide';
$align_class = $align !== '' ? 'align' . $align : '';

if (!empty($block['style']['color']['background'])) {
    $bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
    $bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
    $bg_value = '';
}

$content_align = ($block['contentAlign'] ?? 'left') === 'center' ? 'center' : 'left';
$content_align_class = $content_align === 'center' ? 'mx-auto text-center' : '';

$show_breadcrumbs = ($block['showBreadcrumbs'] ?? true) !== false;

$breadcrumb_color = $block['breadcrumbColor'] ?? '';
$title_color = $block['titleColor'] ?? '';
$date_color = $block['dateColor'] ?? '';
$badge_accent_color = $block['badgeAccentColor'] ?? '';

$style_parts = ['box-sizing:border-box'];
if ($breadcrumb_color !== '') {
    $style_parts[] = '--own-news-single-hero-breadcrumb-color:' . esc_attr($breadcrumb_color);
}
if ($title_color !== '') {
    $style_parts[] = '--own-news-single-hero-title-color:' . esc_attr($title_color);
}
if ($date_color !== '') {
    $style_parts[] = '--own-news-single-hero-date-color:' . esc_attr($date_color);
}
if ($badge_accent_color !== '') {
    $style_parts[] = '--own-news-accent:' . esc_attr($badge_accent_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-news-single-hero py-12 sm:py-16 lg:py-20'),
    'style' => implode(';', $style_parts),
]);

$bleed_background = ownframework_full_bleed_background($bg_value);

$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm text-(--own-news-single-hero-breadcrumb-color)', !empty($is_preview))
    : '';

$post_id = get_the_ID();
$categories = get_the_category($post_id);
?>
<section <?= $wrapper_attributes; ?>>
    <?= $bleed_background; ?>
    <div class="relative z-10 max-w-3xl <?= esc_attr($content_align_class); ?>">
        <?= $breadcrumbs; ?>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold m-0! text-(--own-news-single-hero-title-color)">
            <?= esc_html(get_the_title()); ?>
        </h1>
        <p class="mt-3 text-sm opacity-80 text-(--own-news-single-hero-date-color)">
            <?= esc_html(get_the_date()); ?>
        </p>
        <?php if (!empty($categories)) : ?>
            <div class="mt-4 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' ? 'justify-center' : ''; ?>">
                <?php foreach ($categories as $category) : ?>
                    <span class="own-news-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                        <?= esc_html($category->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
