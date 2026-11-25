<?php
defined('ABSPATH') || exit();

/**
 * Instagram-feed via OWN's eigen Meta-app ("Instagram API with Instagram
 * Login", scope instagram_business_basic). Gedeeld door blocks/instagram-feed
 * (leest de opgeslagen posts) en de ontwikkelaarspagina (koppelen, status).
 *
 * Opslag:
 *   ownframework_instagram_app       netwerk (site option): Meta-app ID + secret,
 *                                    terugval OWN_INSTAGRAM_APP_ID/_SECRET
 *   ownframework_instagram           per site: token, account, status (autoload:
 *                                    klein, en zo kost de cron-check op init
 *                                    geen extra query)
 *   ownframework_instagram_posts     per site: laatste GOEDE feed (geen autoload)
 *   uploads/own-instagram/<id>.jpg   per site: lokale kopieën van de beelden
 *
 * De pagina doet nooit zelf een API-call: een uurlijkse WP-Cron-taak per site
 * ververst token en posts, en het block toont alleen wat er opgeslagen is.
 * Beelden worden lokaal bewaard omdat Instagram's CDN-URL's ondertekend zijn
 * en na een paar dagen verlopen, en zodat bezoekers niets van Meta laden.
 * Mislukt een sync, dan blijft de vorige feed staan. Is het token 30 dagen
 * ongeldig (ook na een deauthorize of verwijderverzoek bij Meta), dan wist de
 * site alles zelf: ownframework_instagram_auto_remove().
 *
 * Tokens: long-lived, 60 dagen geldig. Verversen mag pas na 24 uur en geeft
 * weer 60 dagen; een verlopen token is niet meer te verversen, dan moet het
 * account opnieuw koppelen. Daarom wekelijks verversen, ruim binnen de marge.
 *
 * WP-Cron draait per site alleen bij verkeer op DIE site. Met DISABLE_WP_CRON
 * moet de server-cron elke site afgaan, niet alleen de hoofdsite.
 */

const OWNFRAMEWORK_IG_OPTION = 'ownframework_instagram';
const OWNFRAMEWORK_IG_POSTS_OPTION = 'ownframework_instagram_posts';
const OWNFRAMEWORK_IG_INVITES_OPTION = 'ownframework_instagram_invites';
const OWNFRAMEWORK_IG_REMOVED_OPTION = 'ownframework_instagram_removed';
const OWNFRAMEWORK_IG_REMOVE_AFTER = 30 * DAY_IN_SECONDS;
const OWNFRAMEWORK_IG_CRON = 'ownframework_instagram_sync';
const OWNFRAMEWORK_IG_MAX_POSTS = 12;
const OWNFRAMEWORK_IG_IMAGE_WIDTH = 640;
const OWNFRAMEWORK_IG_UPLOAD_DIR = 'own-instagram';
const OWNFRAMEWORK_IG_REST_NAMESPACE = 'own-framework/v1';

/* -------------------------------------------------------------------------
 * App-gegevens en URL's
 * ---------------------------------------------------------------------- */

/**
 * @return array{id: string, secret: string} Netwerkwaarde, anders de
 *         constanten uit wp-config.php (per omgeving, buiten de database).
 */
function ownframework_instagram_app(): array
{
    $stored = is_multisite()
        ? get_site_option('ownframework_instagram_app', [])
        : get_option('ownframework_instagram_app', []);
    $stored = is_array($stored) ? $stored : [];

    $id = trim((string) ($stored['id'] ?? ''));
    $secret = trim((string) ($stored['secret'] ?? ''));

    if ($id === '' && defined('OWN_INSTAGRAM_APP_ID')) {
        $id = trim((string) OWN_INSTAGRAM_APP_ID);
    }
    if ($secret === '' && defined('OWN_INSTAGRAM_APP_SECRET')) {
        $secret = trim((string) OWN_INSTAGRAM_APP_SECRET);
    }

    return ['id' => $id, 'secret' => $secret];
}

