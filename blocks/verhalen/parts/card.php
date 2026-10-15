<?php
defined('ABSPATH') || exit();

/**
 * Eén verhaal in de resultatengrid — aangeroepen vanuit een live WP_Query-
 * loop (ownframework_verhalen_render_cards(), hooks/query.php), the_post()
 * heeft al gedraaid voor het huidige item.
 *
 * Bewust een heel ander kaarttype dan blocks/news/parts/card.php's eigen
 * kaart (foto BOVEN de tekst, wit kaartlijf eronder): dit is storytelling,
 * geen nieuwsfeed, dus de foto IS de kaart — vaste portretverhouding
 * ("aspect-[3/4]"), de afbeelding vult 'm volledig ("absolute inset-0
 * object-cover"), en kop/ondertitel/datum/link liggen eroverheen onderin,
 * leesbaar dankzij een donker verloop van onderaf (".own-verhalen-card__
 * scrim", verhalen.css) — geen los tekstvlak, geen uittreksel (dat hoort bij
 * dit CPT thuis in de hero van de detailpagina, blocks/verhaal-single-hero/
 * render.php, niet nog eens verkort op de kaart).
 *
 * De KOP-positie (het "<h2>"/"<h3>"-element, $title_tag) toont de quote
 * (ACF-veld "quote", acf-json/group_own_verhaal_fields.json) als die is
 * ingevuld, met de titel van het verhaal als fallback zodra dat niet zo is —
 * nooit allebei tegelijk, en nooit leeg. Aanhalingstekens worden alleen om de
 * quote gezet (niet in het veld zelf opgeslagen, en niet om de titel-
 * fallback — dat is geen citaat), zodat een redacteur alleen de kale
 * quotetekst hoeft in te typen. "Ondertitel" (ACF-veld "subtitle", zelfde
 * group) is een tweede, optioneel veld dat op de plek verschijnt waar
 * voorheen de quote zelf stond (".own-verhalen-card__quote" — gedeeld met
 * blocks/verhaal-single-hero/render.php's eigen quote, verhalen.css's eigen
 * docblock) — bedoeld als korte aanvullende tekst onder de kop, niet als
 * vervanging van de quote/titel-fallback erboven.
 *
 * DRIE lagen voor de kaartachtergrond, in deze volgorde: (1) het verhaal's
 * eigen uitgelichte afbeelding, (2) zonder die: acf/verhalen's eigen
 * "fallbackImage"-attribute (editor.js' "Kaart zonder foto"-paneel,
 * ownframework_verhalen_get_configured_fallback_image() hieronder), een
 * projectbreed ingestelde placeholder-foto — bijvoorbeeld een merk-beeld,
 * i.p.v. dat elk verhaal zonder eigen foto er kaal/incompleet uitziet, (3)
 * zonder ALLEBEI: een effen kleurvak (".own-verhalen-card--fallback"), nooit
 * een kapotte/lege afbeeldingsbox. De scrim/kop/ondertitel/datum/link-laag
 * erover blijft in alle drie de gevallen hetzelfde — ook op een effen kleur
 * geeft het onderste verloop de tekst nog wat diepte.
 *
 * Kop/ondertitel/datum/link lezen "--own-verhalen-card-text-color" (acf/
 * verhalen's eigen "cardTextColor"-attribute, op DIT block's eigen wrapper
 * gezet, niet hier) — valt terug op wit (verhalen.css) als niemand een
 * kleur koos. Bewust ÉÉN gedeelde kleur voor alle vier (geen aparte titel-/
 * ondertitel-/datum-/linkkleur zoals blocks/news/parts/card.php's eigen
 * kaart): deze kaart heeft geen eigen te kleuren achtergrond om ze los van
 * te onderscheiden, alleen de foto erachter.
 *
 * "no-underline" op de root <a>: een voorouder's text-decoration schildert
 * anders door naar elk kind-element — zelfde reden als blocks/news/parts/
 * card.php's/blocks/vacatures/parts/card.php's eigen root-link.
 */

