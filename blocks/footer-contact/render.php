<?php
defined('ABSPATH') || exit();

/**
 * Footer-contactgegevens — een eigen, genest block in plaats van een stuk
 * van acf/footer's eigen render.php: zelfde reden als acf/footer-cta's
 * eigen render.php-comment (InnerBlocks staat maar 1 gebied per SCF-block
 * toe). Contact/social media komen uit de "Algemeen" options page (acf-
 * json/group_own_general_settings.json) — geen footer-specifieke velden,
 * want dezelfde gegevens horen ook elders (bv. een Contact-block) herbruikt
 * te kunnen worden.
 *
 * iconsColor is de enige eigen kleurkeuze hier (geen native support, "icoon-
 * kleur" is geen native WP-kleurtype) — de algemene tekstkleur erft dit
 * block gewoon via --own-footer-text, de CSS-cascade van acf/footer's eigen
 * wrapper.
 */

$contact = get_field('contact', 'option') ?: [];
$social = get_field('social_media', 'option') ?: [];

$icons_style = '';
if (!empty($block['iconsColor'])) {
	$icons_style = sprintf('--own-footer-icons-color:%s;', esc_attr($block['iconsColor']));
}

// Alle 3 contactlinks + de social-icon-links hieronder krijgen een inline
// color style: elke <a> hier is gevoelig voor WP's Ontwerp "Elements ▸
// Links"-kleur (.wp-elements-<hash> a:where(...), hogere specificiteit dan
// een gewone class) — zonder dit volgden ze die kleur (of WP's default) in
// plaats van de algemene footertekstkleur (--own-footer-text). Zelfde fix
// als acf/footer-cta's eigen render.php.
$contact_link_style = 'color: var(--own-footer-text, inherit);';

// ownframework_get_contact_address() — inc/functions/contact-address.php,
// gedeeld met blocks/google-maps.
$address = ownframework_get_contact_address();
$address_html = implode('<br>', array_map('esc_html', $address['lines']));
$maps_url = $address['maps_url'];

// social_media.socials is een repeater — editors voegen zelf rijen toe/
// verwijderen/herordenen via "Social platform toevoegen". social_platform's
// keuzes matchen niet 1-op-1 met media/icons/ (x_twitter vs. social-x.svg),
// dus een kleine mapping naar het icoonbestand + een leesbaar label voor
// aria-label.
$social_platforms = [
	'facebook' => __('Facebook', 'own-framework'),
	'instagram' => __('Instagram', 'own-framework'),
	'linkedin' => __('LinkedIn', 'own-framework'),
	'x_twitter' => 'X',
	'threads' => __('Threads', 'own-framework'),
	'youtube' => __('YouTube', 'own-framework'),
];
$social_icon_slugs = ['x_twitter' => 'x'];

$social_links = array_values(array_filter(
	$social['socials'] ?: [],
	fn($row) => !empty($row['social_platform']) && !empty($row['url'])
));

$has_any = !empty($contact['phone']) || !empty($contact['email']) || $address_html !== '' || !empty($social_links);

if (!$has_any && empty($is_preview)) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes([
	'style' => $icons_style,
]);
?>
<div <?= $wrapper_attributes; ?>>
	<?php if (!$has_any) : ?>
		<p class="text-sm opacity-60"><?php esc_html_e('Geen contactgegevens ingesteld (Algemeen-instellingenpagina).', 'own-framework'); ?></p>
	<?php endif; ?>

	<?php if (!empty($contact['phone'])) : ?>
		<a
			href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', $contact['phone'])); ?>"
			class="own-footer-link w-fit flex items-center gap-3 text-sm hover:underline"
			style="<?= esc_attr($contact_link_style); ?>">
			<?= ownframework_get_icon('phone', 'own-footer-icon w-4 h-4 shrink-0'); ?>
			<?= esc_html($contact['phone']); ?>
		</a>
	<?php endif; ?>

	<?php if (!empty($contact['email'])) : ?>
		<a
			href="mailto:<?= esc_attr($contact['email']); ?>"
			class="own-footer-link w-fit mt-2 flex items-center gap-3 text-sm hover:underline"
			style="<?= esc_attr($contact_link_style); ?>">
			<?= ownframework_get_icon('mail', 'own-footer-icon w-4 h-4 shrink-0'); ?>
			<?= esc_html($contact['email']); ?>
		</a>
	<?php endif; ?>

	<?php if ($address_html !== '') : ?>
		<a
			href="<?= esc_url($maps_url); ?>"
			target="_blank"
			rel="noopener"
			class="own-footer-link w-fit mt-5 flex items-start gap-3 text-sm hover:underline"
			style="<?= esc_attr($contact_link_style); ?>">
			<?= ownframework_get_icon('map-pin', 'own-footer-icon w-4 h-4 shrink-0'); ?>
			<?php // $address_html is hierboven al per regel door esc_html() gehaald en
			// daarna met <br> aan elkaar gezet — vandaar geen escaping meer hier.
			?>
			<span><?= $address_html; ?></span>
		</a>
	<?php endif; ?>

	<?php if (!empty($social_links)) : ?>
		<div class="mt-5 flex flex-wrap items-center gap-2">
			<?php foreach ($social_links as $row) :
				$platform = $row['social_platform'];
				$icon_slug = $social_icon_slugs[$platform] ?? $platform;
				$label = $social_platforms[$platform] ?? ucfirst(str_replace('_', ' ', $platform));
			?>
				<a
					href="<?= esc_url($row['url']); ?>"
					target="_blank"
					rel="noopener"
					class="own-footer-icon-link"
					aria-label="<?= esc_attr($label); ?>"
					style="color: var(--own-footer-icons-color, inherit);">
					<?= ownframework_get_icon('social-' . $icon_slug, 'own-footer-icon w-5 h-5'); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
