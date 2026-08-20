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
 * object-cover"), en titel/quote/datum/link liggen eroverheen onderin,
 * leesbaar dankzij een donker verloop van onderaf (".own-verhalen-card__
 * scrim", verhalen.css) — geen los tekstvlak, geen uittreksel (dat hoort bij
 * dit CPT thuis in de hero van de detailpagina, blocks/verhaal-single-hero/
 * render.php, niet nog eens verkort op de kaart).
 *
 * Quote (ACF-veld "quote", acf-json/group_own_verhaal_fields.json) is
 * optioneel — een verhaal zonder quote toont gewoon titel/datum/link, geen
 * lege regel. Aanhalingstekens worden hier toegevoegd (niet in het veld
 * zelf opgeslagen), zodat een redacteur alleen de kale tekst hoeft in te
 * typen.
 *
 * DRIE lagen voor de kaartachtergrond, in deze volgorde: (1) het verhaal's
 * eigen uitgelichte afbeelding, (2) zonder die: acf/verhalen's eigen
 * "fallbackImage"-attribute (editor.js' "Kaart zonder foto"-paneel,
 * ownframework_verhalen_get_configured_fallback_image() hieronder), een
 * projectbreed ingestelde placeholder-foto — bijvoorbeeld een merk-beeld,
 * i.p.v. dat elk verhaal zonder eigen foto er kaal/incompleet uitziet, (3)
 * zonder ALLEBEI: een effen kleurvak (".own-verhalen-card--fallback"), nooit
 * een kapotte/lege afbeeldingsbox. De scrim/titel/quote/datum/link-laag
 * erover blijft in alle drie de gevallen hetzelfde — ook op een effen kleur
 * geeft het onderste verloop de tekst nog wat diepte.
 *
 * Titel/quote/datum/link lezen "--own-verhalen-card-text-color" (acf/
 * verhalen's eigen "cardTextColor"-attribute, op DIT block's eigen wrapper
 * gezet, niet hier) — valt terug op wit (verhalen.css) als niemand een
 * kleur koos. Bewust ÉÉN gedeelde kleur voor alle vier (geen aparte titel-/
 * quote-/datum-/linkkleur zoals blocks/news/parts/card.php's eigen kaart):
 * deze kaart heeft geen eigen te kleuren achtergrond om ze los van te
 * onderscheiden, alleen de foto erachter.
 *
 * "no-underline" op de root <a>: een voorouder's text-decoration schildert
 * anders door naar elk kind-element — zelfde reden als blocks/news/parts/
 * card.php's/blocks/vacatures/parts/card.php's eigen root-link.
 */

$post_id = get_the_ID();
$has_thumbnail = has_post_thumbnail($post_id);
$quote = trim((string) get_field('quote', $post_id));

$fallback_image = $has_thumbnail ? [] : ownframework_resolve_block_image(ownframework_verhalen_get_configured_fallback_image());
$has_fallback_image = !$has_thumbnail && $fallback_image['url'] !== '';
$has_any_image = $has_thumbnail || $has_fallback_image;
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-verhalen-card group relative flex aspect-3/4 flex-col overflow-hidden rounded-2xl no-underline <?= $has_any_image ? '' : 'own-verhalen-card--fallback'; ?>">
    <?php if ($has_thumbnail) : ?>
        <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'absolute inset-0 h-full w-full object-cover m-0! transition-transform duration-300 group-hover:scale-105']); ?>
    <?php elseif ($has_fallback_image) : ?>
        <img
            src="<?= esc_url($fallback_image['url']); ?>"
            alt="<?= esc_attr($fallback_image['alt']); ?>"
            class="absolute inset-0 h-full w-full object-cover m-0! transition-transform duration-300 group-hover:scale-105">
    <?php endif; ?>

    <div class="own-verhalen-card__scrim absolute inset-0" aria-hidden="true"></div>

    <div class="relative z-10 mt-auto flex flex-col gap-1 p-4 sm:p-5 text-(--own-verhalen-card-text-color)">
        <h2 class="own-verhalen-card__title m-0! text-base sm:text-lg font-semibold leading-snug line-clamp-2 text-current!">
            <?= esc_html(get_the_title()); ?>
        </h2>
        <?php if ($quote !== '') : ?>
            <p class="own-verhalen-card__quote mt-1 text-sm italic leading-snug opacity-90 line-clamp-2">
                &ldquo;<?= esc_html($quote); ?>&rdquo;
            </p>
        <?php endif; ?>
        <p class="mt-2 text-xs opacity-75">
            <?= esc_html(get_the_date()); ?>
        </p>
        <span class="own-verhalen-card__link mt-3 inline-flex items-center gap-1 text-sm font-medium group-hover:underline!">
            <?php esc_html_e('Lees verhaal', 'own-framework'); ?>
            <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
        </span>
    </div>
</a>