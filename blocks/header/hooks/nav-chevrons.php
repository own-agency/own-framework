<?php
defined('ABSPATH') || exit();

/**
 * Appends a chevron icon (media/icons/chevron-down.svg / chevron-right.svg)
 * to primary-menu items that have children — only for the desktop dropdown
 * nav in render.php, flagged via its own `own_show_chevrons` arg on
 * wp_nav_menu() (both the desktop nav and the mobile menu's flat, always-
 * expanded list in parts/mobile-menu.php share theme_location "primary-menu",
 * so theme_location alone can't tell them apart — the mobile list has no
 * dropdown behaviour to indicate, so it never sets this flag and never gets
 * a chevron). wp_nav_menu() casts its $args to an object before handing it
 * to the walker, so a custom key set there is read back as $args->own_show_chevrons
 * here, same as any of its built-in ones.
 *
 * `own_chevron_max_depth` (render.php, only set for the mega-menu submenu
 * style) caps how deep this still fires: a mega menu's depth-1 items become
 * column titles, not a further flyout trigger, so they shouldn't get the
 * depth>0 chevron-right that dropdown mode uses to hint "opens sideways".
 * Left unset (dropdown mode, and every other wp_nav_menu() call in the
 * theme), every depth with children still gets one, same as before.
 */
add_filter('nav_menu_item_title', function (string $title, $item, $args, int $depth): string {
    if (empty($args->own_show_chevrons)) {
        return $title;
    }

    if (!in_array('menu-item-has-children', (array) $item->classes, true)) {
        return $title;
    }

    if (isset($args->own_chevron_max_depth) && $depth > (int) $args->own_chevron_max_depth) {
        return $title;
    }

    $icon = $depth === 0 ? 'chevron-down' : 'chevron-right';

    return $title . ownframework_get_icon($icon, 'own-header-menu-chevron w-5 h-5');
}, 10, 4);
