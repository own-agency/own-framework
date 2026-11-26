<?php
defined('ABSPATH') || exit();

/**
 * Updates from the public GitHub repository's releases, through the normal
 * Dashboard → Updates screen (Netwerkbeheer → Updates on a multisite). The
 * release zip (built by .github/workflows/release.yml) is attached to each
 * release; Plugin Update Checker installs that zip, not the source tree.
 *
 * vendor/ only exists after `composer install` (locally) or in the release
 * zip, so without it the theme simply doesn't check for updates.
 *
 * A git checkout (a development copy, .git present) never checks either: an
 * update replaces the whole theme folder, .git and uncommitted work included.
 * The release zip has no .git, so live sites are unaffected. Filter
 * ownframework_check_for_updates to override.
 */

$ownframework_autoload = get_template_directory() . '/vendor/autoload.php';
$ownframework_check = apply_filters(
    'ownframework_check_for_updates',
    !file_exists(get_template_directory() . '/.git')
);

if ($ownframework_check && is_readable($ownframework_autoload)) {
    require_once $ownframework_autoload;

    $ownframework_updater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/own-agency/own-framework/',
        get_template_directory() . '/style.css',
        'own-framework'
    );

    // Require the built zip. The default ("prefer") falls back to GitHub's
    // source zipball when a release has no asset, which lacks assets/,
    // developer/build/ and vendor/: the site would lose its CSS and JS. 2 is
    // Vcs\Api::REQUIRE_RELEASE_ASSETS; the literal because that class lives
    // in a versioned namespace that changes with PUC updates.
    $ownframework_updater->getVcsApi()->enableReleaseAssets('/own-framework\.zip$/', 2);
}

unset($ownframework_autoload, $ownframework_check, $ownframework_updater);
