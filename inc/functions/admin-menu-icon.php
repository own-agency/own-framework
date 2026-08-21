<?php
defined('ABSPATH') || exit();

/**
 * "Thema opties" (acf-json/ui_options_page_69bbbe5664ea4.json) heeft een
 * eigen media-library-afbeelding als menu-icoon (menu_icon.type ===
 * "media_library"). SCF resolveert dat normaal naar een gewone
 * wp_get_attachment_image_url()-URL, en WP core rendert zo'n icon_url dan
 * als een letterlijke <img> (wp-admin/menu-header.php) — die <img> wordt
 * altijd naar opacity .6 gedimd tot 'ie gehoverd/actief is (wp-admin/css/
 * admin-menu.css's eigen #adminmenu .wp-menu-image img-regel).
 *
 * Een losse CSS-override hiertegen bleek in de praktijk niet te werken
 * (zelfs met !important en de juiste stylesheet-dependency-volgorde) —
 * onduidelijk waarom, maar in plaats van nóg een keer tegen diezelfde regel
 * te vechten, ontwijkt dit 'm helemaal: base64-encoded data:image/svg+xml
 * URI's krijgen van WP core een COMPLEET ANDERE renderpad (dezelfde functie,
 * menu-header.php, regel ~135) — geen <img> meer, maar een <br/> plus het
 * icoon als CSS background-image op div.wp-menu-image zelf, met een eigen
 * ".svg" class. Die class heeft geen opacity-regel in admin-menu.css (alleen
 * .wp-menu-image img en het dashicons-lettertype-pad via :before hebben
 * dat) — dus dit icoon wordt nooit gedimd, zonder dat er ergens een !important
 * of specificity-gok voor nodig is.
 *
 * acf/ui_options_page/registration_args is SCF's eigen filter, precies de
 * laatste stap vóór acf_add_options_page()/acf_add_options_sub_page()
 * (secure-custom-fields/includes/post-types/class-acf-ui-options-page.php's
 * get_options_page_args()) — dus dit haakt in NA dat SCF de media library-
 * URL al heeft opgezocht, en vervangt die met de daadwerkelijke bestands-
 * inhoud, base64-encoded. Alleen voor "theme-options": update de menu_slug-
 * check hieronder als die pagina ooit weer hernoemd wordt via de options-
 * page's eigen admin-UI.
 */
add_filter('acf/ui_options_page/registration_args', function ($args) {
	if (($args['menu_slug'] ?? '') !== 'theme-options') {
		return $args;
	}

	if (($args['menu_icon']['type'] ?? '') !== 'media_library') {
		return $args;
	}

	$attachment_id = $args['menu_icon']['value'] ?? 0;
	if (empty($attachment_id) || get_post_mime_type($attachment_id) !== 'image/svg+xml') {
		return $args;
	}

	$file_path = get_attached_file($attachment_id);
	if (!$file_path || !file_exists($file_path)) {
		return $args;
	}

	$svg_contents = file_get_contents($file_path);
	if ($svg_contents === false) {
		return $args;
	}

	$args['icon_url'] = 'data:image/svg+xml;base64,' . base64_encode($svg_contents);

	return $args;
});

/**
 * WP core dims every admin menu <img> icon to opacity .6 until hover/active
 * (wp-admin/css/admin-menu.css's #adminmenu .wp-menu-image img rule) and only
 * gives it 9px of top padding — both tuned for a monochrome dashicon-style
 * glyph that's meant to fade in on hover, not for a full-colour icon, which
 * it instead washes out and pushes off-centre.
 *
 * The <li> id is NOT #toplevel_page_theme-options, even though that's this
 * options page's own "menu_slug" (acf-json/ui_options_page_69bbbe5664ea4.
 * json). "Thema opties" has "redirect": 1, and SCF's calculate_options_pages
 * redirect logic (class-acf-options-page.php) silently overwrites the
 * PARENT's own $page['menu_slug'] with its first CHILD's menu_slug ("general",
 * the "Algemeen" sub-page) before ever calling add_menu_page() — the original
 * is kept only as $page['_menu_slug'], never passed to WP core. So the
 * top-level <li> WP core actually renders is #toplevel_page_general (WP's own
 * get_plugin_page_hookname() naming, keyed off THAT slug). Update this
 * selector if "Algemeen" is ever renamed/re-slugged, or if "Thema opties"
 * stops being the first "redirect: 1" page with "general" as its first child.
 */
add_action('admin_enqueue_scripts', function () {
	wp_add_inline_style('common', '
		#adminmenu #toplevel_page_general .wp-menu-image img {
			opacity: 1;
			padding: 9px;
		}
	');
});
