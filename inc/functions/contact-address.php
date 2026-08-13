<?php
defined('ABSPATH') || exit();

/**
 * Het adres van de "Algemeen"-optiepagina (Thema opties → Algemeen → Contact →
 * Adres) in één genormaliseerde vorm, gedeeld door blocks/footer-contact en
 * blocks/google-maps.
 *
 * Dat veld is van het type google_map (acf-json/group_own_general_settings
 * .json), dus get_field() geeft een ARRAY terug — met o.a. "address" (de door
 * Google geformatteerde adresregel), "lat", "lng" en "zoom". Deze functie
 * maakt daar één voorspelbare structuur van, zodat geen enkele consument zelf
 * hoeft te weten hoe dat veld eruitziet:
 *
 *   text       string    De volledige adresregel ("" als er geen adres is).
 *   lines      string[]  Diezelfde regel opgesplitst op komma's, voor een
 *                        adresblok dat als postadres moet ogen.
 *   lat, lng   string    Coördinaten, "" zonder speld op de kaart.
 *   has_coords bool      Of lat/lng bruikbaar zijn.
 *   maps_url   string    Kant-en-klare "open in Google Maps"-link ("" zonder
 *                        adres).
 *
 * De is_array()-check is nodig voor een adres dat nog als platte string in de
 * database staat, van vóór de veldtypewijziging en sindsdien niet opnieuw
 * opgeslagen: zonder die tak zou dat adres zonder waarschuwing verdwijnen.
 * Die string wordt naar hetzelfde komma-formaat gebracht dat Google zelf
 * gebruikt, zodat er verder maar één weergavepad nodig is i.p.v. twee.
 */
function ownframework_get_contact_address(): array
{
    // Meerdere blocks op één pagina vragen dit allemaal op; het adres kan
    // binnen één request niet veranderen.
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $contact = function_exists('get_field') ? (get_field('contact', 'option') ?: []) : [];
    $raw = is_array($contact) ? ($contact['address'] ?? '') : '';

    if (is_array($raw)) {
        $address = $raw;
        $text = trim((string) ($address['address'] ?? ''));
    } else {
        $address = [];
        $text = trim(wp_strip_all_tags(
            preg_replace('/\s*<br\s*\/?>\s*/i', ', ', (string) $raw)
        ));
    }

    $has_coords = isset($address['lat'], $address['lng'])
        && $address['lat'] !== ''
        && $address['lng'] !== '';

    // Coördinaten zijn preciezer dan de adrestekst: dat is exact de speld die
    // de editor op de kaart heeft gezet. Zonder coördinaten (of bij een oud,
    // getypt adres) alsnog gewoon op de tekst zoeken. "api=1" is Google's
    // eigen gedocumenteerde URL-vorm voor een zoekopdracht.
    $maps_query = $has_coords ? $address['lat'] . ',' . $address['lng'] : $text;

    $cache = [
        'text' => $text,
        'lines' => array_values(array_filter(
            array_map('trim', explode(',', $text)),
            fn($line) => $line !== ''
        )),
        'lat' => $has_coords ? (string) $address['lat'] : '',
        'lng' => $has_coords ? (string) $address['lng'] : '',
        'has_coords' => $has_coords,
        'maps_url' => $text === ''
            ? ''
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($maps_query),
    ];

    return $cache;
}
