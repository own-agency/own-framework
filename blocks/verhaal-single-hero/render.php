<?php
defined('ABSPATH') || exit();

/**
 * Detailpagina-hero van één verhaal — broodkruimelpad, titel, uittreksel en
 * categorie-badges van DIT verhaal, in een tekstkolom van 2/3 breedte
 * ("lg:w-2/3", dezelfde verhouding als templates/single-vacancy.html's/
 * single-agenda.html's eigen "core/columns"-splitsing verderop op die
 * pagina's — hier toegepast op de hero zelf) op een effen kleur- of
 * verloopachtergrond, structureel gebaseerd op acf/vacature-single-hero/acf/
 * news-single-hero (links uitgelijnd, broodkruimelpad standaard AAN,
 * dezelfde kleurattribuut-per-sub-element opzet — CLAUDE.md Traps #7).
 *
 * HET GENUINE VERSCHIL met die twee: de uitgelichte afbeelding (portret-
 * verhouding, "aspect-[3/4]" — dezelfde als blocks/verhalen/parts/card.php's
 * eigen kaart, met de quote er ook hier bovenop, achter hetzelfde donkere
 * verloop, zie hieronder) staat op "lg" NIET in dezelfde document-flow-rij
 * als de tekst, maar "lg:absolute", vanaf de bovenkant van de sectie rechts
 * uitgelijnd. Reden: in een gedeelde flex/grid-rij bepaalt het HOOGSTE kind
 * de rij's — en dus de gekleurde sectie's eigen ACHTERGROND — hoogte, dus de
 * achtergrond zou nooit korter kunnen zijn dan de foto zelf (zoals een
 * eerdere versie van dit bestand deed, met de foto's hoogte vast op
 * "h-72 sm:h-80 lg:h-96" om de sectie behapbaar te houden). Door de foto in
 * plaats daarvan absoluut te positioneren TEN OPZICHTE VAN de sectie (die al
 * "position:relative" heeft), bepaalt alleen de TEKST de sectie's eigen
 * hoogte — de foto's eigen, vaste hoogte ("lg:h-112", 28rem, ruim hoger dan een
 * gemiddelde titel+uittreksel+badges-kolom) hangt daar los overheen en steekt
 * daardoor typisch een stuk onder de sectie's eigen ondergrens uit, over de
 * inhoud die erna komt — precies het "in de hero, maar overloopt onderaan
 * over de content"-effect dat is aangeleverd als voorbeeld. Geen clipping
 * nodig: een absoluut gepositioneerd element wordt nooit afgekapt door een
 * voorouder zonder "overflow:hidden" (die heeft dit block nergens), en zo'n
 * element tekent zich, ongeacht DOM-volgorde, altijd BOVEN een later, gewoon
 * statisch gepositioneerd sibling (hier: wat er ook na dit block komt, zoals
 * core/post-content) — geen aparte z-index-truc nodig om "erover heen" te
 * garanderen, alleen ter expliciete duidelijkheid nog een "z-20" gezet.
 * Alleen vanaf "lg" (desktop) — op mobiel staat de foto gewoon, in normale
 * flow, onder de tekst (geen overlap-effect, dat zou daar alleen tekst
 * kunnen bedekken op een smal scherm).
 *
 * De PUBLICATIEDATUM (get_the_date(), eigen "dateColor"-attribute) staat
 * tussen titel en uittreksel — dezelfde datum die blocks/verhalen/parts/
 * card.php's eigen archiefkaart ook toont, hier gewoon prominent in de hero
 * i.p.v. klein onderaan een foto: het archief en de detailpagina moeten
 * hetzelfde feit (wanneer is dit gepubliceerd) allebei laten zien, niet
 * alleen de kaart.
 *
 * Het UITTREKSEL (get_the_excerpt(), altijd getoond — CLAUDE.md Traps: dit
 * CPT ondersteunt "excerpt" expliciet) staat in de tekstkolom, onder de
 * datum. De QUOTE (ACF-veld "quote") zit ALTIJD op de foto zelf (niet
 * ernaast/eronder) — dezelfde ".own-verhalen-card__scrim"/"__quote"-classes
 * als blocks/verhalen/parts/card.php's eigen archiefkaart, bewust hetzelfde
 * beeldidioom op archief én detailpagina. Zonder foto (geen thumbnail
 * ingesteld) verschijnt de quote alsnog, dan als eigen regel in de
 * tekstkolom (nooit stilzwijgend verborgen als een editor 'm wel invult maar
 * geen foto instelt) — "quoteColor" is voor precies dat uitzonderingsgeval.
 *
 * Badges lezen ownframework_verhalen_get_taxonomies() (blocks/verhalen/
 * hooks/query.php, autoloaded) — dynamisch ontdekt, geen hardcoded
 * constante, zelfde reden als blocks/agenda-single-hero's eigen aanpak.
 *
 * Alleen insertable binnen templates/single-verhaal.html — hooks/
 * restrict-to-single-verhaal-template.php.
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
$description_color = $block['descriptionColor'] ?? '';
$badge_accent_color = $block['badgeAccentColor'] ?? '';
$quote_color = $block['quoteColor'] ?? '';

// breadcrumb_color/title_color/date_color/description_color/quote_color gaan
// NIET meer via een CSS custom property + Tailwind "text-(--var)"-class
// (zoals badge_accent_color hieronder nog wel doet): WordPress' eigen
// Ontwerp-brede kop-/tekstkleur (Stijlen → Typografie) rendert als een KALE
// "h1,h2,...{color:...}"-regel, ONGELAAGD ("unlayered") — Tailwind's eigen
// gegenereerde utility-classes (incl. dit soort "text-(--var)") komen altijd
// in "@layer utilities" terecht, en een ongelaagde regel wint ALTIJD van een
// gelaagde, ongeacht specificiteit. Een editor-gekozen titelkleur verloor
// daardoor stilzwijgend van de globale kopkleur zodra die laatste ooit gezet
// werd. Een echte inline style="" op het element zelf (hieronder, bij de
// <h1>/broodkruimel/datum/omschrijving/quote) omzeilt dat volledig: inline
// styles zitten buiten elke cascade layer en winnen altijd.
$style_parts = ['box-sizing:border-box'];
if ($badge_accent_color !== '') {
    $style_parts[] = '--own-verhalen-accent:' . esc_attr($badge_accent_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => trim($align_class . ' relative own-verhalen-single-hero py-12 sm:py-16 lg:py-20'),
    'style' => implode(';', $style_parts),
]);

$bleed_background = ownframework_full_bleed_background($bg_value);

$breadcrumbs = $show_breadcrumbs
    ? ownframework_get_breadcrumbs('not-prose block mb-6 text-sm', !empty($is_preview), $breadcrumb_color !== '' ? 'color:' . $breadcrumb_color : '')
    : '';

$post_id = get_the_ID();
$has_thumbnail = has_post_thumbnail($post_id);
$quote = trim((string) get_field('quote', $post_id));

$badges = [];
foreach (ownframework_verhalen_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms($post_id, $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    foreach ($terms as $term) {
        $badges[] = $term->name;
    }
}
?>
<section <?= $wrapper_attributes; ?>>
    <?= $bleed_background; ?>
    <?php // Broodkruimelpad buiten de max-w-3xl-kolom, zodat een lang pad de volle
    // blockbreedte gebruikt voordat het wrapt. ?>
    <div class="relative z-10 <?= $content_align === 'center' && !$has_thumbnail ? '[&>nav>ol]:justify-center' : ''; ?>">
        <?= $breadcrumbs; ?>
        <div class="relative <?= $has_thumbnail ? '' : 'max-w-3xl ' . esc_attr($content_align_class); ?>">
            <div class="<?= $has_thumbnail ? 'lg:w-2/3' : ''; ?>">
                <h1 class="font-semibold m-0!"<?= $title_color !== '' ? ' style="color:' . esc_attr($title_color) . '"' : ''; ?>>
                    <?= esc_html(get_the_title()); ?>
                </h1>
                <p class="mt-3 text-sm opacity-80"<?= $date_color !== '' ? ' style="color:' . esc_attr($date_color) . '"' : ''; ?>>
                    <?= esc_html(get_the_date()); ?>
                </p>
                <div class="mt-3 text-sm sm:text-base leading-relaxed opacity-80"<?= $description_color !== '' ? ' style="color:' . esc_attr($description_color) . '"' : ''; ?>>
                    <?= wp_kses_post(get_the_excerpt()); ?>
                </div>
                <?php if (!$has_thumbnail && $quote !== '') : ?>
                    <p class="own-verhalen-single-hero-quote mt-4 text-(length:--own-h3-font-size)! leading-(--own-h3-line-height)! font-bold"<?= $quote_color !== '' ? ' style="color:' . esc_attr($quote_color) . '"' : ''; ?>>
                        &ldquo;<?= esc_html($quote); ?>&rdquo;
                    </p>
                <?php endif; ?>
                <?php if (!empty($badges)) : ?>
                    <div class="mt-4 pt-4 border-t border-current/15 flex flex-wrap items-center gap-2 <?= $content_align === 'center' && !$has_thumbnail ? 'justify-center' : ''; ?>">
                        <?php foreach ($badges as $badge) : ?>
                            <span class="own-verhalen-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                                <?= esc_html($badge); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_thumbnail) : ?>
                <div class="relative mx-auto mt-8 w-56 sm:w-64 lg:absolute lg:inset-y-0 lg:right-0 lg:mx-0 lg:mt-0 lg:top-0 lg:w-auto lg:h-112 z-20">
                    <div class="relative aspect-[3/4] h-full w-full overflow-hidden rounded-xl shadow-xl">
                        <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'h-full w-full object-cover m-0!']); ?>
                        <?php if ($quote !== '') : ?>
                            <div class="own-verhalen-card__scrim absolute inset-0" aria-hidden="true"></div>
                            <p class="own-verhalen-card__quote absolute inset-x-0 bottom-0 z-10 p-4 sm:p-5 text-sm sm:text-base italic leading-snug text-white opacity-90">
                                &ldquo;<?= esc_html($quote); ?>&rdquo;
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