function ownframework_instagram_app_configured(): bool
{
    $app = ownframework_instagram_app();
    return $app['id'] !== '' && $app['secret'] !== '';
}

/**
 * De callback van dit netwerk: één route op de hoofdsite, dus één URI per
 * netwerk, niet één per subdomein. De state zegt voor welke site het is.
 */
function ownframework_instagram_callback_url(): string
{
    return get_rest_url(is_multisite() ? get_main_site_id() : null, OWNFRAMEWORK_IG_REST_NAMESPACE . '/instagram/callback');
}

/**
 * Centrale relay (developer/instagram-relay/), netwerkbreed of via
 * OWN_INSTAGRAM_RELAY_URL. Leeg = geen relay.
 */
function ownframework_instagram_relay_url(): string
{
    $url = is_multisite()
        ? (string) get_site_option('ownframework_instagram_relay_url', '')
        : (string) get_option('ownframework_instagram_relay_url', '');

    if ($url === '' && defined('OWN_INSTAGRAM_RELAY_URL')) {
        $url = (string) OWN_INSTAGRAM_RELAY_URL;
    }

    return esc_url_raw(trim($url));
}

/**
 * De redirect-URI zoals Meta 'm ziet. Meta accepteert alleen URI's die
 * letterlijk in de app staan. Met een relay is dat er één voor alle
 * installaties; zonder relay de callback van dit netwerk. Het
 * code-voor-token-verzoek moet exact dezelfde URI meesturen.
 */
function ownframework_instagram_redirect_uri(): string
{
    return ownframework_instagram_relay_url() ?: ownframework_instagram_callback_url();
}

/**
 * State voor de relay: "<payload>.<handtekening>". De payload bevat het
 * terugkeeradres; de relay stuurt alleen door als de HMAC (met de app-secret,
 * die elke site en de relay al hebben) klopt. Zo kan niemand de relay als
 * open redirect gebruiken.
 */
function ownframework_instagram_sign_state(string $return_url): string
{
    $payload = rtrim(strtr(base64_encode(wp_json_encode([
        'r' => $return_url,
        'n' => wp_generate_password(16, false),
        't' => time(),
    ])), '+/', '-_'), '=');
    $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, ownframework_instagram_app()['secret'], true)), '+/', '-_'), '=');

    return $payload . '.' . $signature;
}

/**
 * Start een koppeling: bewaart een eenmalige state (netwerkbreed, want de
 * callback draait op de hoofdsite) en geeft Instagram's inlogpagina terug.
 * De transient-sleutel is een hash: een relay-state is te lang voor een
 * optienaam.
 *
 * @param string $return 'developer' (ontwikkelaarspagina), 'site' (Thema opties → Instagram van die site) of
 *                       'invite' (eigen bedankpagina, voor de klant)
 */
function ownframework_instagram_authorize_url(int $blog_id, string $return, string $invite = ''): string
{
    $state = ownframework_instagram_relay_url() !== ''
        ? ownframework_instagram_sign_state(ownframework_instagram_callback_url())
        : wp_generate_password(32, false);

    set_site_transient('ownframework_ig_state_' . md5($state), [
        'blog_id' => $blog_id,
        'return' => $return,
        'invite' => $invite,
    ], 30 * MINUTE_IN_SECONDS);

    return 'https://www.instagram.com/oauth/authorize?' . http_build_query([
        'client_id' => ownframework_instagram_app()['id'],
        'redirect_uri' => ownframework_instagram_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'instagram_business_basic',
        'state' => $state,
    ]);
}

/* -------------------------------------------------------------------------
 * Koppellinks voor de klant
 *
 * Wie koppelt, moet zelf op het Instagram-account inloggen. Een koppellink
 * laat de school dat doen zonder wachtwoorden te delen. De link is het enige
 * slot: wie 'm heeft, kan elk Instagram-account aan die site hangen. Daarom
 * eenmalig en 14 dagen geldig.
 * ---------------------------------------------------------------------- */

