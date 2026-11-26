<?php
defined('ABSPATH') || exit();

/**
 * De pagina zelf. Multisite: Netwerkbeheer → Ontwikkelaar, één overzicht van
 * alle sites. Losse site: Gereedschap → Ontwikkelaar, alleen deze site.
 *
 * De pagina is een React-app (developer/src, gebouwd naar developer/build met
 * wp-scripts) op @wordpress/components, zodat hij eruitziet en werkt als
 * WordPress zelf. Hij leest en schrijft via de REST-route in rest.php.
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

    add_action("load-{$hook}", function (): void {
        add_action('admin_enqueue_scripts', 'ownframework_developer_enqueue');
    });
};
add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', $ownframework_developer_menu);
unset($ownframework_developer_menu);

/**
 * Laadt de gebouwde app met de afhankelijkheden die wp-scripts in
 * index.asset.php vastlegde: React en de componenten komen uit WordPress
 * zelf, niets dubbel gebundeld.
 */
function ownframework_developer_enqueue(): void
{
    $asset_file = OWNFRAMEWORK_DEVELOPER_DIR . '/build/index.asset.php';
    if (!is_readable($asset_file)) {
        return; // ownframework_developer_render_page() meldt het.
    }

    $asset = require $asset_file;
    $uri = get_template_directory_uri() . '/developer';

    wp_enqueue_script('own-developer', $uri . '/build/index.js', $asset['dependencies'], $asset['version'], true);
    wp_add_inline_script('own-developer', 'window.ownDeveloper = ' . wp_json_encode([
        'isMultisite' => is_multisite(),
        'version' => (string) wp_get_theme(get_template())->get('Version'),
    ]) . ';', 'before');

    $css = OWNFRAMEWORK_DEVELOPER_DIR . '/assets/admin.css';
    wp_enqueue_style('wp-components');
    wp_enqueue_style('own-developer', $uri . '/assets/admin.css', ['wp-components'], (string) filemtime($css));
}

function ownframework_developer_render_page(): void
{
    if (!ownframework_is_developer()) {
        wp_die('Deze pagina is alleen voor ontwikkelaars.', 403);
    }

    if (!is_readable(OWNFRAMEWORK_DEVELOPER_DIR . '/build/index.asset.php')) {
        echo '<div class="wrap"><h1>Ontwikkelaar</h1><div class="notice notice-error"><p>De build van deze pagina ontbreekt. Draai <code>pnpm build</code> in het thema, of installeer de release-zip.</p></div></div>';
        return;
    }

    echo '<div class="wrap own-developer"><div id="own-developer-root"></div></div>';
}

/**
 * Sites met per post type de huidige stand en het aantal items. Op een losse
 * site één "site": deze.
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
            'adminUrl' => admin_url(),
            'isMain' => is_main_site(),
            'isPublic' => (bool) get_option('blog_public'),
            // Een site met een ander theme heeft deze CPT's niet; de stand
            // wordt wel bewaard voor als het theme later wordt geactiveerd.
            'ownTheme' => get_option('template') === get_template(),
            'postTypes' => $post_types,
            'mapsKey' => ownframework_google_maps_site_key(),
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

/**
 * De hele pagina in één antwoord, voor de app (GET en na opslaan).
 */
function ownframework_developer_state(): array
{
    $post_types = [];
    foreach (OWNFRAMEWORK_DEV_POST_TYPES as $slug => $config) {
        $post_types[] = ['slug' => $slug, 'label' => $config['label']];
    }

    return [
        'isMultisite' => is_multisite(),
        'postTypes' => $post_types,
        'sites' => ownframework_developer_sites(),
        'switcherNonPublic' => ownframework_switcher_include_non_public_sites(),
        'mapsNetworkKey' => ownframework_google_maps_network_key(),
        'mapsConstantKey' => defined('OWN_GOOGLE_MAPS_API_KEY') && trim((string) OWN_GOOGLE_MAPS_API_KEY) !== '',
    ];
}

/**
 * Slaat op wat er gestuurd is; een ontbrekende sleutel blijft zoals hij was,
 * zodat een open tabblad met een oudere versie van de pagina niets wist.
 *
 * @param array $data {
 *     @type bool   $switcherNonPublic Alleen multisite.
 *     @type string $mapsNetworkKey    Alleen multisite.
 *     @type array  $sites             [{id, postTypes: {slug: bool}, mapsKey}]
 * }
 */
function ownframework_developer_save(array $data): void
{
    if (is_multisite()) {
        if (array_key_exists('switcherNonPublic', $data)) {
            update_site_option('ownframework_switcher_non_public', (bool) $data['switcherNonPublic']);
        }
        if (array_key_exists('mapsNetworkKey', $data)) {
            update_site_option('ownframework_google_maps_api_key', sanitize_text_field((string) $data['mapsNetworkKey']));
        }
    }

    foreach ((array) ($data['sites'] ?? []) as $site) {
        $site_id = (int) ($site['id'] ?? 0);
        if (is_multisite() ? !get_site($site_id) : $site_id !== get_current_blog_id()) {
            continue;
        }

        if (is_multisite()) {
            switch_to_blog($site_id);
        }

        if (isset($site['postTypes']) && is_array($site['postTypes'])) {
            $current = ownframework_disabled_post_types();
            $disabled = $current;
            foreach ($site['postTypes'] as $post_type => $enabled) {
                if (!isset(OWNFRAMEWORK_DEV_POST_TYPES[$post_type])) {
                    continue;
                }
                $disabled = $enabled
                    ? array_values(array_diff($disabled, [$post_type]))
                    : array_values(array_unique(array_merge($disabled, [$post_type])));
            }
            sort($disabled);
            sort($current);

            if ($disabled !== $current) {
                update_option('ownframework_disabled_post_types', $disabled);
                // Archief- en single-URL's komen of gaan: de site bouwt de
                // rewrite rules bij de eerstvolgende request opnieuw op, met de
                // post types zoals die op DIE site geregistreerd zijn.
                // flush_rewrite_rules() kan dat hier niet: binnen
                // switch_to_blog() staan nog de post types van de huidige
                // request geregistreerd.
                delete_option('rewrite_rules');
            }
        }

        // Een lege waarde betekent "netwerksleutel gebruiken" en laat de oude
        // SCF-waarde niet meer meetellen (ownframework_google_maps_site_key()).
        if (array_key_exists('mapsKey', $site)) {
            update_option('ownframework_google_maps_api_key', sanitize_text_field((string) $site['mapsKey']), false);
            delete_option('options_google_maps_api_key');
            delete_option('_options_google_maps_api_key');
        }

        if (is_multisite()) {
            restore_current_blog();
        }
    }
}
