<?php
defined("ABSPATH") || exit();

/**
 * Removes Dashicons, emojis, embeds and other unused WordPress features.
 * Block/global styles stay enabled on purpose — the theme needs them for
 * editor/front-end parity (theme.json palette, layout sizes, core blocks).
 */

function ownframework_disable_dashicons()
{
    if (!is_admin_bar_showing() && !is_customize_preview()) {
        wp_deregister_style("dashicons");
    }
}
add_action("wp_enqueue_scripts", "ownframework_disable_dashicons");

function ownframework_disable_emojis()
{
    remove_action("wp_head", "print_emoji_detection_script", 7);
    remove_action("admin_print_scripts", "print_emoji_detection_script");
    remove_action("wp_print_styles", "print_emoji_styles");
    remove_action("admin_print_styles", "print_emoji_styles");
    remove_filter("the_content_feed", "wp_staticize_emoji");
    remove_filter("comment_text_rss", "wp_staticize_emoji");
    remove_filter("wp_mail", "wp_staticize_emoji_for_email");
    add_filter("tiny_mce_plugins", "ownframework_disable_emojis_tinymce");
}
add_action("init", "ownframework_disable_emojis");

function ownframework_disable_emojis_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, ["wpemoji"]);
    }
    return [];
}

function ownframework_disable_embeds()
{
    wp_deregister_script("wp-embed");
    remove_action("wp_head", "wp_oembed_add_discovery_links");
    remove_action("wp_head", "wp_oembed_add_host_js");
}
add_action("init", "ownframework_disable_embeds");

function ownframework_disable_jquery_migrate($scripts)
{
    if (!is_admin() && isset($scripts->registered["jquery"])) {
        $script = $scripts->registered["jquery"];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, ["jquery-migrate"]);
        }
    }
}
add_action("wp_default_scripts", "ownframework_disable_jquery_migrate");

function ownframework_disable_rss_feeds()
{
    wp_die(
        sprintf(
            __('No feed available, please visit the <a href="%s">homepage</a>!', "own-framework"),
            esc_url(home_url("/")),
        ),
    );
}
add_action("do_feed", "ownframework_disable_rss_feeds", 1);
add_action("do_feed_rdf", "ownframework_disable_rss_feeds", 1);
add_action("do_feed_rss", "ownframework_disable_rss_feeds", 1);
add_action("do_feed_rss2", "ownframework_disable_rss_feeds", 1);
add_action("do_feed_atom", "ownframework_disable_rss_feeds", 1);

remove_action("wp_head", "wlwmanifest_link");
remove_action("wp_head", "wp_shortlink_wp_head");
remove_filter("template_redirect", "redirect_canonical");

function ownframework_disable_comments()
{
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, "comments")) {
            remove_post_type_support($post_type, "comments");
            remove_post_type_support($post_type, "trackbacks");
        }
    }

    add_filter("comments_open", "__return_false", 20, 2);
    add_filter("pings_open", "__return_false", 20, 2);

    add_filter("comments_array", "__return_empty_array", 10, 2);

    add_action("admin_menu", function () {
        remove_menu_page("edit-comments.php");
    });

    add_action("wp_before_admin_bar_render", function () {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu("comments");
    });
}
add_action("init", "ownframework_disable_comments");

function ownframework_disable_attachment_pages()
{
    if (is_attachment()) {
        wp_redirect(home_url(), 301);
        exit();
    }
}
add_action("template_redirect", "ownframework_disable_attachment_pages");

function ownframework_disable_default_widgets()
{
    unregister_widget("WP_Widget_Pages");
    unregister_widget("WP_Widget_Calendar");
    unregister_widget("WP_Widget_Archives");
    unregister_widget("WP_Widget_Links");
    unregister_widget("WP_Widget_Meta");
    unregister_widget("WP_Widget_Search");
    unregister_widget("WP_Widget_Text");
    unregister_widget("WP_Widget_Categories");
    unregister_widget("WP_Widget_Recent_Posts");
    unregister_widget("WP_Widget_Recent_Comments");
    unregister_widget("WP_Widget_RSS");
    unregister_widget("WP_Widget_Tag_Cloud");
    unregister_widget("WP_Nav_Menu_Widget");
}
add_action("widgets_init", "ownframework_disable_default_widgets", 11);
