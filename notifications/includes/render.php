<?php

/**
 * Site-wide stacked notification cards, anchored to a screen corner (default
 * bottom-right — "Positie" op de options page kiest de andere 3). Enige
 * consument
 * van de "Melding(en)" options page (acf-json/ui_options_page_6a7c7f38b048a.json
 * + group_6a7db031097d5.json, menu_slug "notifications") — geen block en geen
 * markup die 2+ consumenten deelt, dus hier in notifications/ i.p.v. blocks/
 * of template-parts/ (root CLAUDE.md's plaatsingsregel), zelfde soort module
 * als live-search/.
 *
 * Dismiss-status leeft puur in sessionStorage (src/js/scripts/
 * notifications.js), geen cookie en geen server-side state: een bezoeker die
 * een melding wegklikt ziet 'm niet terug tot de volgende browsersessie
 * (nieuwe tab/venster nadat de browser echt gesloten is) — sessionStorage
 * wist zichzelf dan al, dus de melding komt vanzelf terug zonder dat er iets
 * bijgehouden hoeft te worden.
 *
 * "colors" is een group-veld (geen tab) en geldt voor ALLE kaarten tegelijk,
 * niet per rij — dus 1x als inline custom properties op de buitenste
 * wrapper (own_color_palette met return_format "value" geeft al een
 * resolved hex/"transparent" terug, zelfde conventie als
 * blocks/header/parts/search-modal.php's eigen $panel_style).
 *
 * Elke rij krijgt een stabiel id (md5 van titel+tekst): het repeater-veld
 * heeft zelf geen unieke-ID-subveld. Hashen op titel+tekst i.p.v. de
 * rij-index betekent dat een editor rijen kan herordenen zonder dat een
 * reeds-weggeklikte melding daardoor per ongeluk weer verschijnt — een
 * index-gebaseerd id zou dat wel doen. Het HERSCHRIJVEN van de tekst van een
 * reeds-weggeklikte melding laat 'm bewust weer verschijnen: dat is dan
 * inhoudelijk een nieuwe melding.
 */

defined('ABSPATH') || exit();

