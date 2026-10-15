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
 * De datum staat als vierkant naast de titel, zelfde plek, formaat
 * (min-w-16/h-16) EN kleurmechaniek als blocks/agenda/parts/card.php's eigen
 * archiefkaart-badge — bewust hetzelfde visuele element op beide plekken,
 * niet vergroot voor de hero: dezelfde direct-herkenbare vorm hoort er
 * hetzelfde uit te zien, of je 'm nu op het archief of op de detailpagina
 * ziet. Het jaartal staat BINNEN hetzelfde vak (maand erboven, dag ertussenin
 * groot, jaartal eronder, alle drie met dezelfde geërfde accentkleur via
 * "currentColor"/opacity, i.p.v. het jaartal los een hardcoded grijstint te
 * geven) i.p.v. als los, ongekaderd tekstregeltje ONDER het vierkant — zelfde
 * herziening als op de kaart, hier voor het eerst doorgevoerd en vandaar ook
 * naar de kaart teruggeport. In plaats van hier een eigen tekstregel te laten
 * staan zoals vacature-single-hero's "X geleden geplaatst": voor een
 * agenda-moment is WANNEER het plaatsvindt de eerste vraag van een bezoeker,
 * dus verdient het een direct-herkenbare datumvorm.
 * De kleine tekstregel onder de titel (ownframework_agenda_get_card_caption(),
 * zelfde functie als de kaart) toont alleen wat het vierkant zelf niet kan
 * laten zien (een tijdstip, of een bereik dat een maand/jaar overschrijdt) —
 * nooit gewoon dezelfde datum nog een keer.
 *
 * Titel, tijdstip-regel, samenvatting én taxonomie-badges staan allemaal IN
 * dezelfde tekstkolom-div als de titel (niet meer als aparte, na de hele rij
 * gestapelde blokken) — anders bepaalde de HOOGTE VAN DE DATUM-KOLOM (vaak
 * hoger dan een korte titel) hoeveel losse ruimte er onder de titel overbleef
 * voor de samenvatting begon, ook al stond er tekstueel niets tussen: de
 * twee kolommen van de flex-rij worden namelijk beide even hoog getrokken
 * (default align-items:stretch-gedrag zou dat doen; hier expliciet
 * "items-start", maar de daaropvolgende elementen stonden VOORHEEN als
 * sibling van de hele rij, dus ze begonnen pas onder de HOOGSTE van de twee
 * kolommen, niet direct onder de titel-tekst zelf). Nu ze in de titel-kolom
 * zelf zitten, volgen ze gewoon direct op de titel, ongeacht hoe hoog het
 * datumvierkant ernaast is.
 *
 * Geen "Meld je aan"-knop (meer) hier — kort geprobeerd, maar een kale knop
 * zo vroeg in de hero, los van elke uitleg, oogde losstaand/onduidelijk.
 * Aanmelden gebeurt nu op ÉÉN plek: blocks/agenda-share/render.php's eigen
 * "Aanmelden mogelijk"-kaart onder de inhoud, met een korte uitleg naast de
 * knop i.p.v. hier een kale knop zonder context.
 *
 * Alleen insertable binnen templates/single-agenda.html — hooks/
 * restrict-to-single-agenda-template.php.
 *
 * Uitgelichte afbeelding (native has_post_thumbnail() — de "agenda" CPT
 * ondersteunt "thumbnail", zie acf-json/post_type_6a82b458e972a.json) komt
 * NAAST de tekst i.p.v. erboven, zelfde opzet/reden als blocks/
 * news-single-hero/render.php's eigen docblock (content eerst gebufferd via
 * ob_start/ob_get_clean, daarna pas gekozen: los renderen zonder afbeelding —
 * identiek aan de oude opzet — of in een twee-koloms flex-rij ernaast).
 * "contentAlign: center" wordt genegeerd zodra er een afbeelding is, om
 * dezelfde reden: een gecentreerde tekstkolom naast een afbeelding heeft geen
 * zinnig middelpunt meer. Vaste liggende beeldverhouding ("aspect-video",
 * 16:9) op elke breedte, vaste breedte ("lg:w-md") i.p.v. meegroeien met de
 * tekstkolom, en "rounded-xl" i.p.v. -2xl — zelfde redenen als blocks/
 * news-single-hero/render.php's eigen docblock.
 *
 * "contentVerticalAlign" (editor.js) bepaalt hoe de tekstkolom t.o.v. de
 * afbeelding uitlijnt in die twee-koloms rij — top/center/bottom naar
 * "items-start"/"items-center"/"items-end". Geen zichtbaar effect zonder
 * afbeelding.
 *
 * Geen "max-w-5xl" (meer) op de buitenste content-div zodra er een
 * afbeelding is, zelfde reden/redenering als blocks/news-single-hero/
 * render.php's eigen docblock: die cap hield de rij smaller dan de sectie's
 * eigen "align=wide"-breedte, zonder "mx-auto" om 'm te centreren, waardoor
 * de afbeelding wél rechts stond BINNEN die te smalle box, maar niet tegen
 * de echte rand van de hero.
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

