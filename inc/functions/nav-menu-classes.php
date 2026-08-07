<?php
defined("ABSPATH") || exit();

/**
 * Lets any wp_nav_menu() call pass per-depth/per-item classes and attributes
 * as plain args (e.g. `li_class_1`, `a_atts_order_3`, `submenu_class_0`)
 * instead of writing a custom Walker per menu. Shared across every
 * wp_nav_menu() call in the theme (header, footer, ...), not one block's
 * concern — hence inc/functions/, not a per-block hooks folder.
 */

function ownframework_nav_menu_has_attribute($variable, $attribute)
{
    return (is_object($variable) && property_exists($variable, $attribute)) ||
        (is_array($variable) && array_key_exists($attribute, $variable));
}

function ownframework_nav_menu_get_attribute($variable, $attribute, $default = null)
{
    if (is_object($variable)) {
        return property_exists($variable, $attribute) ? $variable->$attribute : $default;
    } elseif (is_array($variable)) {
        return array_key_exists($attribute, $variable) ? $variable[$attribute] : $default;
    }

    return $default;
}

// WordPress core escapes menu CSS classes, which mangles Tailwind classes
// containing ":" (e.g. "hover:text-primary"). Author them as
// "hover___text-primary" and this restores the colons.
// @link https://core.trac.wordpress.org/ticket/33924
function ownframework_nav_menu_fix_classes($classes)
{
    $patterns = apply_filters('nav_menu_css_class_unescape_patterns', '/___/');
    $replacements = apply_filters('nav_menu_css_class_unescape_replacements', ':');

    return array_map(function ($cssclass) use ($patterns, $replacements) {
        return preg_replace($patterns, $replacements, $cssclass);
    }, $classes);
}

function ownframework_nav_menu_array_or_string_classes($prop, $args)
{
    $classes = [];
    if (ownframework_nav_menu_has_attribute($args, $prop)) {
        $temp_classes = ownframework_nav_menu_get_attribute($args, $prop);
        if (is_string($temp_classes)) {
            $temp_classes = explode(' ', $temp_classes);
        }
        $classes = array_merge($classes, $temp_classes);
    }

    return $classes;
}

function ownframework_nav_menu_merge_attributes($atts, $new_atts)
{
    $new_classes = ownframework_nav_menu_array_or_string_classes('class', $new_atts);

    if (!empty($new_classes)) {
        $original_classes = ownframework_nav_menu_array_or_string_classes('class', $atts);
        $classes = ownframework_nav_menu_fix_classes(array_merge($original_classes, $new_classes));
        $new_atts['class'] = implode(' ', $classes);
    }

    return array_merge($atts, $new_atts);
}

function ownframework_nav_menu_build_attributes($prefix, $atts, $args, $depth, $index = -1)
{
    if (ownframework_nav_menu_has_attribute($args, "{$prefix}_atts")) {
        $atts = ownframework_nav_menu_merge_attributes($atts, ownframework_nav_menu_get_attribute($args, "{$prefix}_atts"));
    }
    if (ownframework_nav_menu_has_attribute($args, "{$prefix}_atts_{$depth}")) {
        $atts = ownframework_nav_menu_merge_attributes($atts, ownframework_nav_menu_get_attribute($args, "{$prefix}_atts_{$depth}"));
    }
    if ($index !== -1 && ownframework_nav_menu_has_attribute($args, "{$prefix}_atts_order_{$index}")) {
        $atts = ownframework_nav_menu_merge_attributes($atts, ownframework_nav_menu_get_attribute($args, "{$prefix}_atts_order_{$index}"));
    }

    if (empty($atts['class'])) {
        $atts['class'] = '';
    }

    return $atts;
}

function ownframework_nav_menu_build_classes($prefix, $atts, $args, $depth, $index = -1)
{
    $classes = explode(' ', ownframework_nav_menu_get_attribute($atts, 'class', ''));

    $classes = array_merge($classes, ownframework_nav_menu_array_or_string_classes("{$prefix}_class", $args));
    $classes = array_merge($classes, ownframework_nav_menu_array_or_string_classes("{$prefix}_class_{$depth}", $args));
    if ($index !== -1) {
        $classes = array_merge($classes, ownframework_nav_menu_array_or_string_classes("{$prefix}_class_order_{$index}", $args));
    }

    $atts['class'] = implode(' ', ownframework_nav_menu_fix_classes($classes));

    return $atts;
}

add_filter('nav_menu_link_attributes', function ($atts, $item, $args, $depth) {
    $index = $item->menu_order;
    $atts = ownframework_nav_menu_build_attributes('a', $atts, $args, $depth, $index);
    $atts = ownframework_nav_menu_build_classes('a', $atts, $args, $depth, $index);
    return $atts;
}, 10, 4);

add_filter('nav_menu_item_attributes', function ($atts, $item, $args, $depth) {
    $index = $item->menu_order;
    $atts = ownframework_nav_menu_build_attributes('li', $atts, $args, $depth, $index);
    $atts = ownframework_nav_menu_build_classes('li', $atts, $args, $depth, $index);
    return $atts;
}, 10, 4);

add_filter('nav_menu_submenu_attributes', function ($atts, $args, $depth) {
    $atts = ownframework_nav_menu_build_attributes('submenu', $atts, $args, $depth);
    $atts = ownframework_nav_menu_build_classes('submenu', $atts, $args, $depth);
    return $atts;
}, 10, 3);