/** @return array<string, array{blog_id: int, expires: int}> Alleen geldige. */
function ownframework_instagram_invites(): array
{
    $invites = is_multisite()
        ? get_site_option(OWNFRAMEWORK_IG_INVITES_OPTION, [])
        : get_option(OWNFRAMEWORK_IG_INVITES_OPTION, []);

    return array_filter((array) $invites, fn($invite) => is_array($invite) && (int) ($invite['expires'] ?? 0) > time());
}

function ownframework_instagram_save_invites(array $invites): void
{
    is_multisite()
        ? update_site_option(OWNFRAMEWORK_IG_INVITES_OPTION, $invites)
        : update_option(OWNFRAMEWORK_IG_INVITES_OPTION, $invites, false);
}

/** Maakt een nieuwe link voor de site en trekt een eventuele oude in. */
function ownframework_instagram_create_invite(int $blog_id): string
{
    $invites = array_filter(ownframework_instagram_invites(), fn($invite) => (int) $invite['blog_id'] !== $blog_id);
    $token = wp_generate_password(40, false);
    $invites[$token] = ['blog_id' => $blog_id, 'expires' => time() + 14 * DAY_IN_SECONDS];
    ownframework_instagram_save_invites($invites);

    return $token;
}

function ownframework_instagram_revoke_invites(int $blog_id): void
{
    ownframework_instagram_save_invites(
        array_filter(ownframework_instagram_invites(), fn($invite) => (int) $invite['blog_id'] !== $blog_id)
    );
}

/** @return array{url: string, expires: int}|null De geldige link van de site. */
function ownframework_instagram_invite_for(int $blog_id): ?array
{
    foreach (ownframework_instagram_invites() as $token => $invite) {
        if ((int) $invite['blog_id'] === $blog_id) {
            return [
                'url' => add_query_arg(
                    'invite',
                    $token,
                    get_rest_url(is_multisite() ? get_main_site_id() : null, OWNFRAMEWORK_IG_REST_NAMESPACE . '/instagram/connect')
                ),
                'expires' => (int) $invite['expires'],
            ];
        }
    }
    return null;
}

/* -------------------------------------------------------------------------
 * Koppeling van de huidige site
 * ---------------------------------------------------------------------- */

/**
 * @return array{token?: string, expires?: int, user_id?: string,
 *               username?: string, connected_at?: int, refreshed_at?: int,
 *               needs_reconnect?: bool, error?: string, error_at?: int,
 *               notified?: bool}
 */
function ownframework_instagram_connection(): array
{
    $connection = get_option(OWNFRAMEWORK_IG_OPTION, []);
    return is_array($connection) ? $connection : [];
}

function ownframework_instagram_is_connected(): bool
{
    return !empty(ownframework_instagram_connection()['token']);
}

function ownframework_instagram_needs_reconnect(): bool
{
    $connection = ownframework_instagram_connection();
    if (empty($connection['token'])) {
        return false;
    }
    return !empty($connection['needs_reconnect'])
        || (!empty($connection['expires']) && (int) $connection['expires'] < time());
}

/**
 * @return array{synced_at: int, username: string, posts: array<int, array{
 *               id: string, permalink: string, caption: string, type: string,
 *               timestamp: string, file: string, width: int, height: int}>}
 */
function ownframework_instagram_feed(): array
{
    $feed = get_option(OWNFRAMEWORK_IG_POSTS_OPTION, []);
    $feed = is_array($feed) ? $feed : [];

    return [
        'synced_at' => (int) ($feed['synced_at'] ?? 0),
        'username' => (string) ($feed['username'] ?? ''),
        'posts' => array_values((array) ($feed['posts'] ?? [])),
    ];
}

/**
 * Wisselt de code uit de callback om voor een long-lived token, slaat het
 * account op en haalt meteen de eerste posts op.
 */
