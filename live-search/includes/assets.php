<?php
defined('ABSPATH') || exit();

/**
 * Localizes onto "theme-scripts" (inc/functions/enqueue-scripts-styles.php's
 * own already-enqueued Vite bundle) rather than registering a separate stub
 * handle just to hang wp_localize_script off of, the way a plugin-style drop-
 * in normally would — this theme only ever ships ONE JS bundle, and
 * src/js/scripts/live-search.js (bundled into it) already reads
 * window.OwnLiveSearch at runtime. wp_localize_script's inline "-js-extra"
 * script prints inline (not deferred) at this handle's position regardless
 * of "theme-scripts" carrying the defer attribute itself, so the global is
 * always in place before the deferred bundle executes.
 */
add_action('wp_enqueue_scripts', function () {
	$config = OWNFRAMEWORK_LIVE_SEARCH_CONFIG;

	wp_localize_script('theme-scripts', 'OwnLiveSearch', [
		'endpoint' => rest_url('own-live-search/v1/query'),
		'nonce' => wp_create_nonce('wp_rest'),
		'minChars' => (int) $config['min_chars'],
		'debounce' => (int) $config['debounce'],
		'highlight' => (bool) $config['highlight'],
	]);
});
