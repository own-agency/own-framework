<?php
defined('ABSPATH') || exit();

/**
 * Vacature-detailpagina's eigen hero — broodkruimelpad, titel, samenvatting
 * (excerpt) en taxonomie-badges van DEZE vacature (dezelfde ownframework_
 * vacatures_get_taxonomies()/ownframework_vacature_taxonomy_icon() als
 * blocks/vacatures/parts/card.php en blocks/vacatures/hooks/query.php's eigen
 * filterbalk — een taxonomie die daar niet actief is (Thema opties →
 * Vacature taxonomieën) verschijnt dus ook hier niet).
 *
 * "Vergelijkbaar met acf/vacature-hero" (het archief's hero), maar met eigen
 * defaults: links uitgelijnd i.p.v. gecentreerd (er staat hier geen
 * zoekbalk om content omheen te centreren) en het broodkruimelpad staat hier
 * standaard AAN (op de archiefpagina zelf is dat pad altijd leeg — "Home" is
 * het enige item — dus daar staat het uit).
 *
 * Achtergrond/tekst hebben allemaal een standaardkleur (block.json:
 * "primary"/"var(--wp--preset--color--base)") — zelfde reden als acf/
 * vacature-hero's eigen backgroundColor/textColor-defaults ("primary"/
 * "base"): een vers ingevoegd block moet al contrast tonen, niet pas nadat
 * iemand zelf kleuren gaat kiezen.
 *
 * Broodkruimelpad/titel/omschrijving/badges hebben elk hun EIGEN kleur-
 * attribute (editor.js) i.p.v. het native "Kleuren"-paneel voor tekst
 * (block.json's supports.color.text staat daarom uit, alleen background
 * blijft native) — vier onafhankelijk instelbare sub-elementen die dat ene
 * paneel nooit apart zou kunnen kleuren (CLAUDE.md Traps #7), zelfde
 * redenering als acf/vacatures' eigen cardTitleColor/cardTextColor/etc.
 * "badgeAccentColor" hergebruikt bewust dezelfde "--own-vacatures-accent"
 * custom property als de archiefpagina's badges (vacatures.css) — geen
 * aparte kleurmechaniek nodig voor exact hetzelfde soort element.
 *
 * De omschrijving gebruikt bewust GEEN ".prose" (in tegenstelling tot bv.
 * acf/vacatures' parts/card.php) — Tailwind Typography geeft ".prose" zijn
 * EIGEN kleurtoken (--tw-prose-body) in plaats van gewoon geërfde `color`,
 * wat "descriptionColor" hieronder zou negeren. Voor een korte samenvatting
 * (geen gestructureerde rich text) is platte tekst met een leesbare
 * regelhoogte voldoende.
 *
 * Alleen insertable binnen templates/single-vacancy.html — hooks/
 * restrict-to-single-vacancy-template.php.
 *
 * De achtergrondkleur (native "Kleuren"-paneel) loopt tot de volle
 * schermbreedte via dezelfde ownframework_full_bleed_class()/_background()-
 * truc als acf/vacature-hero — een block blijft anders altijd binnen zijn
 * eigen align-breedte hangen (CLAUDE.md, "Alignment, width and full-bleed
 * backgrounds"), en deze hero hoort er precies zo uit te zien als de
 * archiefpagina's eigen hero. get_block_wrapper_attributes() past de native
 * achtergrond dus WEL nog automatisch toe op de sectie zelf (block.json's
 * supports.color.background) — onschadelijk, want die kleur is identiek aan
 * de full-bleed laag eronder, alleen breder.
 *
 * Uitgelichte afbeelding (native has_post_thumbnail() — de "vacancy" CPT
 * ondersteunt "thumbnail") komt NAAST de tekst i.p.v. erboven, zelfde opzet/
 * reden als blocks/news-single-hero/render.php's eigen docblock: content
 * eerst gebufferd via ob_start()/ob_get_clean(), daarna pas gekozen — los
 * renderen zonder afbeelding (identiek aan de oude opzet) of in een
 * twee-koloms flex-rij ernaast (vaste liggende beeldverhouding "aspect-video",
 * vaste breedte "lg:w-md", "rounded-xl"). "contentAlign: center" wordt
 * genegeerd zodra er een afbeelding is, "contentVerticalAlign" (editor.js)
 * bepaalt de top/center/bottom-uitlijning van de tekstkolom t.o.v. de
 * afbeelding — allemaal exact dezelfde mechanismen/redenen als
 * news-single-hero/agenda-single-hero.
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

$style_parts = ['box-sizing:border-box'];
if ($breadcrumb_color !== '') {
    $style_parts[] = '--own-vacature-single-hero-breadcrumb-color:' . esc_attr($breadcrumb_color);
}
if ($title_color !== '') {
    $style_parts[] = '--own-vacature-single-hero-title-color:' . esc_attr($title_color);
}
if ($description_color !== '') {
    $style_parts[] = '--own-vacature-single-hero-description-color:' . esc_attr($description_color);
}
if ($badge_accent_color !== '') {
    $style_parts[] = '--own-vacatures-accent:' . esc_attr($badge_accent_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-vacature-single-hero py-12 sm:py-16 lg:py-20'),
    'style' => implode(';', $style_parts),
]);

$bleed_background = ownframework_full_bleed_background($bg_value);

$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm text-(--own-vacature-single-hero-breadcrumb-color)', !empty($is_preview))
    : '';

$posted_timestamp = (int) get_the_date('U');
$time_ago = human_time_diff($posted_timestamp, current_time('timestamp'));

$badges = [];
foreach (ownframework_vacatures_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms(get_the_ID(), $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    $icon = ownframework_vacature_taxonomy_icon($slug);
    foreach ($terms as $term) {
        $badges[] = ['label' => $term->name, 'icon' => $icon];
    }
}

$post_id = get_the_ID();
$has_thumbnail = has_post_thumbnail($post_id);

$vertical_align = $block['contentVerticalAlign'] ?? 'top';
$vertical_align_class = match ($vertical_align) {
    'center' => 'items-center',
    'bottom' => 'items-end',
    default => 'items-start',
};

ob_start();
?>
<h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold m-0! text-(--own-vacature-single-hero-title-color)">
    <?= esc_html(get_the_title()); ?>
</h1>
<?php if (has_excerpt()) : ?>
    <div class="mt-3 text-sm sm:text-base leading-relaxed opacity-80 text-(--own-vacature-single-hero-description-color)">
        <?= wp_kses_post(get_the_excerpt()); ?>
    </div>
<?php endif; ?>
<p class="mt-5 text-sm opacity-70 text-(--own-vacature-single-hero-description-color)">
    <?= esc_html($time_ago); ?> <?php esc_html_e('geleden geplaatst', 'own-framework'); ?>
</p>
<?php if (!empty($badges)) : ?>
    <div class="mt-4 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' && !$has_thumbnail ? 'justify-center' : ''; ?>">
        <?php foreach ($badges as $badge) : ?>
            <span class="own-vacatures-badge inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium">
                <?php if (!empty($badge['icon'])) : ?>
                    <span class="own-vacatures-tax-icon w-3.5 h-3.5 shrink-0" aria-hidden="true"><?= $badge['icon']; ?></span>
                <?php endif; ?>
                <?= esc_html($badge['label']); ?>
            </span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
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