function ownframework_instagram_connect_with_code(string $code): ?WP_Error
{
    $app = ownframework_instagram_app();

    $short = ownframework_instagram_request('POST', 'https://api.instagram.com/oauth/access_token', [
        'client_id' => $app['id'],
        'client_secret' => $app['secret'],
        'grant_type' => 'authorization_code',
        'redirect_uri' => ownframework_instagram_redirect_uri(),
        'code' => $code,
    ]);
    if (is_wp_error($short)) {
        return $short;
    }
    // Meta geeft dit antwoord zowel plat als verpakt in "data" terug.
    $short = isset($short['data'][0]) ? $short['data'][0] : $short;
    if (empty($short['access_token'])) {
        return new WP_Error('instagram', 'Instagram gaf geen toegangstoken terug.');
    }

    $long = ownframework_instagram_request('GET', 'https://graph.instagram.com/access_token', [
        'grant_type' => 'ig_exchange_token',
        'client_secret' => $app['secret'],
        'access_token' => $short['access_token'],
    ]);
    if (is_wp_error($long)) {
        return $long;
    }

    $me = ownframework_instagram_request('GET', 'https://graph.instagram.com/me', [
        'fields' => 'user_id,username',
        'access_token' => $long['access_token'],
    ]);
    if (is_wp_error($me)) {
        return $me;
    }

    $previous = ownframework_instagram_connection();
    if (($previous['user_id'] ?? '') !== (string) ($me['user_id'] ?? '')) {
        // Ander account: de posts van het vorige mogen niet blijven staan.
        ownframework_instagram_clear_feed();
    }

    update_option(OWNFRAMEWORK_IG_OPTION, [
        'token' => (string) $long['access_token'],
        'expires' => time() + (int) ($long['expires_in'] ?? 60 * DAY_IN_SECONDS),
        'user_id' => (string) ($me['user_id'] ?? ''),
        'username' => (string) ($me['username'] ?? ''),
        'connected_at' => time(),
        'refreshed_at' => time(),
        'needs_reconnect' => false,
        'error' => '',
        'error_at' => 0,
        'notified' => false,
        'broken_since' => 0,
    ], true);
    delete_option(OWNFRAMEWORK_IG_REMOVED_OPTION);

    ownframework_instagram_schedule();

    return ownframework_instagram_sync();
}

function ownframework_instagram_disconnect(): void
{
    delete_option(OWNFRAMEWORK_IG_OPTION);
    ownframework_instagram_clear_feed();
    ownframework_instagram_unschedule();
}

/**
 * Wist alles van een account waarvan het token al 30 dagen ongeldig is.
 * Een ingetrokken account (deauthorize of verwijderverzoek bij Meta) komt
 * altijd hier uit; zo is verwijderen niet afhankelijk van iemand die een
 * mail leest. Een school die alleen haar wachtwoord wijzigde, heeft 30 dagen
 * om opnieuw te koppelen en houdt zolang haar laatste posts.
 */
function ownframework_instagram_auto_remove(): void
{
    $connection = ownframework_instagram_connection();
    ownframework_instagram_disconnect();
    update_option(OWNFRAMEWORK_IG_REMOVED_OPTION, [
        'username' => (string) ($connection['username'] ?? ''),
        'at' => time(),
    ], false);
}

function ownframework_instagram_clear_feed(): void
{
    delete_option(OWNFRAMEWORK_IG_POSTS_OPTION);
    ownframework_instagram_prune_images([]);
}

/* -------------------------------------------------------------------------
 * Sync
 * ---------------------------------------------------------------------- */

/**
 * Ververst zo nodig het token en haalt de laatste posts op. Bij een fout
 * blijft de vorige feed staan en komt de fout op de ontwikkelaarspagina.
 */
