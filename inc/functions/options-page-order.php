<?php
defined('ABSPATH') || exit();

/**
 * Zet de submenu-items onder een SCF-optiepagina alsnog in de volgorde van hun
 * eigen "position"-waarde.
 *
 * Zonder dit klopt die volgorde niet betrouwbaar, en dat ligt niet aan SCF maar
 * aan WordPress zelf: de "position" die SCF doorgeeft aan add_submenu_page() is
 * geen SORTEERSLEUTEL maar een INVOEGPOSITIE in de submenu-array zoals die op
 * dát moment is (wp-admin/includes/plugin.php):
 *
 *     if ( null === $position || ! isset( $submenu[ $parent ] )
 *          || $position >= count( $submenu[ $parent ] ) ) {
 *         $submenu[ $parent ][] = $new_sub_menu;   // gewoon achteraan
 *     }
 *
 * Een position die groter of gelijk is aan het aantal reeds geregistreerde
 * items wordt dus volledig genegeerd — het item gaat achteraan. Alleen een
 * position KLEINER dan dat aantal voegt echt in. Het eindresultaat hangt
 * daarmee net zo goed af van de volgorde waarin de pagina's geregistreerd
 * worden, en die is voor local-JSON-pagina's de laadvolgorde van de bestanden
 * (alfabetisch op bestandsnaam), niet de volgorde van hun position.
 *
 * Concreet ging het daardoor mis met "Koppelingen" (3) en "Live search" (2),
 * geregistreerd in de bestandsvolgorde Algemeen, Koppelingen, Live search,
 * Stijlen:
 *
 *   Algemeen (0)    → array leeg          → achteraan  [Algemeen]
 *   Koppelingen (3) → 3 >= 1, genegeerd   → achteraan  [Algemeen, Koppelingen]
 *   Live search (2) → 2 >= 2, genegeerd   → achteraan  [..., Live search]
 *   Stijlen (1)     → 1 < 3               → op index 1 [Algemeen, Stijlen, ...]
 *
 * Resultaat: Algemeen, Stijlen, Koppelingen(3), Live search(2) — een 3 boven
 * een 2. Achteraf sorteren is dan de enige betrouwbare oplossing: het is niet
 * op te lossen door "betere" position-waarden te kiezen, want elke waarde
 * blijft afhankelijk van de registratievolgorde.
 *
 * Prioriteit 200: SCF registreert zijn optiepagina's op admin_menu prioriteit
 * 99 (includes/admin/class-acf-admin-options-page.php), dus dit moet daarna.
 */
add_action('admin_menu', function () {
    global $submenu;

    if (!function_exists('acf_get_options_pages')) {
        return;
    }

    $pages = acf_get_options_pages();
    if (empty($pages)) {
        return;
    }

    // Per ouderpagina: menu_slug => gewenste position.
    $positions = [];
    foreach ($pages as $page) {
        $parent = $page['parent_slug'] ?? '';
        if ($parent === '' || $parent === 'none') {
            continue;
        }
        $positions[$parent][$page['menu_slug']] = (int) ($page['position'] ?? 0);
    }

    foreach ($positions as $parent => $map) {
        if (empty($submenu[$parent])) {
            continue;
        }

        /**
         * Een submenu-item is [menu_title, capability, menu_slug, page_title, ...].
         *
         * Een item met de slug van de ouder zelf is de "overzicht"-regel die
         * WordPress bovenaan een submenu zet; die hoort altijd eerst te blijven
         * staan, ongeacht positions. Een item dat hier verder niet in $map
         * voorkomt (door een plugin toegevoegd, geen SCF-optiepagina) krijgt de
         * hoogst mogelijke rang: die schuiven naar achteren i.p.v. tussen de
         * eigen pagina's te belanden. usort is sinds PHP 8.0 stabiel, dus
         * onderling houden ze hun bestaande volgorde.
         */
        $rank = static function (array $item) use ($map, $parent): int {
            $slug = $item[2] ?? '';
            if ($slug === $parent) {
                return PHP_INT_MIN;
            }
            return $map[$slug] ?? PHP_INT_MAX;
        };

        usort($submenu[$parent], static fn(array $a, array $b): int => $rank($a) <=> $rank($b));

        // WordPress leest dit als een gewone lijst; na usort zijn de sleutels
        // al 0..n, maar array_values() maakt dat expliciet en onafhankelijk van
        // wat er hierboven mee gebeurd is.
        $submenu[$parent] = array_values($submenu[$parent]);
    }
}, 200);
