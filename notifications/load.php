<?php

/**
 * Site-wide notification cards — een zelfstandige module, geen block en geen
 * markup die 2+ consumenten deelt, dus noch blocks/ noch template-parts/
 * past hier (root CLAUDE.md's plaatsingsregel), zelfde reden als live-search/
 * eigen load.php. functions.php's autoload-array verwacht dit bestand al op
 * precies dit pad (/notifications/load.php).
 *
 * Content komt uit de "Melding(en)" options page (acf-json/
 * ui_options_page_6a7c7f38b048a.json + group_6a7db031097d5.json,
 * menu_slug "notifications") — editorial content, geen config-array zoals
 * live-search/load.php's eigen OWNFRAMEWORK_LIVE_SEARCH_CONFIG, want een
 * editor zonder code-toegang moet zelf meldingen kunnen toevoegen/verbergen.
 */

defined('ABSPATH') || exit();

define('OWNFRAMEWORK_NOTIFICATIONS_DIR', __DIR__);

// Geen includes/assets.php zoals live-search/ heeft: er is niets om te
// wp_localize_script'en — de kaarten zelf dragen hun data-notification-id al
// (render.php), en het dismissen leunt puur op sessionStorage in de browser,
// geen REST-endpoint of config die eerst naar JS moet.
require_once OWNFRAMEWORK_NOTIFICATIONS_DIR . '/includes/render.php';
