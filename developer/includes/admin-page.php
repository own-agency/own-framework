<?php
defined('ABSPATH') || exit();

/**
 * De pagina zelf. Multisite: Netwerkbeheer → Ontwikkelaar, één overzicht van
 * alle sites. Losse site: Gereedschap → Ontwikkelaar, alleen deze site.
 *
 * Het menu-item wordt alleen toegevoegd voor ownframework_is_developer();
 * de capability van het menu-item zelf is slechts een tweede slot.
 */

const OWNFRAMEWORK_DEV_PAGE = 'own-developer';

function ownframework_developer_page_url(array $args = []): string
{
    $base = is_multisite()
        ? network_admin_url('admin.php?page=' . OWNFRAMEWORK_DEV_PAGE)
        : admin_url('tools.php?page=' . OWNFRAMEWORK_DEV_PAGE);

    return add_query_arg($args, $base);
}

$ownframework_developer_menu = function (): void {
    if (!ownframework_is_developer()) {
        return;
    }

    $hook = is_multisite()
        ? add_menu_page('Ontwikkelaar', 'Ontwikkelaar', 'manage_network_options', OWNFRAMEWORK_DEV_PAGE, 'ownframework_developer_render_page', 'dashicons-hammer', 99)
        : add_management_page('Ontwikkelaar', 'Ontwikkelaar', 'manage_options', OWNFRAMEWORK_DEV_PAGE, 'ownframework_developer_render_page');

    add_action("admin_print_styles-{$hook}", function (): void {
        $file = OWNFRAMEWORK_DEVELOPER_DIR . '/assets/admin.css';
        wp_enqueue_style('own-developer', get_template_directory_uri() . '/developer/assets/admin.css', [], (string) filemtime($file));
    });
};
add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', $ownframework_developer_menu);
unset($ownframework_developer_menu);

/**
 * Sites met per post type de huidige stand en het aantal items, voor de
 * view. Op een losse site één "site": deze.
 */
function ownframework_developer_sites(): array
{
    $site_ids = is_multisite()
        ? get_sites(['number' => 0, 'deleted' => 0, 'orderby' => 'id', 'order' => 'ASC', 'fields' => 'ids'])
        : [get_current_blog_id()];

    $sites = [];
    foreach ($site_ids as $site_id) {
        $site_id = (int) $site_id;
        if (is_multisite()) {
            switch_to_blog($site_id);
        }

        $disabled = ownframework_disabled_post_types();
        $counts = ownframework_developer_post_counts();
        $post_types = [];
        foreach (array_keys(OWNFRAMEWORK_DEV_POST_TYPES) as $post_type) {
            $post_types[$post_type] = [
                'enabled' => !in_array($post_type, $disabled, true),
                'count' => $counts[$post_type] ?? 0,
            ];
        }

        $sites[] = [
            'id' => $site_id,
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'admin_url' => admin_url(),
            'is_main' => is_main_site(),
            'is_public' => (bool) get_option('blog_public'),
            // Een site met een ander theme heeft deze CPT's niet; de stand
            // wordt wel bewaard voor als het theme later wordt geactiveerd.
            'own_theme' => get_option('template') === get_template(),
            'post_types' => $post_types,
            'maps_key' => ownframework_google_maps_site_key(),
        ];

        if (is_multisite()) {
            restore_current_blog();
        }
    }

    return $sites;
}

/**
 * Items per post type op de huidige site. Een eigen query i.p.v.
 * wp_count_posts(): die geeft niets terug voor een type dat in DEZE request
 * niet geregistreerd is (Netwerkbeheer draait op de hoofdsite, met diens
 * theme en post types).
 *
 * @return array<string, int>
 */
function ownframework_developer_post_counts(): array
{
    global $wpdb;

    $post_types = array_keys(OWNFRAMEWORK_DEV_POST_TYPES);
    $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT post_type, COUNT(*) AS total FROM {$wpdb->posts}
         WHERE post_type IN ($placeholders) AND post_status NOT IN ('trash', 'auto-draft', 'inherit')
         GROUP BY post_type",
        $post_types
    ));

    return array_map('intval', wp_list_pluck($rows, 'total', 'post_type'));
}

function ownframework_developer_render_page(): void
{
    if (!ownframework_is_developer()) {
        wp_die('Deze pagina is alleen voor ontwikkelaars.', 403);
    }

    $sites = ownframework_developer_sites();
    $post_types = OWNFRAMEWORK_DEV_POST_TYPES;
    $switcher_non_public = ownframework_switcher_include_non_public_sites();
    $maps_network_key = ownframework_google_maps_network_key();
    $maps_constant_key = defined('OWN_GOOGLE_MAPS_API_KEY') && trim((string) OWN_GOOGLE_MAPS_API_KEY) !== '';
    $updated = isset($_GET['updated']);

    require OWNFRAMEWORK_DEVELOPER_DIR . '/includes/views/page.php';
}

add_action('admin_post_own_developer_save', function (): void {
    if (!ownframework_is_developer()) {
        wp_die('Deze pagina is alleen voor ontwikkelaars.', 403);
    }
    check_admin_referer('own_developer_save');

    if (is_multisite()) {
        update_site_option('ownframework_switcher_non_public', !empty($_POST['switcher_non_public']));
        if (isset($_POST['maps_network_key'])) {
            update_site_option('ownframework_google_maps_api_key', sanitize_text_field(wp_unslash($_POST['maps_network_key'])));
        }
    }

    // "sites[]" somt alle getoonde sites op, zodat een uitgevinkte switch
    // (die zelf niets post) als "uit" telt.
    $site_ids = array_map('intval', (array) ($_POST['sites'] ?? []));
    $enabled = (array) ($_POST['post_types'] ?? []);
    $maps_keys = (array) wp_unslash($_POST['maps_key'] ?? []);

    foreach ($site_ids as $site_id) {
        if (is_multisite() && !get_site($site_id)) {
            continue;
        }

        $site_enabled = array_keys((array) ($enabled[$site_id] ?? []));
        $disabled = array_values(array_diff(array_keys(OWNFRAMEWORK_DEV_POST_TYPES), $site_enabled));

        if (is_multisite()) {
            switch_to_blog($site_id);
        }

        if ($disabled !== ownframework_disabled_post_types()) {
            update_option('ownframework_disabled_post_types', $disabled);
            // Archief- en single-URL's komen of gaan: de site bouwt de
            // rewrite rules bij de eerstvolgende request opnieuw op, met de
            // post types zoals die op DIE site geregistreerd zijn.
            // flush_rewrite_rules() kan dat hier niet: binnen switch_to_blog()
            // staan nog de post types van de huidige request geregistreerd.
            delete_option('rewrite_rules');
        }

        // Alleen een veld dat echt gepost is: een formulier van vóór dit veld
        // bestond (open tabblad) mag geen sleutel wissen. Een lege waarde
        // betekent "netwerksleutel gebruiken" en laat de oude SCF-waarde niet
        // meer meetellen (ownframework_google_maps_site_key()).
        if (array_key_exists($site_id, $maps_keys)) {
            update_option('ownframework_google_maps_api_key', sanitize_text_field($maps_keys[$site_id]), false);
            delete_option('options_google_maps_api_key');
            delete_option('_options_google_maps_api_key');
        }

        if (is_multisite()) {
            restore_current_blog();
        }
    }

    wp_safe_redirect(ownframework_developer_page_url(['updated' => 1]));
    exit();
});
