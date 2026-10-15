<?php
defined('ABSPATH') || exit();

/**
 * Detailpagina-hero van één nieuwsbericht — broodkruimelpad, titel,
 * publicatiedatum, samenvatting en categorie-badges van DIT bericht.
 * Structureel een kopie van acf/vacature-single-hero (links uitgelijnd,
 * broodkruimelpad standaard AAN, dezelfde kleurattribuut-per-sub-element
 * opzet — CLAUDE.md Traps #7). "dateColor" kleurt alleen de publicatiedatum-
 * regel, "descriptionColor" alleen de samenvatting eronder — twee losse
 * attributes, zelfde als acf/agenda-single-hero/acf/vacature-single-hero's
 * eigen "descriptionColor", i.p.v. de datum-kleur ook voor de samenvatting te
 * hergebruiken (een eerdere, inmiddels weer rechtgezette tussenoplossing van
 * toen deze hero nog geen samenvatting had). get_the_excerpt() i.p.v.
 * has_excerpt(): zelfde "toon 'm altijd"-
 * conventie als blocks/news/parts/card.php's eigen kaart (WordPress vult 'm
 * anders zelf aan uit de inhoud), in tegenstelling tot acf/agenda-single-hero
 * dat WEL op has_excerpt() checkt (daar is een samenvatting optioneel, geen
 * standaard blog-conventie).
 *
 * Badges lezen get_the_category() (native, geen taxonomie-loop zoals
 * vacatures/agenda's eigen dynamisch-ontdekte taxonomieën) — dit CPT filtert/
 * tagt bewust alleen op WordPress' eigen "Categorie", zie blocks/news/hooks/
 * query.php's eigen docblock voor de volledige reden. Eigen tags staan hier
 * NIET (meer) — die staan nu bij blocks/news-share/render.php's eigen
 * deelbalk onderaan de pagina: minder belangrijk dan de categorie (geen
 * filterbare facet, puur losse context), dus verplaatst naar een minder
 * prominente plek i.p.v. mee te tellen in de hero's eigen hiërarchie.
 *
 * Alleen insertable op een echte `post` of binnen templates/single.html —
 * hooks/restrict-to-single-post.php.
 *
 * De achtergrondkleur (native "Kleuren"-paneel) loopt tot de volle
 * schermbreedte via ownframework_full_bleed_class()/_background() — zelfde
 * reden als acf/vacature-single-hero's eigen docblock.
 *
 * Uitgelichte afbeelding (native has_post_thumbnail(), niet verplicht) komt
 * NAAST de tekst te staan i.p.v. erboven — anders dan blocks/news/parts/
 * card.php's eigen leidende afbeelding (die IS altijd bovenaan de kaart,
 * daar is geen tweede kolom om in te passen). Content wordt daarom eerst in
 * $content_html gebufferd (ob_start/ob_get_clean, zelfde techniek als hooks/
 * query.php's eigen ownframework_news_render_cards()) en pas daarna gekozen
 * of 'm los rendert (geen afbeelding — identiek aan de oude, tekst-only
 * opzet) of in een twee-koloms flex-rij naast de afbeelding. Zonder
 * afbeelding blijft "contentAlign: center" gewoon werken (mx-auto
 * text-center op de hele tekstkolom); MET afbeelding wordt die centrering
 * bewust genegeerd — een gecentreerde tekstkolom naast een afbeelding heeft
 * geen zinnig middelpunt meer, dus dan is links uitgelijnd de enige optie.
 * Volgorde wisselt op mobiel (afbeelding boven, order-1) t.o.v. desktop
 * (tekst links, order-1) — zelfde order-1/order-2-wisseltruc als blocks/
 * vacatures/parts/card.php's eigen logo/tekst-rij. Vaste liggende
 * beeldverhouding ("aspect-video", 16:9) op elke breedte — bewust geen
 * portret/vierkant-variant op mobiel, dit is altijd een liggende foto naast
 * (of, op mobiel, boven) de tekst. Vaste breedte "lg:w-md" (i.p.v. flex-1,
 * zodat 'm niet meegroeit met de tekstkolom) — de tekstkolom's eigen
 * "flex-1" erlangs duwt de afbeelding daardoor vanzelf tegen de rechterkant
 * van de container aan. "rounded-xl", niet -2xl: één stap kleiner, dit IS de
 * media zelf (geen aparte kaart eromheen), zelfde stap als CLAUDE.md's eigen
 * kaart-media-conventie.
 *
 * Geen "max-w-5xl" (meer) op de buitenste content-div zodra er een
 * afbeelding is — die cap liet de twee-koloms rij zelf blijven steken op
 * 1024px, ruim smaller dan de sectie's eigen "align=wide"-breedte
 * (theme.json's "wideSize", doorgaans breder), terwijl er geen "mx-auto" op
 * stond om 'm alsnog te centreren: de rij bleef daardoor links hangen, met de
 * afbeelding wél rechts uitgelijnd BINNEN die te smalle box, maar niet tegen
 * de echte rand van de hero. Zonder cap vult de rij de sectie's eigen,
 * al-begrensde breedte, dus landt de afbeelding pas dán tegen de werkelijke
 * rechterrand.
 *
 * "contentVerticalAlign" (editor.js) bepaalt hoe de tekstkolom t.o.v. de
 * afbeelding uitlijnt in die twee-koloms rij — top/center/bottom naar
 * "items-start"/"items-center"/"items-end" op de flex-rij. Heeft geen
 * zichtbaar effect zonder afbeelding (dan is er maar één kolom).
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
$description_color = $block['descriptionColor'] ?? '';
$badge_accent_color = $block['badgeAccentColor'] ?? '';

// breadcrumb_color/title_color/date_color/description_color gaan NIET meer
// via een CSS custom property + Tailwind "text-(--var)"-class (zoals
// badge_accent_color hieronder nog wel doet): WordPress' eigen Ontwerp-brede
// kop-/tekstkleur (Stijlen → Typografie) rendert als een KALE "h1,h2,...
// {color:...}"-regel, ONGELAAGD ("unlayered") — Tailwind's eigen
// gegenereerde utility-classes (incl. dit soort "text-(--var)") komen altijd
// in "@layer utilities" terecht, en een ongelaagde regel wint ALTIJD van een
// gelaagde, ongeacht specificiteit. Een editor-gekozen titelkleur verloor
// daardoor stilzwijgend van de globale kopkleur zodra die laatste ooit gezet
// werd. Een echte inline style="" op het element zelf (hieronder, bij de
// <h1>/broodkruimel/datum/omschrijving) omzeilt dat volledig: inline styles
// zitten buiten elke cascade layer en winnen altijd.
$style_parts = ['box-sizing:border-box'];
if ($badge_accent_color !== '') {
    $style_parts[] = '--own-news-accent:' . esc_attr($badge_accent_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-news-single-hero py-12 sm:py-16 lg:py-20'),
    'style' => implode(';', $style_parts),
]);

$bleed_background = ownframework_full_bleed_background($bg_value);

$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview), $breadcrumb_color !== '' ? 'color:' . $breadcrumb_color : '')
    : '';

$post_id = get_the_ID();
$categories = get_the_category($post_id);
$has_thumbnail = has_post_thumbnail($post_id);

$vertical_align = $block['contentVerticalAlign'] ?? 'top';
$vertical_align_class = match ($vertical_align) {
    'center' => 'items-center',
    'bottom' => 'items-end',
    default => 'items-start',
};

ob_start();
?>
<h1 class="font-semibold m-0!"<?= $title_color !== '' ? ' style="color:' . esc_attr($title_color) . '"' : ''; ?>>
    <?= esc_html(get_the_title()); ?>
</h1>
<p class="mt-3 text-sm opacity-80"<?= $date_color !== '' ? ' style="color:' . esc_attr($date_color) . '"' : ''; ?>>
    <?= esc_html(get_the_date()); ?>
</p>
<div class="mt-3 text-sm sm:text-base leading-relaxed opacity-80"<?= $description_color !== '' ? ' style="color:' . esc_attr($description_color) . '"' : ''; ?>>
    <?= wp_kses_post(get_the_excerpt()); ?>
</div>
<?php if (!empty($categories)) : ?>
    <div class="mt-4 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' && !$has_thumbnail ? 'justify-center' : ''; ?>">
        <?php foreach ($categories as $category) : ?>
            <span class="own-news-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                <?= esc_html($category->name); ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content_html = ob_get_clean();
?>
<section <?= $wrapper_attributes; ?>>
    <?= $bleed_background; ?>
    <?php // Broodkruimelpad buiten de max-w-3xl-kolom, zodat een lang pad de volle
    // blockbreedte gebruikt voordat het wrapt. ?>
    <div class="relative z-10 <?= $content_align === 'center' && !$has_thumbnail ? '[&>nav>ol]:justify-center' : ''; ?>">
        <?= $breadcrumbs; ?>
        <?php if ($has_thumbnail) : ?>
            <div class="flex flex-col lg:flex-row <?= esc_attr($vertical_align_class); ?> gap-8 lg:gap-12">
                <div class="min-w-0 flex-1 order-2 lg:order-1"><?= $content_html; ?></div>
                <div class="order-1 lg:order-2 w-full lg:w-md shrink-0 aspect-video overflow-hidden rounded-xl">
                    <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'w-full h-full object-cover m-0!']); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="max-w-3xl <?= esc_attr($content_align_class); ?>"><?= $content_html; ?></div>
        <?php endif; ?>
    </div>
</section>
