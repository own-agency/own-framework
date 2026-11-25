<?php
defined('ABSPATH') || exit();

/**
 * Thema opties → Instagram: de koppeling van DEZE site, voor de klant zelf.
 * Capability manage_theme_instagram (inc/functions/capabilities.php, stap 3),
 * dus per rol aan of uit te zetten in User Role Editor, zoals de andere
 * Thema opties-pagina's.
 *
 * De Meta-app (ID, secret, relay) blijft op de ontwikkelaarspagina: die geldt
 * voor alle sites en een fout daar breekt alles. Zonder ingestelde app staat
 * de pagina er wel, met alleen de melding dat OWN de koppeling nog instelt:
 * zo weet de klant waar het straks zit.
 *
 * Wie op "Instagram koppelen" klikt, logt in DEZE browser in op Instagram.
 * Beheert iemand anders het account, dan maakt de pagina een koppellink om
 * door te sturen.
 */

const OWNFRAMEWORK_IG_PAGE = 'instagram';
const OWNFRAMEWORK_IG_CAP = 'manage_theme_instagram';
const OWNFRAMEWORK_IG_SITE_ACTIONS = ['connect', 'invite', 'sync', 'disconnect'];

function ownframework_instagram_site_page_url(?int $blog_id = null, array $args = []): string
{
    $url = $blog_id && is_multisite()
        ? get_admin_url($blog_id, 'admin.php?page=' . OWNFRAMEWORK_IG_PAGE)
        : admin_url('admin.php?page=' . OWNFRAMEWORK_IG_PAGE);

    return add_query_arg($args, $url);
}

function ownframework_instagram_site_action_url(string $action): string
{
    return wp_nonce_url(
        add_query_arg('action', 'own_instagram_site_' . $action, admin_url('admin-post.php')),
        'own_instagram_site_' . $action
    );
}

// Na SCF (prioriteit 99), anders bestaat het Thema opties-menu nog niet.
// options-page-order.php zet niet-SCF-items achteraan: Instagram komt onderaan.
//
// Niet 'theme-options' als ouder: die pagina heeft "redirect", en dan hangt
// SCF het menu op onder de slug van de eerste subpagina ("general"). Onder
// 'theme-options' verschijnt het item nergens, zonder foutmelding. Daarom de
// ouder van een bestaande Thema opties-pagina overnemen, en wel uit
// acf_get_options_pages(): acf_get_options_page() (enkelvoud) geeft de
// onopgeloste 'theme-options' terug.
add_action('admin_menu', function (): void {
    $parent = 'theme-options';
    if (function_exists('acf_get_options_pages')) {
        foreach ((array) acf_get_options_pages() as $page) {
            if (($page['menu_slug'] ?? '') === 'styles' && !empty($page['parent_slug'])) {
                $parent = $page['parent_slug'];
                break;
            }
        }
    }
    add_submenu_page($parent, 'Instagram', 'Instagram', OWNFRAMEWORK_IG_CAP, OWNFRAMEWORK_IG_PAGE, 'ownframework_instagram_render_site_page');
}, 150);

foreach (OWNFRAMEWORK_IG_SITE_ACTIONS as $ownframework_ig_site_action) {
    add_action('admin_post_own_instagram_site_' . $ownframework_ig_site_action, function () use ($ownframework_ig_site_action): void {
        if (!current_user_can(OWNFRAMEWORK_IG_CAP)) {
            wp_die('Je hebt geen toegang tot de Instagram-koppeling.', 403);
        }
        check_admin_referer('own_instagram_site_' . $ownframework_ig_site_action);

        $blog_id = get_current_blog_id();
        $result = 'ok';

        if (!ownframework_instagram_app_configured()) {
            wp_die('De Instagram-koppeling is nog niet ingesteld. Neem contact op met OWN.', 400);
        }

        switch ($ownframework_ig_site_action) {
            case 'connect':
                wp_redirect(ownframework_instagram_authorize_url($blog_id, 'site'));
                exit();

            case 'invite':
                ownframework_instagram_create_invite($blog_id);
                $result = 'link';
                break;

            case 'sync':
                $error = ownframework_instagram_sync();
                if ($error) {
                    set_site_transient('ownframework_ig_notice', $error->get_error_message(), 5 * MINUTE_IN_SECONDS);
                    $result = 'fout';
                } else {
                    $result = 'ververst';
                }
                break;

            case 'disconnect':
                ownframework_instagram_disconnect();
                ownframework_instagram_revoke_invites($blog_id);
                $result = 'ontkoppeld';
                break;
        }

        wp_safe_redirect(ownframework_instagram_site_page_url(null, ['instagram' => $result]));
        exit();
    });
}
unset($ownframework_ig_site_action);