function ownframework_instagram_sync(): ?WP_Error
{
    $connection = ownframework_instagram_connection();
    if (empty($connection['token'])) {
        return new WP_Error('instagram', 'Deze site is niet aan Instagram gekoppeld.');
    }

    $broken_since = (int) ($connection['broken_since'] ?? 0);
    if ($broken_since && $broken_since < time() - OWNFRAMEWORK_IG_REMOVE_AFTER) {
        ownframework_instagram_auto_remove();
        return new WP_Error('instagram', 'Het token was 30 dagen ongeldig. Posts en afbeeldingen zijn verwijderd.');
    }

    if (!empty($connection['expires']) && (int) $connection['expires'] < time()) {
        $error = new WP_Error('instagram', 'Het token is verlopen. Koppel het account opnieuw.', ['code' => 190]);
        ownframework_instagram_fail($error);
        return $error;
    }

    if (time() - (int) ($connection['refreshed_at'] ?? 0) > WEEK_IN_SECONDS) {
        $refreshed = ownframework_instagram_request('GET', 'https://graph.instagram.com/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $connection['token'],
        ]);
        if (is_wp_error($refreshed)) {
            ownframework_instagram_fail($refreshed);
            if (ownframework_instagram_is_token_error($refreshed)) {
                return $refreshed;
            }
            // Een tijdelijke fout: het oude token is nog geldig, ga door.
        } elseif (!empty($refreshed['access_token'])) {
            $connection['token'] = (string) $refreshed['access_token'];
            $connection['expires'] = time() + (int) ($refreshed['expires_in'] ?? 60 * DAY_IN_SECONDS);
            $connection['refreshed_at'] = time();
            update_option(OWNFRAMEWORK_IG_OPTION, $connection, true);
        }
    }

    $media = ownframework_instagram_request('GET', 'https://graph.instagram.com/me/media', [
        'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
        'limit' => OWNFRAMEWORK_IG_MAX_POSTS,
        'access_token' => $connection['token'],
    ]);
    if (is_wp_error($media)) {
        ownframework_instagram_fail($media);
        return $media;
    }

    $items = array_slice((array) ($media['data'] ?? []), 0, OWNFRAMEWORK_IG_MAX_POSTS);
    $posts = [];
    foreach ($items as $item) {
        // Van een video alleen de coverafbeelding; de feed speelt niets af.
        $source = ($item['media_type'] ?? '') === 'VIDEO'
            ? (string) ($item['thumbnail_url'] ?? '')
            : (string) ($item['media_url'] ?? '');
        if ($source === '' || empty($item['id'])) {
            continue;
        }

        $image = ownframework_instagram_store_image((string) $item['id'], $source);
        if (is_wp_error($image)) {
            continue;
        }

        $posts[] = [
            'id' => (string) $item['id'],
            'permalink' => (string) ($item['permalink'] ?? ''),
            'caption' => (string) ($item['caption'] ?? ''),
            'type' => (string) ($item['media_type'] ?? 'IMAGE'),
            'timestamp' => (string) ($item['timestamp'] ?? ''),
            'file' => $image['file'],
            'width' => $image['width'],
            'height' => $image['height'],
        ];
    }

    if (!$posts && $items) {
        $error = new WP_Error('instagram', 'De afbeeldingen konden niet worden opgeslagen in de uploads-map.');
        ownframework_instagram_fail($error);
        return $error;
    }

    update_option(OWNFRAMEWORK_IG_POSTS_OPTION, [
        'synced_at' => time(),
        'username' => (string) ($connection['username'] ?? ''),
        'posts' => $posts,
    ], false);
    ownframework_instagram_prune_images(wp_list_pluck($posts, 'file'));

    $connection = ownframework_instagram_connection();
    $connection['error'] = '';
    $connection['error_at'] = 0;
    $connection['needs_reconnect'] = false;
    $connection['notified'] = false;
    $connection['broken_since'] = 0;
    update_option(OWNFRAMEWORK_IG_OPTION, $connection, true);

    return null;
}

function ownframework_instagram_is_token_error(WP_Error $error): bool
{
    $data = (array) $error->get_error_data();
    return (int) ($data['code'] ?? 0) === 190;
}

/**
 * Legt de fout vast. Is het token ongeldig (code 190: verlopen, ingetrokken,
 * wachtwoord gewijzigd), dan één mail; niemand kijkt dagelijks naar de
 * ontwikkelaarspagina, en een feed die stil blijft staan valt pas na weken op.
 */
