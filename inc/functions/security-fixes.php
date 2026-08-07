<?php
defined("ABSPATH") || exit();

/**
 * Security Fixes
 */

// XML-RPC: brute-force target, unused here.
add_filter("xmlrpc_enabled", "__return_false");

// Version and discovery meta in <head>: fingerprinting aids, no functional use.
remove_action("wp_head", "rsd_link");
remove_action("wp_head", "wlwmanifest_link");
remove_action("wp_head", "wp_generator");

// Feeds: unused, and a scraping surface.
function ownframework_disable_feeds()
{
    if (is_feed()) {
        wp_redirect(home_url(), 301);
        exit();
    }
    remove_action("wp_head", "feed_links", 2);
    remove_action("wp_head", "feed_links_extra", 3);
}
add_action("template_redirect", "ownframework_disable_feeds", 1);
add_filter("feed_links_show_comments_feed", "__return_false");

// wp-links-opml.php serves the deprecated blogroll feature.
function ownframework_disable_links_opml()
{
    if (strpos($_SERVER["REQUEST_URI"], "wp-links-opml.php") !== false) {
        wp_redirect(home_url(), 301);
        exit();
    }
}
add_action("init", "ownframework_disable_links_opml");

// Trackbacks and pingbacks.
add_filter("xmlrpc_methods", function ($methods) {
    unset($methods["pingback.ping"]);
    unset($methods["pingback.extensions.getPingbacks"]);
    return $methods;
});
// Self-pingbacks.
add_action("pre_ping", function (&$links) {
    $home = get_option("home");
    foreach ($links as $l => $link) {
        if (0 === strpos($link, $home)) {
            unset($links[$l]);
        }
    }
});

// Remove X-Pingback header
add_filter(
    "wp_headers",
    function ($headers) {
        unset($headers["X-Pingback"]);
        return $headers;
    },
    10,
    1,
);

// Do NOT gate the REST API on is_user_logged_in(). Two routes are deliberately
// open to guests (permission_callback => __return_true) and are called from the
// front end: live-search/v1/query and ownframework/v1/onderzoek ("Toon meer").

// No theme/plugin file editing from wp-admin.
if (!defined("DISALLOW_FILE_EDIT")) {
    define("DISALLOW_FILE_EDIT", true);
}
