<?php

/**
 * Ontwikkelaarsinstellingen — een zelfstandige module, zelfde reden als
 * live-search/ en notifications/: geen block en geen gedeelde markup, dus
 * noch blocks/ noch template-parts/ (root CLAUDE.md's plaatsingsregel).
 *
 * Instellingen die alleen het ontwikkelteam hoort te beheren, NIET een
 * klant-beheerder: bewust geen SCF options page onder Thema opties, want daar
 * regelt een rol-editor (User Role Editor) per pagina wie erbij mag, en een
 * klant met manage_options zou zichzelf hier toegang toe kunnen geven.
 *
 *   Multisite:  Netwerkbeheer → Ontwikkelaar (één pagina voor alle sites)
 *   Losse site: Gereedschap → Ontwikkelaar
 *
 * Wie "ontwikkelaar" is: zie ownframework_is_developer() in
 * includes/settings.php.
 */

defined('ABSPATH') || exit();

define('OWNFRAMEWORK_DEVELOPER_DIR', __DIR__);

require_once OWNFRAMEWORK_DEVELOPER_DIR . '/includes/settings.php';
require_once OWNFRAMEWORK_DEVELOPER_DIR . '/includes/post-types.php';
require_once OWNFRAMEWORK_DEVELOPER_DIR . '/includes/admin-page.php';
require_once OWNFRAMEWORK_DEVELOPER_DIR . '/includes/rest.php';