function ownframework_instagram_fail(WP_Error $error): void
{
    $connection = ownframework_instagram_connection();
    if (!$connection) {
        return;
    }

    $connection['error'] = $error->get_error_message();
    $connection['error_at'] = time();

    if (ownframework_instagram_is_token_error($error)) {
        $connection['needs_reconnect'] = true;
        if (empty($connection['broken_since'])) {
            $connection['broken_since'] = time();
        }

        if (empty($connection['notified'])) {
            $to = defined('OWN_INSTAGRAM_ALERT_EMAIL')
                ? (string) OWN_INSTAGRAM_ALERT_EMAIL
                : (is_multisite() ? (string) get_site_option('admin_email') : (string) get_option('admin_email'));

            if ($to !== '') {
                $site = get_bloginfo('name') ?: home_url('/');
                wp_mail(
                    $to,
                    sprintf('Instagram-koppeling verbroken: %s', $site),
                    sprintf(
                        "De Instagram-koppeling van %s (@%s) werkt niet meer:\n\n%s\n\nDe site toont de laatst opgehaalde posts tot het account opnieuw is gekoppeld. Gebeurt dat niet binnen 30 dagen, dan verwijdert de site de posts en afbeeldingen zelf. Maak een nieuwe koppellink via de ontwikkelaarspagina:\n%s",
                        $site,
                        $connection['username'] ?? '?',
                        $error->get_error_message(),
                        function_exists('ownframework_developer_page_url') ? ownframework_developer_page_url() : admin_url()
                    )
                );
            }
            $connection['notified'] = true;
        }
    }

    update_option(OWNFRAMEWORK_IG_OPTION, $connection, true);
}

/**
 * @return array|WP_Error Gedecodeerde JSON. Meta's fouten komen in twee
 *         vormen terug: Graph ({"error": {...}}) en api.instagram.com
 *         ({"error_type", "code", "error_message"}).
 */
function ownframework_instagram_request(string $method, string $url, array $params)
{
    $response = $method === 'POST'
        ? wp_remote_post($url, ['timeout' => 15, 'body' => $params])
        : wp_remote_get($url . '?' . http_build_query($params), ['timeout' => 15]);

    if (is_wp_error($response)) {
        return new WP_Error('instagram', 'Instagram is niet bereikbaar: ' . $response->get_error_message());
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['error']) && is_array($body['error'])) {
        return new WP_Error('instagram', (string) ($body['error']['message'] ?? 'Onbekende fout van Instagram.'), [
            'code' => (int) ($body['error']['code'] ?? 0),
        ]);
    }
    if (isset($body['error_message'])) {
        return new WP_Error('instagram', (string) $body['error_message'], [
            'code' => (int) ($body['code'] ?? 0),
        ]);
    }
    if ($status >= 400 || !is_array($body)) {
        return new WP_Error('instagram', sprintf('Onverwacht antwoord van Instagram (HTTP %d).', $status));
    }

    return $body;
}

/* -------------------------------------------------------------------------
 * Lokale beelden
 * ---------------------------------------------------------------------- */

/** @return array{path: string, url: string} Map van de huidige site. */
function ownframework_instagram_upload_dir(): array
{
    $uploads = wp_upload_dir(null, false);
    return [
        'path' => $uploads['basedir'] . '/' . OWNFRAMEWORK_IG_UPLOAD_DIR,
        'url' => $uploads['baseurl'] . '/' . OWNFRAMEWORK_IG_UPLOAD_DIR,
    ];
}

/**
 * Downloadt en verkleint één beeld. Een post verandert niet van beeld, dus
 * een bestaand bestand wordt hergebruikt: na de eerste sync downloadt elke
 * volgende alleen nieuwe posts.
 *
 * Bewust buiten de mediabibliotheek: dit zijn geen beelden die een redacteur
 * moet kunnen kiezen, en ze worden opgeruimd zodra ze uit de feed vallen.
 *
 * @return array{file: string, width: int, height: int}|WP_Error
 */
