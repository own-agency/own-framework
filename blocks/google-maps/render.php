<?php
defined('ABSPATH') || exit();

/**
 * Google Maps kaart — toont het adres van de "Algemeen"-optiepagina met een
 * markering erop. Bewust GEEN eigen adresveld: het adres hoort op één plek te
 * staan (Thema opties → Algemeen → Contact → Adres), zodat een verhuizing niet
 * per pagina nagelopen hoeft te worden. Zelfde bron als blocks/footer-contact,
 * via ownframework_get_contact_address().
 *
 * Gebruikt de Maps JAVASCRIPT API (src/js/scripts/google-map.js), niet de
 * Embed API. Een eerdere versie gebruikte de Embed API — een simpele
 * <iframe>, geen eigen JS — maar die biedt GEEN manier om de straatweergave-
 * poppetje of de satelliet-knop te verbergen: geverifieerd tegen Google's
 * eigen documentatie voor "place"-mode, die in totaal maar 6 parameters kent
 * (q, center, zoom, maptype, language, region) en niets voor UI-controls.
 * Die twee alleen aan/uit kunnen zetten (streetViewControl/mapTypeControl)
 * bestaat uitsluitend op het google.maps.Map-object van de JS-API. Zodra dat
 * gevraagd is, is de JS-API dus de enige weg — dezelfde afweging als destijds
 * bij de markering (zie git-historie/eerdere versie van deze docblock).
 *
 * Werkt alleen met COÖRDINATEN, niet met alleen een adrestekst: de JS-API
 * centreert op lat/lng. Een adres zonder coördinaten (nog uit de tijd dat het
 * veld een tekstveld was, of nog nooit op de kaart gekozen) zou een geocoding-
 * call per paginaweergave kosten; één keer het adres opnieuw kiezen/de speld
 * verslepen is dat niet waard.
 *
 * Geen eigen marker-styling — dat is bewust NIET gevraagd deze keer, dus ook
 * niet toegevoegd: gewoon Google's eigen standaardspeld
 * (google.maps.Marker, afgeschreven ten gunste van AdvancedMarkerElement, dat
 * op zijn beurt een cloud-side "map ID" vereist — dat soort configuratie-
 * buiten-de-codebase is precies waarom dit block daar niet van afhankelijk
 * wordt tot het echt nodig is).
 *
 * Vereist "Maps JavaScript API" en "Geocoding API" aan in de Google Cloud
 * Console (zie de instructies bij het sleutelveld, Thema opties →
 * Koppelingen) — niet meer "Maps Embed API", die is met deze wijziging niet
 * meer in gebruik.
 *
 * Zonder sleutel of zonder adres MET coördinaten rendert dit block op de
 * front-end NIETS — een kapotte of lege kaart is daar altijd erger dan geen
 * kaart.
 *
 * De EDITOR laadt nooit de echte kaart, ook niet met een geldige sleutel en
 * adres: elke keer dat Gutenberg een block-preview opnieuw rendert (bij ELKE
 * attribuutwijziging, dus ook tijdens het schuiven aan de zoom-slider) zou dat
 * anders een nieuw, betaald Maps-verzoek zijn. In plaats daarvan altijd een
 * neutrale placeholder met het adres erbij, zodat wél duidelijk is WAAR de
 * kaart straks staat, zonder 'm daadwerkelijk te laden. Ontbreekt sleutel of
 * adres, dan legt die placeholder uit wát er ontbreekt en waar dat ingesteld
 * wordt — anders is een leeg blok in de editor niet te onderscheiden van een
 * blok dat gewoon nog laadt.
 *
 * Val nooit met een class="...-<cijfer>" op het einde van een class-attribute
 * i.p.v. op een letter, "!" of "]": wptexturize() zet een cijfer direct
 * gevolgd door een aanhalingsteken om in een dubbele-prime-entity, wat het
 * sluitende aanhalingsteken kapotmaakt. De class-lijsten hieronder zijn
 * daarom zo geordend dat ze nooit op een cijfer eindigen.
 *
 * "not-prose" op alle drie de wrapper-varianten hieronder: dit block zit via
 * core/post-content altijd binnen ".prose" (templates/page.html's eigen
 * "className":"prose"), en Tailwind Typography's ":where(img)"-regel daarin
 * (margin-top/bottom: 2em) kijkt niet naar WAAR een <img> vandaan komt — hij
 * raakte zo ook Google's eigen, door hun JS laat ingevoegde zoom-knop-iconen,
 * ver binnen dit block's eigen DOM. Bij dit block's 11px lettergrootte (var
 * op Google's eigen <div>'s) is dat exact 22px marge, die de iconen (absoluut
 * gepositioneerd en op het midden gecentreerd via transform, wat met een
 * margin van 0 rekent) 22px naar onder duwt — buiten de knop se eigen 40px
 * hoge, overflow-hidden box, dus zichtbaar alleen nog een piepklein flintertje
 * onderaan i.p.v. het hele icoon. Zelfde "not-prose"-patroon als acf/header/
 * acf/footer/acf/hero's broodkruimelpad hierboven al gebruiken — hun eigen
 * render.php-comments voor de volledige uitleg van die klasse.
 */