add_action('wp_footer', function (): void {
	$notifications = array_values(array_filter(
		get_field('notifications', 'option') ?: [],
		fn ($row) => empty($row['hide_notification'])
	));

	if (empty($notifications)) {
		return;
	}

	$colors = get_field('colors', 'option') ?: [];

	$stack_style = '';
	foreach (
		[
			'notifications_bg_color' => '--own-notification-bg',
			'notifications_icon_color' => '--own-notification-icon-color',
			'notifications_title_color' => '--own-notification-title-color',
		] as $field_name => $css_var
	) {
		$color_value = $colors[$field_name] ?? '';
		if (!empty($color_value)) {
			$stack_style .= sprintf('%s:%s;', $css_var, esc_attr($color_value));
		}
	}

	// "Positie" (group_6a7db031097d5.json's eigen "position"-veld) kiest de
	// hoek. flex-col-reverse (onder) / flex-col (boven) bepaalt of de EERSTE
	// rij uit de repeater het dichtst bij de hoek zelf staat, met latere rijen
	// die er steeds verder van AF stapelen — nooit richting het midden van het
	// scherm, ongeacht welke hoek gekozen is.
	$position = get_field('position', 'option') ?: 'bottom-right';
	$position_classes = match ($position) {
		'bottom-left' => 'bottom-4 sm:left-4 flex-col-reverse items-start',
		'top-right' => 'top-4 sm:right-4 flex-col items-end',
		'top-left' => 'top-4 sm:left-4 flex-col items-start',
		default => 'bottom-4 sm:right-4 flex-col-reverse items-end', // bottom-right
	};
	?>
	<!-- pointer-events-none op de wrapper: inset-x-4 laat 'm op mobiel bijna de
	     volle breedte beslaan, en zonder dit zou de lege ruimte naast de
	     uitgelijnde kaarten klikken op onderliggende pagina-inhoud blokkeren.
	     Elke kaart krijgt zelf weer pointer-events-auto terug. -->
	<div
		class="own-notifications pointer-events-none fixed inset-x-4 z-40 flex gap-3 sm:inset-x-auto <?= esc_attr($position_classes); ?>"
		style="<?= $stack_style; ?>"
		role="region"
		aria-label="<?= esc_attr__('Mededelingen', 'own-framework'); ?>">
		<?php foreach ($notifications as $row) :
			$id = substr(md5(($row['title'] ?? '') . '|' . ($row['text'] ?? '')), 0, 12);

			$title_raw = (string) ($row['title'] ?? '');
			$title_is_empty = trim(wp_strip_all_tags($title_raw)) === '';
			// new_lines "br" geeft al <br>-tags terug, geen platte tekst meer —
			// wp_kses staat alleen <br> toe zodat de regeleinden blijven zonder
			// dat de rest van de titel ongefilterd meegaat.
			$title_html = wp_kses($title_raw, ['br' => []]);

			$text = (string) ($row['text'] ?? '');
			$text_is_empty = trim(wp_strip_all_tags($text)) === '';

			$show_icon = !empty($row['show_icon']);

			$link = $row['button']['link'] ?? null;
			$button_version = $row['button']['button_version'] ?? 'filled';
			// De button_group-keuzes ("text-link" etc., group_6a7db031097d5.json)
			// zijn Nederlandse editor-labels, geen 1-op-1 kopie van de
			// is-style-*-classnamen die inc/core-blocks/button-styles.php
			// registreert ("link", niet "text-link") — vandaar deze mapping in
			// plaats van de waarde direct als class te gebruiken. "filled" is
			// core/button's eigen default look en heeft geen is-style-class nodig.
			$button_class = match ($button_version) {
				'outline' => 'is-style-outline',
				'text-link' => 'is-style-link',
				'secondary' => 'is-style-secondary',
				'secondary-outline' => 'is-style-secondary-outline',
				default => '',
			};
		?>
			<div
				x-data="ownNotificationCard('<?= esc_js($id); ?>')"
				x-show="visible"
				x-cloak
				x-transition:enter="transition ease-out duration-300"
				x-transition:enter-start="opacity-0 translate-y-4"
				x-transition:enter-end="opacity-100 translate-y-0"
				x-transition:leave="transition ease-in duration-200"
				x-transition:leave-start="opacity-100"
				x-transition:leave-end="opacity-0"
				class="own-notification-card pointer-events-auto w-full max-w-sm rounded-2xl p-4 shadow-xl sm:w-96"
				role="status">

				<div class="flex items-start gap-3">
					<?php if ($show_icon) : ?>
						<span class="own-notification-card__icon shrink-0" aria-hidden="true">
							<?= ownframework_get_icon('bell', 'w-5 h-5'); ?>
						</span>
					<?php endif; ?>

					<div class="min-w-0 flex-1">
						<?php if (!$title_is_empty) : ?>
							<strong class="own-notification-card__title block font-semibold"><?= $title_html; ?></strong>
						<?php endif; ?>

						<?php if (!$text_is_empty) : ?>
							<div class="own-notification-card__text mt-1 text-sm"><?= $text; ?></div>
						<?php endif; ?>

						<?php if (!empty($link['url'])) : ?>
							<div class="wp-block-button <?= esc_attr($button_class); ?> mt-3">
								<a
									href="<?= esc_url($link['url']); ?>"
									class="wp-block-button__link wp-element-button"
									<?php if (($link['target'] ?? '') === '_blank') : ?>
									target="_blank" rel="noopener"
									<?php endif; ?>>
									<?= esc_html($link['title'] ?: __('Meer informatie', 'own-framework')); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>

					<!-- -m-1.5 cancelt de eigen p-1.5 weer op: de knop krijgt zo een
					     comfortabele hit-target zonder dat 'ie de kaart z'n
					     buitenste p-4 met extra ruimte laat afwijken tussen boven/
					     onder/links/rechts. -->
					<button
						type="button"
						@click="dismiss()"
						class="own-notification-card__close -m-1.5 inline-flex shrink-0 items-center justify-center rounded-lg p-1.5"
						aria-label="<?= esc_attr__('Melding sluiten', 'own-framework'); ?>">
						<?= ownframework_get_icon('close', 'w-5 h-5'); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
});