function ownframework_instagram_render_site_page(): void
{
    if (!current_user_can(OWNFRAMEWORK_IG_CAP)) {
        wp_die('Je hebt geen toegang tot deze pagina.', 403);
    }

    if (!ownframework_instagram_app_configured()) {
        ?>
        <div class="wrap">
            <h1>Instagram</h1>
            <p class="description" style="max-width:640px">
                Hier koppel je straks het Instagram-account van deze website, om de nieuwste posts te tonen met het blok "Instagram-feed".
            </p>
            <div class="notice notice-info inline"><p>OWN heeft de Instagram-koppeling voor deze website nog niet ingesteld. Zodra dat gebeurd is, kun je hier je account koppelen.</p></div>
        </div>
        <?php
        return;
    }

    $blog_id = get_current_blog_id();
    $connection = ownframework_instagram_connection();
    $feed = ownframework_instagram_feed();
    $connected = !empty($connection['token']);
    $needs_reconnect = ownframework_instagram_needs_reconnect();
    $error = (string) ($connection['error'] ?? '');
    $remove_at = !empty($connection['broken_since']) ? (int) $connection['broken_since'] + OWNFRAMEWORK_IG_REMOVE_AFTER : 0;
    $removed = (array) get_option(OWNFRAMEWORK_IG_REMOVED_OPTION, []);
    $invite = ownframework_instagram_invite_for($blog_id);
    $result = sanitize_key($_GET['instagram'] ?? '');

    $notices = [
        'gekoppeld' => ['success', 'Instagram is gekoppeld. De nieuwste posts staan klaar voor het blok "Instagram-feed".'],
        'ververst' => ['success', 'De posts zijn ververst.'],
        'ontkoppeld' => ['success', 'Instagram is ontkoppeld. De posts en afbeeldingen zijn van de website verwijderd.'],
        'link' => ['success', 'De koppellink staat hieronder. Stuur hem naar de beheerder van het Instagram-account.'],
        'fout' => ['error', ''],
    ];
    if ($result === 'fout') {
        $notices['fout'][1] = (string) get_site_transient('ownframework_ig_notice') ?: 'Er ging iets mis bij Instagram. Probeer het opnieuw.';
        delete_site_transient('ownframework_ig_notice');
    }
    ?>
    <div class="wrap">
        <h1>Instagram</h1>
        <p class="description" style="max-width:640px">
            Koppel het Instagram-account van deze website om de nieuwste posts te tonen met het blok "Instagram-feed". De website haalt elk uur zelf nieuwe posts op. Alleen zakelijke en creator-accounts kunnen gekoppeld worden.
        </p>

        <?php if (isset($notices[$result])) : ?>
            <div class="notice notice-<?php echo esc_attr($notices[$result][0]); ?> is-dismissible"><p><?php echo esc_html($notices[$result][1]); ?></p></div>
        <?php endif; ?>

        <?php if ($connected && $needs_reconnect) : ?>
            <div class="notice notice-warning inline"><p>
                <strong>De koppeling werkt niet meer.</strong>
                <?php echo esc_html($error); ?>
                De website toont voorlopig de laatst opgehaalde posts.
                <?php if ($remove_at) : ?>
                    <?php echo esc_html(sprintf('Koppel opnieuw vóór %s, anders worden ze verwijderd.', wp_date('j F Y', $remove_at))); ?>
                <?php endif; ?>
            </p></div>
        <?php elseif ($connected && $error !== '') : ?>
            <div class="notice notice-warning inline"><p><strong>De laatste keer ophalen is mislukt.</strong> <?php echo esc_html($error); ?> De website probeert het elk uur opnieuw.</p></div>
        <?php elseif (!$connected && !empty($removed['at'])) : ?>
            <div class="notice notice-info inline"><p><?php echo esc_html(sprintf('@%s was 30 dagen niet bereikbaar. De posts en afbeeldingen zijn op %s verwijderd. Koppel het account opnieuw om de feed terug te zetten.', $removed['username'] ?? '?', wp_date('j F Y', (int) $removed['at']))); ?></p></div>
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Account</th>
                <td>
                    <?php if ($connected) : ?>
                        <strong>@<?php echo esc_html((string) ($connection['username'] ?? '')); ?></strong>
                        <p class="description">
                            <?php
                            echo esc_html(sprintf(
                                '%d posts opgehaald%s.',
                                count($feed['posts']),
                                $feed['synced_at'] ? ', laatst ' . human_time_diff($feed['synced_at']) . ' geleden' : ''
                            ));
                            ?>
                        </p>
                    <?php else : ?>
                        Niet gekoppeld
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo $connected ? 'Beheer' : 'Koppelen'; ?></th>
                <td>
                    <p>
                        <a class="button <?php echo !$connected || $needs_reconnect ? 'button-primary' : ''; ?>" href="<?php echo esc_url(ownframework_instagram_site_action_url('connect')); ?>">
                            <?php echo !$connected ? 'Instagram koppelen' : ($needs_reconnect ? 'Opnieuw koppelen' : 'Ander account koppelen'); ?>
                        </a>
                        <?php if ($connected && !$needs_reconnect) : ?>
                            <a class="button" href="<?php echo esc_url(ownframework_instagram_site_action_url('sync')); ?>">Nu verversen</a>
                        <?php endif; ?>
                        <?php if ($connected) : ?>
                            <a class="button button-link-delete" href="<?php echo esc_url(ownframework_instagram_site_action_url('disconnect')); ?>" onclick="return confirm('Instagram ontkoppelen? De feed verdwijnt van de website.');">Ontkoppelen</a>
                        <?php endif; ?>
                    </p>
                    <p class="description">Je logt in bij Instagram met het account dat je op de website wilt tonen.</p>
                </td>
            </tr>
            <?php if (!$connected || $needs_reconnect) : ?>
                <tr>
                    <th scope="row">Beheert iemand anders het account?</th>
                    <td>
                        <?php if ($invite) : ?>
                            <input type="text" class="large-text code" value="<?php echo esc_attr($invite['url']); ?>" readonly onclick="this.select()" aria-label="Koppellink">
                            <p class="description"><?php echo esc_html(sprintf('Stuur deze link naar de beheerder van het Instagram-account. Hij werkt één keer en is geldig tot %s.', wp_date('j F Y', $invite['expires']))); ?></p>
                            <p><a class="button" href="<?php echo esc_url(ownframework_instagram_site_action_url('invite')); ?>">Nieuwe link maken</a></p>
                        <?php else : ?>
                            <p><a class="button" href="<?php echo esc_url(ownframework_instagram_site_action_url('invite')); ?>">Koppellink maken</a></p>
                            <p class="description">Een link waarmee de beheerder van het account zelf inlogt. Je hoeft geen wachtwoorden te delen.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php
}
