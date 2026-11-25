<?php
defined('ABSPATH') || exit();

/**
 * Acties van het Instagram-blok op de ontwikkelaarspagina (Koppelingen):
 * zelf koppelen, koppellink maken, nu verversen, ontkoppelen. Allemaal per
 * site, via admin-post.php met een nonce per actie en site. De API-logica
 * zelf staat in inc/functions/instagram-feed.php.
 */

const OWNFRAMEWORK_IG_ACTIONS = ['connect', 'invite', 'sync', 'disconnect'];

function ownframework_instagram_action_url(string $action, int $blog_id): string
{
    return wp_nonce_url(
        add_query_arg(['action' => 'own_instagram_' . $action, 'site' => $blog_id], admin_url('admin-post.php')),
        'own_instagram_' . $action . '_' . $blog_id
    );
}

/**
 * Status per site voor de view. Draait binnen switch_to_blog() vanuit
 * ownframework_developer_sites().
 */
function ownframework_instagram_site_status(int $blog_id): array
{
    $connection = ownframework_instagram_connection();
    $feed = ownframework_instagram_feed();

    return [
        'connected' => !empty($connection['token']),
        'needs_reconnect' => ownframework_instagram_needs_reconnect(),
        'username' => (string) ($connection['username'] ?? ''),
        'expires' => (int) ($connection['expires'] ?? 0),
        'error' => (string) ($connection['error'] ?? ''),
        'error_at' => (int) ($connection['error_at'] ?? 0),
        'synced_at' => $feed['synced_at'],
        'post_count' => count($feed['posts']),
        'invite' => ownframework_instagram_invite_for($blog_id),
        'remove_at' => !empty($connection['broken_since']) ? (int) $connection['broken_since'] + OWNFRAMEWORK_IG_REMOVE_AFTER : 0,
        'removed' => (array) get_option(OWNFRAMEWORK_IG_REMOVED_OPTION, []),
    ];
}

foreach (OWNFRAMEWORK_IG_ACTIONS as $ownframework_ig_action) {
    add_action('admin_post_own_instagram_' . $ownframework_ig_action, function () use ($ownframework_ig_action): void {
        if (!ownframework_is_developer()) {
            wp_die('Deze pagina is alleen voor ontwikkelaars.', 403);
        }

        $blog_id = (int) ($_GET['site'] ?? 0);
        check_admin_referer('own_instagram_' . $ownframework_ig_action . '_' . $blog_id);

        if (!is_multisite()) {
            $blog_id = get_current_blog_id();
        } elseif (!get_site($blog_id)) {
            wp_die('Onbekende site.', 404);
        }

        if (in_array($ownframework_ig_action, ['connect', 'invite'], true) && !ownframework_instagram_app_configured()) {
            set_site_transient('ownframework_ig_notice', 'Vul eerst het app-ID en de app-secret van de Meta-app in.', 5 * MINUTE_IN_SECONDS);
            wp_safe_redirect(ownframework_developer_page_url(['instagram' => 'fout', 'site' => $blog_id]) . '#instagram');
            exit();
        }

        $result = 'ok';

        switch ($ownframework_ig_action) {
            case 'connect':
                // Instagram's inlogscherm verschijnt in DEZE browser: alleen
                // handig als je zelf toegang hebt tot het account.
                wp_redirect(ownframework_instagram_authorize_url($blog_id, 'developer'));
                exit();

            case 'invite':
                ownframework_instagram_create_invite($blog_id);
                $result = 'link';
                break;

            case 'sync':
            case 'disconnect':
                if (is_multisite()) {
                    switch_to_blog($blog_id);
                }
                if ($ownframework_ig_action === 'sync') {
                    $error = ownframework_instagram_sync();
                    if ($error) {
                        set_site_transient('ownframework_ig_notice', $error->get_error_message(), 5 * MINUTE_IN_SECONDS);
                        $result = 'fout';
                    } else {
                        $result = 'ververst';
                    }
                } else {
                    ownframework_instagram_disconnect();
                    $result = 'ontkoppeld';
                }
                if (is_multisite()) {
                    restore_current_blog();
                }
                if ($ownframework_ig_action === 'disconnect') {
                    ownframework_instagram_revoke_invites($blog_id);
                }
                break;
        }

        wp_safe_redirect(ownframework_developer_page_url(['instagram' => $result, 'site' => $blog_id]) . '#instagram');
        exit();
    });
}
unset($ownframework_ig_action);