$height = $block['height'] ?? 'medium';
$rounded = ($block['rounded'] ?? true) !== false;
$grayscale = !empty($block['grayscale']);

// Google's eigen bereik is 0 (hele wereld) t/m 21 (straatniveau).
$zoom = max(1, min(21, (int) ($block['zoom'] ?? 15)));

// inc/functions/contact-address.php resp. inc/functions/google-maps-api-key.php
$address = ownframework_get_contact_address();
$api_key = ownframework_get_google_maps_api_key();

$missing = '';
if ($api_key === '') {
    $missing = __('Er is nog geen Google Maps API-sleutel ingesteld. Vul die in bij Thema opties → Koppelingen.', 'own-framework');
} elseif (!$address['has_coords']) {
    $missing = $address['text'] === ''
        ? __('Er is nog geen adres ingesteld. Vul dat in bij Thema opties → Algemeen → Contact.', 'own-framework')
        : __('Het adres heeft nog geen positie op de kaart. Open Thema opties → Algemeen → Contact en kies het adres opnieuw, of versleep de markering.', 'own-framework');
}

// Front-end: liever niets dan een leeg grijs vlak.
if ($missing !== '' && empty($is_preview)) {
    return;
}

// Vaste hoogtes i.p.v. een aspect-ratio: een kaart moet op een telefoon niet
// meegroeien tot een vierkant van schermbreedte, maar een herkenbare, beperkte
// strook blijven.
$height_class = match ($height) {
    'small' => 'h-[260px] sm:h-[320px]',
    'large' => 'h-[380px] sm:h-[560px]',
    default => 'h-[320px] sm:h-[440px]',
};

$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

// "grayscale" (Tailwind's CSS-filter) i.p.v. een echte Maps-kaartstijl: die
// laatste zou ook fijner zijn geweest, maar dit block heeft geen eigen
// marker-overlay om apart van te ontzien — een filter op de hele wrapper
// volstaat dus, geen aparte "styles"-array in google-map.js nodig.
$wrapper_class = trim(
    'own-google-maps not-prose relative overflow-hidden bg-gray-100 '
        . ($rounded ? 'rounded-2xl ' : '')
        . ($grayscale ? 'grayscale ' : '')
        . $align_class . ' '
        . $height_class
);

// $placeholder_class deelt de hoogte/vorm/uitlijning van de echte kaart
// hieronder, zodat de pagina-indeling in de editor niet verspringt zodra een
// editor 'm straks op de front-end wél ziet.
$placeholder_class = trim(
    'own-google-maps not-prose relative overflow-hidden bg-gray-100 text-gray-500 '
        . 'flex items-center justify-center '
        . ($rounded ? 'rounded-2xl ' : '')
        . $align_class . ' '
        . $height_class
);
?>
<?php if ($missing !== '') : ?>
    <?php // Alleen in de editor — zie de docblock hierboven. ?>
    <div <?= get_block_wrapper_attributes(['class' => 'own-google-maps not-prose rounded-2xl bg-gray-100 text-gray-500 p-6 text-sm text-center']); ?>>
        <?= esc_html($missing); ?>
    </div>
<?php elseif (!empty($is_preview)) : ?>
    <?php // Placeholder i.p.v. de echte kaart — zie de docblock hierboven. ?>
    <div <?= get_block_wrapper_attributes(['class' => $placeholder_class]); ?>>
        <div class="flex flex-col gap-2 items-center">
            <?= ownframework_get_icon('map-pin', 'w-8 h-8'); ?>
            <span class="text-sm"><?= esc_html($address['text']); ?></span>
            <span class="text-xs opacity-70"><?php esc_html_e('De kaart wordt alleen op de website zelf geladen.', 'own-framework'); ?></span>
        </div>
    </div>
<?php else : ?>
    <div
        <?= get_block_wrapper_attributes(['class' => $wrapper_class]); ?>
        data-own-google-map
        data-map-key="<?= esc_attr($api_key); ?>"
        data-map-lat="<?= esc_attr($address['lat']); ?>"
        data-map-lng="<?= esc_attr($address['lng']); ?>"
        data-map-zoom="<?= esc_attr((string) $zoom); ?>"
        data-map-title="<?= esc_attr($address['text']); ?>"></div>
<?php endif; ?>
