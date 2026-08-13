<?php
defined('ABSPATH') || exit();

/**
 * Voedt SCF's eigen google_map-veldtype met de API-sleutel van de
 * "Koppelingen"-optiepagina (Thema opties → Koppelingen, acf-json/
 * ui_options_page_own_integrations.json).
 *
 * Zonder sleutel laadt SCF de Google Maps JS wel, maar weigert Google de
 * kaart: het veld toont dan een grijs vlak met "For development purposes
 * only" en de adres-zoekfunctie doet niets. Er is geen foutmelding in
 * WordPress zelf, alleen in de browserconsole — vandaar de admin-notice
 * onderaan dit bestand.
 *
 * "acf/settings/google_api_key" (niet acf_update_setting() op acf/init):
 * acf_get_setting() past dit filter pas toe op het moment dat het veld zijn
 * scripts inlaadt (admin_enqueue_scripts), ruim ná init. get_field() mag dan
 * veilig gebruikt worden — op acf/init zelf zijn de veldgroepen nog niet
 * gegarandeerd geladen, en dan zou de sleutel stilletjes leeg blijven.
 */

/**
 * Leest de sleutel, met een constante als terugval.
 *
 * De optiepagina wint bewust van de constante: de pagina is de plek die een
 * beheerder zelf kan bedienen, dus die moet nooit "niets doen" omdat er ergens
 * een constante staat. OWN_GOOGLE_MAPS_API_KEY (wp-config.php) is er voor het
 * omgekeerde geval — een sleutel per omgeving (staging/productie) meegeven
 * zonder 'm in de database te zetten, en zonder dat een klant 'm per ongeluk
 * kan wissen.
 */
function ownframework_get_google_maps_api_key(): string
{
    // Kan tijdens een enkel request meerdere keren aangeroepen worden (het
    // veld, plus de notice hieronder) — get_field() raakt dan telkens opnieuw
    // de options-cache, wat hier nergens voor nodig is.
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    // "maps_api_key" zit in de "google"-groep (acf-json/group_own_integrations_
    // settings.json). Een sub-field van een group is niet los op naam op te
    // vragen — de groep zelf ophalen en er de sleutel uit lezen, zelfde
    // patroon als blocks/footer-contact's eigen get_field('contact', 'option').
    $google = function_exists('get_field') ? get_field('google', 'option') : [];
    $stored = is_array($google) ? ($google['maps_api_key'] ?? '') : '';

    // get_field() geeft false terug voor een leeg/onbekend veld, geen string.
    $key = is_string($stored) ? trim($stored) : '';

    if ($key === '' && defined('OWN_GOOGLE_MAPS_API_KEY')) {
        $key = trim((string) OWN_GOOGLE_MAPS_API_KEY);
    }

    return $key;
}

add_filter('acf/settings/google_api_key', function ($key) {
    return ownframework_get_google_maps_api_key() ?: $key;
});

/**
 * Zonder deze notice is een ontbrekende sleutel alleen zichtbaar als een kapot
 * kaartje in het veld zelf — en juist niet op de pagina waar je 'm invult.
 * Alleen op de Koppelingen-pagina zelf, zodat dit nergens anders in de admin
 * in de weg staat.
 */
add_action('admin_notices', function () {
    $screen = get_current_screen();
    if (!$screen || !str_contains($screen->id, 'integrations')) {
        return;
    }

    if (ownframework_get_google_maps_api_key() !== '') {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html__(
            'Zonder Google Maps API-sleutel blijft een adresveld leeg: Google toont dan geen kaart en de adres-zoekfunctie werkt niet.',
            'own-framework'
        )
    );
});
