<?php
defined('ABSPATH') || exit();

/**
 * acf/header en acf/header-topbar (parts/header.html's enige 2 blocks) horen
 * nergens anders te bestaan dan in die ene template part — geen ander
 * template/pagina heeft er iets aan, en per ongeluk een 2e header ergens
 * midden in een pagina invoegen levert alleen verwarrende, dubbele markup
 * op. Verbergt ze daarom uit de inserter overal BEHALVE als je de "Header"
 * template part zelf bewerkt.
 *
 * allowed_block_types_all (niet het oudere, post-only allowed_block_types)
 * krijgt een WP_Block_Editor_Context mee die ook geldt voor de Site Editor's
 * eigen Templates/Template-onderdelen-canvas, niet alleen de klassieke
 * post/pagina-editor — dat dekt zowel "in een gewone pagina" als "in een
 * ANDER template zoals templates/page.html" in één keer.
 *
 * post_name (niet een ID): parts/header.html heeft, zolang niemand 'm ooit
 * aanpast, geen eigen rij in de database — WordPress synthetiseert dan een
 * virtuele wp_template_part-post met post_name afgeleid van de bestandsnaam.
 * Zodra een editor 'm wel aanpast, krijgt de ECHTE, opgeslagen versie
 * dezelfde slug (WordPress matcht 'm zo terug op het bestand) — post_name
 * blijft dus in beide gevallen "header".
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
	$restricted_blocks = ['acf/header', 'acf/header-topbar'];

	$post = $editor_context->post ?? null;
	$is_header_template_part = $post
		&& $post->post_type === 'wp_template_part'
		&& $post->post_name === 'header';

	if ($is_header_template_part) {
		return $allowed_block_types;
	}

	// `true` betekent "elk geregistreerd block is toegestaan" — pas dan pas
	// omzetten naar de volledige lijst, zodat er iets is om de 2 geblokkeerde
	// namen weer uit te filteren.
	if ($allowed_block_types === true) {
		$allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
	}

	return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