function ownframework_instagram_store_image(string $media_id, string $url)
{
    $media_id = preg_replace('/\D/', '', $media_id);
    $dir = ownframework_instagram_upload_dir()['path'];
    $file = $media_id . '.jpg';
    $dest = $dir . '/' . $file;

    if (file_exists($dest)) {
        $size = wp_getimagesize($dest);
        return ['file' => $file, 'width' => (int) ($size[0] ?? 0), 'height' => (int) ($size[1] ?? 0)];
    }

    if (!wp_mkdir_p($dir)) {
        return new WP_Error('instagram', 'Uploads-map niet beschrijfbaar.');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $tmp = download_url($url, 20);
    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $editor = wp_get_image_editor($tmp);
    if (is_wp_error($editor)) {
        @unlink($tmp);
        return $editor;
    }

    $editor->resize(OWNFRAMEWORK_IG_IMAGE_WIDTH, null, false);
    $editor->set_quality(82);
    $saved = $editor->save($dest, 'image/jpeg');
    @unlink($tmp);

    if (is_wp_error($saved)) {
        return $saved;
    }

    return ['file' => $file, 'width' => (int) $saved['width'], 'height' => (int) $saved['height']];
}

/** Verwijdert elk beeld dat niet (meer) in de feed staat. */
function ownframework_instagram_prune_images(array $keep): void
{
    $dir = ownframework_instagram_upload_dir()['path'];
    foreach (glob($dir . '/*.jpg') ?: [] as $path) {
        if (!in_array(basename($path), $keep, true)) {
            @unlink($path);
        }
    }
}

/* -------------------------------------------------------------------------
 * Cron
 * ---------------------------------------------------------------------- */

add_action(OWNFRAMEWORK_IG_CRON, 'ownframework_instagram_sync');

function ownframework_instagram_schedule(): void
{
    if (!wp_next_scheduled(OWNFRAMEWORK_IG_CRON)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', OWNFRAMEWORK_IG_CRON);
    }
}

function ownframework_instagram_unschedule(): void
{
    wp_clear_scheduled_hook(OWNFRAMEWORK_IG_CRON);
}

// Zet het event terug als het kwijt is, bijvoorbeeld na een database-import
// tussen omgevingen. Beide checks lezen alleen autoload-opties.
add_action('init', function (): void {
    if (ownframework_instagram_is_connected() && !wp_next_scheduled(OWNFRAMEWORK_IG_CRON)) {
        ownframework_instagram_schedule();
    }
});

/* -------------------------------------------------------------------------
 * REST: koppellink en callback
 *
 * Publiek (permission __return_true) omdat Instagram's redirect geen
 * WordPress-cookie-nonce meestuurt; de eenmalige state en de geheime
 * koppellink zijn de beveiliging. Beide routes sturen de browser door of
 * tonen een simpele pagina, geen JSON.
 * ---------------------------------------------------------------------- */

add_action('rest_api_init', function (): void {
    register_rest_route(OWNFRAMEWORK_IG_REST_NAMESPACE, '/instagram/connect', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'ownframework_instagram_rest_connect',
    ]);
    register_rest_route(OWNFRAMEWORK_IG_REST_NAMESPACE, '/instagram/callback', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => 'ownframework_instagram_rest_callback',
    ]);
});

function ownframework_instagram_rest_connect(WP_REST_Request $request): void
{
    $token = (string) $request->get_param('invite');
    $invite = $token !== '' ? (ownframework_instagram_invites()[$token] ?? null) : null;

    if (!$invite) {
        ownframework_instagram_page('Link verlopen', 'Deze koppellink is verlopen of al gebruikt. Vraag een nieuwe link aan bij OWN.');
    }
    if (!ownframework_instagram_app_configured()) {
        ownframework_instagram_page('Nog niet beschikbaar', 'De Instagram-koppeling is nog niet ingesteld. Neem contact op met OWN.');
    }

    wp_redirect(ownframework_instagram_authorize_url((int) $invite['blog_id'], 'invite', $token));
    exit();
}