// breadcrumb_color/title_color/description_color gaan NIET meer via een CSS
// custom property + Tailwind "text-(--var)"-class (zoals badge_accent_color/
// date_badge_color hieronder nog wel doen): WordPress' eigen Ontwerp-brede
// kop-/tekstkleur (Stijlen → Typografie) rendert als een KALE "h1,h2,...{color:
// ...}"/vergelijkbare regel, ONGELAAGD ("unlayered") — Tailwind's eigen
// gegenereerde utility-classes (incl. dit soort "text-(--var)") komen altijd
// in "@layer utilities" terecht, en een ongelaagde regel wint ALTIJD van een
// gelaagde, ongeacht specificiteit. Een editor-gekozen titelkleur verloor
// daardoor stilzwijgend van de globale kopkleur zodra die laatste ooit gezet
// werd. Een echte inline style="" op het element zelf (hieronder, bij de
// <h1>/broodkruimel/omschrijving) omzeilt dat volledig: inline styles zitten
// buiten elke cascade layer en winnen altijd.
$style_parts = ['box-sizing:border-box'];
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
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview), $breadcrumb_color !== '' ? 'color:' . $breadcrumb_color : '')
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
$has_thumbnail = has_post_thumbnail($post_id);

$vertical_align = $block['contentVerticalAlign'] ?? 'top';
$vertical_align_class = match ($vertical_align) {
    'center' => 'items-center',
    'bottom' => 'items-end',
    default => 'items-start',
};

ob_start();
?>
<div class="flex items-start gap-4 sm:gap-5 <?= $content_align === 'center' && !$has_thumbnail ? 'justify-center text-center' : 'text-left'; ?>">
    <?php if ($date_badge['day'] !== '') : ?>
        <time
            datetime="<?= esc_attr($date_badge['iso']); ?>"
            class="own-agenda-date-badge shrink-0 flex flex-col items-center justify-center gap-0.5 rounded-xl min-w-16 h-16 sm:min-w-18 sm:h-18 px-2 text-center">
            <span class="own-agenda-date-badge__month text-[0.65rem] sm:text-xs font-semibold uppercase tracking-wide leading-none">
                <?= esc_html($date_badge['month']); ?>
            </span>
            <span class="own-agenda-date-badge__day <?= $date_badge['range'] ? 'text-base sm:text-lg' : 'text-xl sm:text-2xl'; ?> font-bold leading-none">
                <?= esc_html($date_badge['day']); ?>
            </span>
            <?php if ($date_badge['year'] !== '') : ?>
                <span class="own-agenda-date-badge__year text-[0.65rem] sm:text-xs font-medium opacity-70 leading-none">
                    <?= esc_html($date_badge['year']); ?>
                </span>
            <?php endif; ?>
        </time>
    <?php endif; ?>
    <div class="min-w-0 flex-1">
        <h1 class="font-semibold m-0!"<?= $title_color !== '' ? ' style="color:' . esc_attr($title_color) . '"' : ''; ?>>
            <?= esc_html(get_the_title()); ?>
        </h1>
        <?php if ($date_caption !== '') : ?>
            <p class="mt-1 text-sm font-medium m-0! text-(--own-agenda-accent)">
                <?= esc_html($date_caption); ?>
            </p>
        <?php endif; ?>
        <?php if (has_excerpt()) : ?>
            <div class="mt-3 text-sm sm:text-base leading-relaxed opacity-80"<?= $description_color !== '' ? ' style="color:' . esc_attr($description_color) . '"' : ''; ?>>
                <?= wp_kses_post(get_the_excerpt()); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($badges)) : ?>
            <div class="mt-5 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' && !$has_thumbnail ? 'justify-center' : ''; ?>">
                <?php foreach ($badges as $badge) : ?>
                    <span class="own-agenda-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                        <?= esc_html($badge); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content_html = ob_get_clean();
?>
<section <?= $wrapper_attributes; ?>>
    <?= $bleed_background; ?>
    <div class="relative z-10 <?= $has_thumbnail ? '' : 'max-w-3xl ' . esc_attr($content_align_class); ?>">
        <?= $breadcrumbs; ?>
        <?php if ($has_thumbnail) : ?>
            <div class="flex flex-col lg:flex-row <?= esc_attr($vertical_align_class); ?> gap-8 lg:gap-12">
                <div class="min-w-0 flex-1 order-2 lg:order-1"><?= $content_html; ?></div>
                <div class="order-1 lg:order-2 w-full lg:w-md shrink-0 aspect-video overflow-hidden rounded-xl">
                    <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'w-full h-full object-cover m-0!']); ?>
                </div>
            </div>
        <?php else : ?>
            <?= $content_html; ?>
        <?php endif; ?>
    </div>
</section>
