<?php

/**
 * Live search — een zelfstandige module, geen block en geen markup die 2+
 * consumenten deelt, dus noch blocks/ noch template-parts/ past hier (zie
 * root CLAUDE.md's plaatsingsregel). functions.php's eigen autoload-array
 * verwacht dit bestand al op precies dit pad (/live-search/load.php) — deze
 * map hoort bij de site als geheel (eigen REST-route, eigen JS-store), ook al
 * is blocks/header/parts/search-modal.php momenteel de enige daadwerkelijke
 * gebruiker van wat 'm registreert.
 *
 * Configuratie hieronder, geen aparte instellingenpagina (zelfde reden als
 * root CLAUDE.md's "Brand identity"-sectie: een losse admin-UI voor iets dat
 * net zo goed een stuk code is, voegt alleen een tweede bron van waarheid
 * toe). Pas de array direct aan per project — post_types uitbreiden met een
 * eigen CPT, acf_fields vullen zodra een project een doorzoekbaar SCF-veld
 * heeft, etc.
 */

defined('ABSPATH') || exit();

define('OWNFRAMEWORK_LIVE_SEARCH_DIR', __DIR__);
define('OWNFRAMEWORK_LIVE_SEARCH_CONFIG', [

	'post_types' => ['post', 'page'],

	// Doorzocht ÉN teruggegeven in de resultaten, bv. ['ondertitel', 'locatie'].
	'acf_fields' => [],

	// Subset van acf_fields die getoond wordt in de resultaten (leeg = allemaal).
	'acf_display' => [],

	'per_page' => 6,

	// Minimaal aantal karakters voordat er gezocht wordt.
	'min_chars' => 2,

	// Debounce-vertraging in milliseconden.
	'debounce' => 300,

	'thumbnail' => true,
	'thumbnail_size' => 'thumbnail',

	// Post type-badge in de resultaten.
	'show_type_label' => true,

	// Highlight de gezochte term in titels en excerpts.
	'highlight' => true,

]);

require_once OWNFRAMEWORK_LIVE_SEARCH_DIR . '/includes/api.php';
require_once OWNFRAMEWORK_LIVE_SEARCH_DIR . '/includes/assets.php';
