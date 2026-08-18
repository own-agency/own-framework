<?php
defined('ABSPATH') || exit();

/**
 * Detailpagina-hero van één agenda-moment — broodkruimelpad, titel (met de
 * datum-badge ernaast, zie hieronder) en de categorie-badges van DIT moment.
 * Zelfde opzet als blocks/vacature-single-hero/render.php: standaard links
 * uitgelijnd (geen zoekbalk om content omheen te centreren), broodkruimelpad
 * standaard AAN.
 *
 * Broodkruimelpad/titel/omschrijving/badges hebben elk hun EIGEN kleur-
 * attribute (editor.js) i.p.v. het native "Kleuren"-paneel voor tekst
 * (block.json's supports.color.text staat daarom uit) — zelfde redenering
 * als acf/vacature-single-hero (CLAUDE.md Traps #7). "badgeAccentColor"
 * gebruikt een eigen "--own-agenda-accent" custom property, dezelfde die de
 * archiefpagina's categorie-badges gebruiken (agenda.css). "dateBadgeColor"
 * is een EIGEN, optionele attribute los daarvan — "--own-agenda-date-accent"
 * — zodat een editor de datum-badge een andere kleur kan geven dan de
 * categorie-badges; leeg (de default) valt agenda.css's eigen
 * ".own-agenda-date-badge" gewoon terug op "--own-agenda-accent", dus zonder
 * die keuze blijft het één samenhangende kleur, zelfde mechanisme als
 * blocks/agenda/render.php's eigen "dateBadgeColor".
 *
 * De datum staat als hetzelfde "dag/maand"-vierkant naast de titel als
 * blocks/agenda/parts/card.php's eigen archiefkaarten (zelfde markup/
 * grootte, alleen hergebruikt) — bewust hetzelfde visuele element op beide
 * plekken, in plaats van hier een eigen tekstregel te laten staan zoals
 * vacature-single-hero's "X geleden geplaatst": voor een agenda-moment is
 * WANNEER het plaatsvindt de eerste vraag van een bezoeker, dus verdient het
 * dezelfde direct-herkenbare vorm als op het archief, niet een andere. De
 * kleine tekstregel onder de titel (ownframework_agenda_get_card_caption(),
 * zelfde functie als de kaart) toont alleen wat het vierkant zelf niet kan
 * laten zien (een tijdstip, of een bereik dat een maand/jaar overschrijdt) —
 * nooit gewoon dezelfde datum nog een keer.
 *
 * Alleen insertable binnen templates/single-agenda.html — hooks/
 * restrict-to-single-agenda-template.php.
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
$description_color = $block['descriptionColor'] ?? '';
$badge_accent_color = $block['badgeAccentColor'] ?? '';
$date_badge_color = $block['dateBadgeColor'] ?? '';

$style_parts = ['box-sizing:border-box'];
if ($breadcrumb_color !== '') {
    $style_parts[] = '--own-agenda-single-hero-breadcrumb-color:' . esc_attr($breadcrumb_color);
}
if ($title_color !== '') {
    $style_parts[] = '--own-agenda-single-hero-title-color:' . esc_attr($title_color);
}
if ($description_color !== '') {
    $style_parts[] = '--own-agenda-single-hero-description-color:' . esc_attr($description_color);
}
if ($badge_accent_color !== '') {
    $style_parts[] = '--own-agenda-accent:' . esc_attr($badge_accent_color);
}
if ($date_badge_color !== '') {
    $style_parts[] = '--own-agenda-date-accent:' . esc_attr($date_badge_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-agenda-single-hero py-12 sm:py-16 lg:py-20'),
    'style' => implode(';', $style_parts),
]);

$bleed_background = ownframework_full_bleed_background($bg_value);

$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm text-(--own-agenda-single-hero-breadcrumb-color)', !empty($is_preview))
    : '';

$post_id = get_the_ID();

$badges = [];
foreach (ownframework_agenda_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms($post_id, $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    foreach ($terms as $term) {
        $badges[] = $term->name;
    }
}
if (ownframework_agenda_is_multi_day($post_id)) {
    array_unshift($badges, __('Meerdaags', 'own-framework'));
}

// Same "day / month" square as blocks/agenda/parts/card.php's own date
// badge — reused as-is (identical markup/size/colour mechanism, not scaled
// up to the hero's larger type) so the archive card and this detail page
// read as the same design language rather than two different date
// treatments. ownframework_agenda_get_card_caption() applies the same
// "only add what the badge can't show" rule as the card: a plain all-day
// date needs nothing else, a timed moment gets its time, a moment crossing
// a month/year gets the full range.
$date_badge = ownframework_agenda_get_date_badge($post_id);
$date_caption = ownframework_agenda_get_card_caption($post_id, $date_badge);
?>
<section <?= $wrapper_attributes; ?>>
    <?= $bleed_background; ?>
    <div class="relative z-10 max-w-3xl <?= esc_attr($content_align_class); ?>">
        <?= $breadcrumbs; ?>
        <div class="flex items-start gap-4 sm:gap-5 <?= $content_align === 'center' ? 'justify-center text-center' : 'text-left'; ?>">
            <?php if ($date_badge['day'] !== '') : ?>
                <time
                    datetime="<?= esc_attr($date_badge['iso']); ?>"
                    class="own-agenda-date-badge shrink-0 flex flex-col items-center justify-center rounded-xl min-w-16 h-16 sm:min-w-18 sm:h-18 px-2 text-center">
                    <span class="own-agenda-date-badge__day <?= $date_badge['range'] ? 'text-base sm:text-lg' : 'text-xl sm:text-2xl'; ?> font-bold leading-none">
                        <?= esc_html($date_badge['day']); ?>
                    </span>
                    <span class="own-agenda-date-badge__month text-[0.65rem] sm:text-xs font-semibold uppercase tracking-wide mt-1 leading-none">
                        <?= esc_html($date_badge['month']); ?>
                    </span>
                </time>
            <?php endif; ?>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold m-0! text-(--own-agenda-single-hero-title-color)">
                    <?= esc_html(get_the_title()); ?>
                </h1>
                <?php if ($date_caption !== '') : ?>
                    <p class="mt-1 text-sm font-medium m-0! text-(--own-agenda-accent)">
                        <?= esc_html($date_caption); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php if (has_excerpt()) : ?>
            <div class="mt-3 text-sm sm:text-base leading-relaxed opacity-80 text-(--own-agenda-single-hero-description-color)">
                <?= wp_kses_post(get_the_excerpt()); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($badges)) : ?>
            <div class="mt-5 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' ? 'justify-center' : ''; ?>">
                <?php foreach ($badges as $badge) : ?>
                    <span class="own-agenda-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                        <?= esc_html($badge); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
