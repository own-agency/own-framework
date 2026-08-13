<?php
defined('ABSPATH') || exit();

/**
 * acf/vacature-hero en acf/vacatures horen nergens anders te bestaan dan in
 * templates/archive-vacancy.html — beide binden aan de globale
 * $store.vacaturesFilters (src/js/scripts/vacatures-filter.js) en acf/
 * vacatures' eigen WP_Query/REST-koppeling (blocks/vacatures/hooks/
 * query.php) verwacht de vacature-archiefcontext, niet een willekeurige
 * pagina. Verbergt ze daarom uit de inserter overal BEHALVE als je die ene
 * template zelf bewerkt — zelfde patroon als blocks/header/hooks/
 * restrict-to-header-template.php, alleen voor een volledig template
 * (post_type "wp_template") in plaats van een template-onderdeel
 * ("wp_template_part").
 *
 * post_name (niet een ID): zolang niemand de template ooit aanpast heeft
 * templates/archive-vacancy.html geen eigen rij in de database — WordPress
 * synthetiseert dan een virtuele wp_template-post met post_name afgeleid van
 * de bestandsnaam. Zodra een editor 'm wel aanpast, krijgt de ECHTE,
 * opgeslagen versie dezelfde slug (WordPress matcht 'm zo terug op het
 * bestand) — post_name blijft dus in beide gevallen "archive-vacancy".
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
	$restricted_blocks = ['acf/vacature-hero', 'acf/vacatures'];

	$post = $editor_context->post ?? null;
	$is_archive_vacancy_template = $post
		&& $post->post_type === 'wp_template'
		&& $post->post_name === 'archive-vacancy';

	if ($is_archive_vacancy_template) {
		return $allowed_block_types;
	}

	if ($allowed_block_types === true) {
		$allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
	}

	return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