$post_id = get_the_ID();
$has_thumbnail = has_post_thumbnail($post_id);
$quote = trim((string) get_field('quote', $post_id));
$subtitle = trim((string) get_field('subtitle', $post_id));
$has_quote = $quote !== '';

// $args['title_level'] — acf/verhalen-uitgelicht's eigen "cardTitleLevel"
// (editor.js), get_template_part()'s 3e argument. Default 2 (H2), ongewijzigd
// t.o.v. eerder, voor de archiefpagina zelf (acf/verhalen, hooks/query.php's
// aanroep hier geeft geen $args mee) — zie blocks/vacatures/parts/card.php's
// identieke comment voor de volle reden.
$title_tag = 'h' . max(2, min(6, (int) ($args['title_level'] ?? 2)));

$fallback_image_configured = $has_thumbnail ? [] : ownframework_verhalen_get_configured_fallback_image();
$fallback_image = ownframework_resolve_block_image($fallback_image_configured);
$has_fallback_image = !$has_thumbnail && $fallback_image['url'] !== '';
$has_any_image = $has_thumbnail || $has_fallback_image;

// ownframework_get_image_object_position() — inc/functions/image-helpers.php,
// gedeeld met acf/hero, acf/cta, acf/content-blok en acf/afbeeldingen-slider.
// Alleen relevant voor de fallback-foto: het verhaal's eigen uitgelichte
// afbeelding (get_the_post_thumbnail() hieronder) is WordPress' eigen native
// featured-image-keuze, die heeft geen brandpunt-instelling van dit block uit.
$fallback_image_position = $has_fallback_image
    ? ownframework_get_image_object_position($fallback_image_configured['position'] ?? [])
    : '';
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-verhalen-card group relative flex aspect-3/4 flex-col overflow-hidden rounded-2xl no-underline <?= $has_any_image ? '' : 'own-verhalen-card--fallback'; ?>">
    <?php if ($has_thumbnail) : ?>
        <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'absolute inset-0 h-full w-full object-cover m-0! transition-transform duration-300 group-hover:scale-105']); ?>
    <?php elseif ($has_fallback_image) : ?>
        <img
            src="<?= esc_url($fallback_image['url']); ?>"
            alt="<?= esc_attr($fallback_image['alt']); ?>"
            class="absolute inset-0 h-full w-full object-cover m-0! transition-transform duration-300 group-hover:scale-105"
            style="object-position: <?= esc_attr($fallback_image_position); ?>;">
    <?php endif; ?>

    <div class="own-verhalen-card__scrim absolute inset-0" aria-hidden="true"></div>

    <div class="relative z-10 mt-auto flex flex-col gap-1 p-4 sm:p-5 text-(--own-verhalen-card-text-color)">
        <<?= $title_tag; ?> class="own-verhalen-card__title m-0! text-(length:--own-h3-font-size)! leading-(--own-h3-line-height)! font-semibold! line-clamp-2 text-current!">
            <?php if ($has_quote) : ?>
                &ldquo;<?= esc_html($quote); ?>&rdquo;
            <?php else : ?>
                <?= esc_html(get_the_title()); ?>
            <?php endif; ?>
        </<?= $title_tag; ?>>
        <?php if ($subtitle !== '') : ?>
            <?php // ".own-verhalen-card__quote" — gedeelde class met blocks/
            // verhaal-single-hero/render.php's eigen quote (verhalen.css's
            // eigen docblock), niet hernoemd om dat andere block erbuiten
            // te laten. 
            ?>
            <p class="own-verhalen-card__quote mt-1 mb-0! text-sm leading-snug opacity-90 line-clamp-2">
                <?= esc_html($subtitle); ?>
            </p>
        <?php endif; ?>
        <p class="mt-2 mb-0! text-xs opacity-75">
            <?= esc_html(get_the_date()); ?>
        </p>
        <span class="own-verhalen-card__link mt-3 inline-flex items-center gap-1 text-sm font-medium group-hover:underline!">
            <?php esc_html_e('Lees verhaal', 'own-framework'); ?>
            <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
        </span>
    </div>
</a>