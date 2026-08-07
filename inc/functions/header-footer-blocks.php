<?php
defined('ABSPATH') || exit();

/**
 * acf/header, acf/header-topbar, acf/footer, acf/footer-bottom and acf/
 * footer-cta/-menu/-contact only make sense inside parts/header.html and
 * parts/footer.html — inserting acf/footer, say, into a normal page would
 * render a 2nd, orphaned <footer> with its own logo/menu/contact fields to
 * fill in, never what an editor actually wants. "ancestor" (their own
 * block.json) already keeps the -cta/-menu/-contact children out of the
 * inserter everywhere except inside acf/footer itself, but acf/header/acf/
 * footer/acf/header-topbar/acf/footer-bottom have no ancestor to lock to —
 * they're the top of their own tree — so they need hiding a different way:
 * removed from the allowed list entirely, except while editing a template
 * or template part (Ontwerp ▸ Templates/Template-onderdelen), where they
 * belong.
 *
 * allowed_block_types_all (not "ancestor"/"parent" in block.json) because
 * this has to key off the CURRENT EDITING CONTEXT (which post type is open),
 * not off what other block the block sits inside — a page and a template
 * part are both just root-level content, no shared ancestor block exists to
 * restrict against.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $block_editor_context) {
	$header_footer_blocks = [
		'acf/header',
		'acf/header-topbar',
		'acf/footer',
		'acf/footer-bottom',
		'acf/footer-cta',
		'acf/footer-menu',
		'acf/footer-contact',
	];

	$editing_post_type = $block_editor_context->post->post_type ?? null;
	if (in_array($editing_post_type, ['wp_template', 'wp_template_part'], true)) {
		return $allowed_block_types;
	}

	// `true` means "every registered block is allowed" — there's no list yet
	// to remove ours from, so build the real one first.
	if ($allowed_block_types === true) {
		$allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
	}

	return array_values(array_diff($allowed_block_types, $header_footer_blocks));
}, 10, 2);
