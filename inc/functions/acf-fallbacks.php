<?php
defined("ABSPATH") || exit();

/**
 * Template files call get_field()/have_rows()/etc. directly, with no
 * function_exists() guard at each call site. If Secure Custom Fields is ever
 * deactivated, those calls would otherwise throw "call to undefined
 * function" and take down the entire front end instead of just losing the
 * dynamic field content. These no-op fallbacks only kick in when the real
 * functions aren't already defined.
 */

if (!function_exists("get_field")) {
    function get_field($selector, $post_id = false, $format_value = true)
    {
        return null;
    }
}

if (!function_exists("get_sub_field")) {
    function get_sub_field($selector, $format_value = true)
    {
        return null;
    }
}

if (!function_exists("have_rows")) {
    function have_rows($selector, $post_id = false)
    {
        return false;
    }
}

if (!function_exists("the_row")) {
    function the_row($format_values = false)
    {
        return false;
    }
}

if (!function_exists("get_row_layout")) {
    function get_row_layout()
    {
        return null;
    }
}