function ownframework_instagram_rest_callback(WP_REST_Request $request): void
{
    $state_key = 'ownframework_ig_state_' . md5((string) $request->get_param('state'));
    $state = get_site_transient($state_key);
    delete_site_transient($state_key);

    if (!is_array($state)) {
        ownframework_instagram_page('Koppelen mislukt', 'Deze koppelpoging is verlopen. Begin opnieuw via de koppellink.');
    }

    $blog_id = (int) $state['blog_id'];
    $error = null;

    if ($request->get_param('error')) {
        $error = new WP_Error('instagram', 'Er is geen toestemming gegeven op Instagram.');
    } elseif (is_multisite() && !get_site($blog_id)) {
        $error = new WP_Error('instagram', 'De site bestaat niet meer.');
    } else {
        // Instagram plakt "#_" achter de code.
        $code = preg_replace('/#_$/', '', (string) $request->get_param('code'));

        if (is_multisite()) {
            switch_to_blog($blog_id);
        }
        $error = ownframework_instagram_connect_with_code($code);
        $site_name = get_bloginfo('name');
        if (is_multisite()) {
            restore_current_blog();
        }

        // Token binnen, maar de eerste sync mislukt (bijvoorbeeld een beeld
        // dat niet op te slaan is): de koppeling staat wél. Dan geldt het als
        // gekoppeld; de sync-fout staat in de status op beide pagina's en de
        // cron probeert het elk uur opnieuw.
        if ($error && ownframework_instagram_connected_on($blog_id)) {
            $error = null;
        }
        if (!$error) {
            ownframework_instagram_revoke_invites($blog_id);
        }
    }

    if ($state['return'] === 'developer' && function_exists('ownframework_developer_page_url')) {
        if ($error) {
            set_site_transient('ownframework_ig_notice', $error->get_error_message(), 5 * MINUTE_IN_SECONDS);
        }
        wp_safe_redirect(ownframework_developer_page_url(['instagram' => $error ? 'fout' : 'gekoppeld', 'site' => $blog_id]) . '#instagram');
        exit();
    }

    // Thema opties → Instagram van de site zelf. wp_redirect, niet
    // wp_safe_redirect: op een subdomein-netwerk is dat een ander domein dan
    // de hoofdsite waar deze callback draait. De URL komt uit de database,
    // niet uit het verzoek.
    if ($state['return'] === 'site' && function_exists('ownframework_instagram_site_page_url')) {
        if ($error) {
            set_site_transient('ownframework_ig_notice', $error->get_error_message(), 5 * MINUTE_IN_SECONDS);
        }
        wp_redirect(ownframework_instagram_site_page_url($blog_id, ['instagram' => $error ? 'fout' : 'gekoppeld']));
        exit();
    }

    if ($error) {
        ownframework_instagram_page('Koppelen mislukt', $error->get_error_message() . ' Probeer het opnieuw via dezelfde link, of neem contact op met OWN.');
    }

    ownframework_instagram_page(
        'Instagram is gekoppeld',
        sprintf('Je Instagram-account is gekoppeld aan %s. De nieuwste posts verschijnen nu op de website. Je kunt dit venster sluiten.', $site_name ?? '')
    );
}

/**
 * Gekoppeld, ook als de eerste sync mislukte (dan staat het token er wel en
 * haalt de cron de posts later op).
 */
function ownframework_instagram_connected_on(int $blog_id): bool
{
    if (is_multisite()) {
        switch_to_blog($blog_id);
    }
    $connected = ownframework_instagram_is_connected();
    if (is_multisite()) {
        restore_current_blog();
    }
    return $connected;
}

/** Een kale HTML-pagina vanuit een REST-route. Stopt het verzoek. */
function ownframework_instagram_page(string $title, string $text): void
{
    status_header(200);
    header('Content-Type: text/html; charset=utf-8');
    nocache_headers();
    printf(
        '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>%1$s</title>'
            . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:16px;font:16px/1.5 system-ui,sans-serif;color:#1d2327;background:#f6f7f7}main{max-width:440px;padding:32px;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)}h1{margin:0 0 8px;font-size:20px}p{margin:0;color:#50575e}</style>'
            . '</head><body><main><h1>%1$s</h1><p>%2$s</p></main></body></html>',
        esc_html($title),
        esc_html($text)
    );
    exit();
}
