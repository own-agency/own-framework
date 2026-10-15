<?php
defined('ABSPATH') || exit();

/**
 * Voedt SCF's eigen google_map-veldtype (en blocks/google-maps) met de
 * Google Maps API-sleutel. Beheerd op de ontwikkelaarspagina (Netwerkbeheer →
 * Ontwikkelaar → Koppelingen, developer/), niet meer door de klant.
 *
 * Zonder sleutel laadt SCF de Google Maps JS wel, maar weigert Google de
 * kaart: het veld toont dan een grijs vlak met "For development purposes
 * only" en de adres-zoekfunctie doet niets — zonder foutmelding in WordPress
 * zelf. De ontwikkelaarspagina toont daarom per site of er een sleutel is.
 *
 * "acf/settings/google_api_key" (niet acf_update_setting() op acf/init):
 * acf_get_setting() past dit filter pas toe op het moment dat het veld zijn
 * scripts inlaadt, ruim ná init.
 */

/**
 * Eigen sleutel van de huidige site, of ''.
 *
 * options_google_maps_api_key is waar de vroegere SCF-pagina (Thema opties →
 * Koppelingen) 'm opsloeg. Die geldt tot de site via de ontwikkelaarspagina
 * wordt opgeslagen; dat zet 'm om naar de eigen optie en ruimt de oude op.
 */
function ownframework_google_maps_site_key(): string
{
    $key = get_option('ownframework_google_maps_api_key', null);
    if ($key === null) {
        $key = get_option('options_google_maps_api_key', '');
    }
    return trim((string) $key);
}

/**
 * Netwerksleutel: één Cloud-project voor alle sites van het netwerk. Op een
 * losse site bestaat dit niveau niet.
 */
function ownframework_google_maps_network_key(): string
{
    return is_multisite() ? trim((string) get_site_option('ownframework_google_maps_api_key', '')) : '';
}

/**
 * De sleutel die deze site gebruikt: eigen sleutel → netwerksleutel →
 * OWN_GOOGLE_MAPS_API_KEY (wp-config.php).
 *
 * De constante is de laatste terugval, voor een sleutel per omgeving
 * (staging/productie) zonder 'm in de database te zetten.
 */
function ownframework_get_google_maps_api_key(): string
{
    $key = ownframework_google_maps_site_key() ?: ownframework_google_maps_network_key();

    if ($key === '' && defined('OWN_GOOGLE_MAPS_API_KEY')) {
        $key = trim((string) OWN_GOOGLE_MAPS_API_KEY);
    }

    return $key;
}

add_filter('acf/settings/google_api_key', function ($key) {
    return ownframework_get_google_maps_api_key() ?: $key;
});
