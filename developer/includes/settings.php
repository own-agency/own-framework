<?php
defined('ABSPATH') || exit();

/**
 * Opslag en getters van de ontwikkelaarsinstellingen.
 *
 *   ownframework_disabled_post_types   per site (blog option), lijst met
 *                                      uitgeschakelde post type slugs
 *   ownframework_switcher_non_public   netwerkbreed (site option), bool
 *
 * Uitgeschakeld i.p.v. ingeschakeld opslaan is bewust: een site zonder
 * opgeslagen waarde, of een CPT die later aan het theme wordt toegevoegd,
 * staat daardoor gewoon aan.
 */

// Post types die per site uit te zetten zijn, met de blocks die alleen voor
// dat type bestaan (worden verborgen in de inserter en renderen niets) en de
// Thema opties-pagina's die erbij horen (menu_slug).
const OWNFRAMEWORK_DEV_POST_TYPES = [
    'vacancy' => [
        'label' => 'Vacatures',
        'blocks' => [
            'acf/vacature-hero',
            'acf/vacatures',
            'acf/vacature-single-hero',
            'acf/vacature-apply',
            'acf/vacature-share',
            'acf/vacatures-uitgelicht',
        ],
        'options_pages' => ['vacature-taxonomieen'],
    ],
    'agenda' => [
        'label' => 'Agenda',
        'blocks' => [
            'acf/agenda-hero',
            'acf/agenda',
            'acf/agenda-single-hero',
            'acf/agenda-aanmelden',
            'acf/agenda-share',
            'acf/agenda-uitgelicht',
        ],
        'options_pages' => [],
    ],
    'verhaal' => [
        'label' => 'Verhalen',
        'blocks' => [
            'acf/verhaal-hero',
            'acf/verhalen',
            'acf/verhaal-single-hero',
            'acf/verhaal-share',
            'acf/verhalen-uitgelicht',
        ],
        'options_pages' => [],
    ],
    'faq' => [
        'label' => "FAQ's",
        'blocks' => ['acf/faqs'],
        'options_pages' => [],
    ],
];

/**
 * Wie de ontwikkelaarsinstellingen mag zien en wijzigen.
 *
 * Altijd een super admin (op een losse site: iedere beheerder met
 * delete_users, zo definieert WP dat daar). Staat OWNFRAMEWORK_DEVELOPERS in
 * wp-config.php, dan alleen de super admins wiens gebruikersnaam of e-mail
 * daarin staat — nodig zodra een klant zelf ook super admin is:
 *
 *   define('OWNFRAMEWORK_DEVELOPERS', ['ties', 'dev@ownagency.nl']);
 */
function ownframework_is_developer(): bool
{
    if (!is_user_logged_in() || !is_super_admin()) {
        return false;
    }

    if (!defined('OWNFRAMEWORK_DEVELOPERS')) {
        return true;
    }

    $user = wp_get_current_user();
    $allowed = array_map('strtolower', (array) OWNFRAMEWORK_DEVELOPERS);

    return in_array(strtolower($user->user_login), $allowed, true)
        || in_array(strtolower($user->user_email), $allowed, true);
}

/**
 * @return string[] Uitgeschakelde post type slugs van de huidige (of de
 *                  opgegeven) site.
 */
function ownframework_disabled_post_types(?int $blog_id = null): array
{
    $value = $blog_id && is_multisite()
        ? get_blog_option($blog_id, 'ownframework_disabled_post_types', [])
        : get_option('ownframework_disabled_post_types', []);

    return array_values(array_intersect((array) $value, array_keys(OWNFRAMEWORK_DEV_POST_TYPES)));
}

function ownframework_post_type_disabled(string $post_type): bool
{
    return in_array($post_type, ownframework_disabled_post_types(), true);
}

/**
 * Site-wisselaar (acf/footer-topbar): ook sites tonen die in Netwerkbeheer →
 * Sites nog niet als "Public" staan. Netwerkbreed, want het gaat over welke
 * sites van het netwerk zichtbaar zijn, niet over één site.
 */
function ownframework_switcher_include_non_public_sites(): bool
{
    return (bool) get_site_option('ownframework_switcher_non_public', false);
}
