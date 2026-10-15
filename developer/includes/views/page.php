<?php
/**
 * @var array $sites               ownframework_developer_sites()
 * @var array $post_types          OWNFRAMEWORK_DEV_POST_TYPES
 * @var bool  $switcher_non_public
 * @var bool  $updated
 */
defined('ABSPATH') || exit();

$is_multisite = is_multisite();
?>
<div class="wrap ofd">
	<header class="ofd-header">
		<h1 class="ofd-header__title">
			Ontwikkelaar
			<span class="ofd-pill">Alleen voor het ontwikkelteam</span>
		</h1>
		<p class="ofd-header__lead">
			<?php if ($is_multisite) : ?>
				Instellingen die klantbeheerders niet zien. Contenttypes stel je hier per site in.
			<?php else : ?>
				Instellingen die klantbeheerders niet zien.
			<?php endif; ?>
		</p>
	</header>

	<?php if ($updated) : ?>
		<div class="notice notice-success is-dismissible"><p>Instellingen opgeslagen.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="own_developer_save">
		<?php wp_nonce_field('own_developer_save'); ?>

		<?php if ($is_multisite) : ?>
			<section class="ofd-card">
				<div class="ofd-card__head">
					<h2 class="ofd-card__title">Site-wisselaar</h2>
					<p class="ofd-card__lead">De wisselaar in de footer-topbalk (acf/footer-topbar) toont normaal alleen sites die in Netwerkbeheer → Sites als "Public" staan.</p>
				</div>
				<label class="ofd-row">
					<span class="ofd-switch">
						<input type="checkbox" name="switcher_non_public" value="1" <?php checked($switcher_non_public); ?>>
						<span class="ofd-switch__track"></span>
					</span>
					<span>
						<strong>Toon ook niet-publieke sites</strong>
						<span class="ofd-hint">Geldt voor alle sites in het netwerk. Zet dit uit zodra de sites live gaan.</span>
					</span>
				</label>
			</section>
		<?php endif; ?>

		<section class="ofd-card">
			<div class="ofd-card__head">
				<h2 class="ofd-card__title">Contenttypes<?php echo $is_multisite ? ' per site' : ''; ?></h2>
				<p class="ofd-card__lead">Zet een contenttype uit als een site het niet gebruikt.</p>
			</div>

			<div class="ofd-callout">
				<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
				<div>
					<strong>Wat uitzetten doet</strong>
					<p>Het type verdwijnt uit het beheer en niemand kan de items nog bewerken, ook beheerders niet. Detail- en archiefpagina's geven een 404, de bijbehorende blocks verdwijnen uit de inserter en tonen niets meer op pagina's. Bestaande items blijven bewaard: weer aanzetten zet alles terug.</p>
				</div>
			</div>

			<div class="ofd-table-wrap">
				<table class="ofd-table">
					<thead>
						<tr>
							<th scope="col">Site</th>
							<?php foreach ($post_types as $config) : ?>
								<th scope="col"><?php echo esc_html($config['label']); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($sites as $site) : ?>
							<tr>
								<th scope="row" class="ofd-site">
									<input type="hidden" name="sites[]" value="<?php echo (int) $site['id']; ?>">
									<a class="ofd-site__name" href="<?php echo esc_url($site['admin_url']); ?>"><?php echo esc_html($site['name'] ?: $site['url']); ?></a>
									<span class="ofd-site__url"><?php echo esc_html(untrailingslashit(preg_replace('#^https?://#', '', $site['url']))); ?></span>
									<span class="ofd-site__badges">
										<?php if ($is_multisite && $site['is_main']) : ?>
											<span class="ofd-badge">Hoofdsite</span>
										<?php endif; ?>
										<?php if ($is_multisite && !$site['is_public']) : ?>
											<span class="ofd-badge ofd-badge--warning">Niet publiek</span>
										<?php endif; ?>
										<?php if (!$site['own_theme']) : ?>
											<span class="ofd-badge ofd-badge--muted">Ander thema</span>
										<?php endif; ?>
									</span>
								</th>
								<?php foreach ($site['post_types'] as $post_type => $state) : ?>
									<td class="ofd-cell<?php echo $state['count'] ? ' has-items' : ''; ?>">
										<label class="ofd-cell__toggle">
											<span class="ofd-switch">
												<input
													type="checkbox"
													name="post_types[<?php echo (int) $site['id']; ?>][<?php echo esc_attr($post_type); ?>]"
													value="1"
													aria-label="<?php echo esc_attr($post_types[$post_type]['label'] . ' op ' . $site['name']); ?>"
													<?php checked($state['enabled']); ?>
												>
												<span class="ofd-switch__track"></span>
											</span>
											<span class="ofd-cell__count">
												<?php echo $state['count'] ? esc_html(sprintf(_n('%d item', '%d items', $state['count'], 'own-framework'), $state['count'])) : 'Leeg'; ?>
											</span>
										</label>
										<?php if ($state['count']) : ?>
											<span class="ofd-cell__warning">Wordt onzichtbaar</span>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<section class="ofd-card">
			<div class="ofd-card__head">
				<h2 class="ofd-card__title">Koppelingen</h2>
				<p class="ofd-card__lead">
					<?php if ($is_multisite) : ?>
						Google Maps API-sleutel voor het adresveld (Algemeen → Contact) en het blok "Google Maps kaart". Een eigen sleutel per site gaat voor de netwerksleutel.
					<?php else : ?>
						Google Maps API-sleutel voor het adresveld (Algemeen → Contact) en het blok "Google Maps kaart".
					<?php endif; ?>
				</p>
			</div>

			<details class="ofd-details">
				<summary>Welke API's moeten aan in Google Cloud?</summary>
				<p>Zet in de <a href="https://console.cloud.google.com/apis/library?filter=category:maps" target="_blank" rel="noopener">Google Cloud Console</a> deze drie API's <strong>elk apart</strong> aan. De ene aanzetten geldt niet voor de andere:</p>
				<ul>
					<li><strong>Maps JavaScript API</strong>: het blok "Google Maps kaart" en het kaartje in het adresveld.</li>
					<li><strong>Geocoding API</strong>: een adres omzetten naar coördinaten en terug.</li>
					<li><strong>Places API</strong>: het zoekvak in het adresveld. Kies de <strong>klassieke</strong> "Places API", niet "Places API (New)". Met alleen de nieuwe blijft het zoekvak leeg.</li>
				</ul>
				<p>Zie je "Google Maps Platform rejected your request", dan staat de bijbehorende API nog uit. Na het aanzetten kan het een paar minuten duren. Beperk de sleutel tot de domeinen van de sites: hij staat in de broncode van elke pagina.</p>
			</details>

			<?php if ($is_multisite) : ?>
				<label class="ofd-field">
					<span class="ofd-field__label">Netwerksleutel</span>
					<input type="text" class="ofd-input" name="maps_network_key" value="<?php echo esc_attr($maps_network_key); ?>" placeholder="AIza…" autocomplete="off" spellcheck="false">
					<span class="ofd-hint">Voor alle sites zonder eigen sleutel.</span>
				</label>
			<?php endif; ?>

			<div class="ofd-table-wrap">
				<table class="ofd-table ofd-table--keys">
					<?php if ($is_multisite) : ?>
						<thead>
							<tr>
								<th scope="col">Site</th>
								<th scope="col">Eigen sleutel</th>
								<th scope="col">Gebruikt</th>
							</tr>
						</thead>
					<?php endif; ?>
					<tbody>
						<?php foreach ($sites as $site) :
							if ($site['maps_key'] !== '') {
								$source = ['Eigen sleutel', ''];
							} elseif ($maps_network_key !== '') {
								$source = ['Netwerksleutel', ''];
							} elseif ($maps_constant_key) {
								$source = ['wp-config.php', 'ofd-badge--muted'];
							} else {
								$source = ['Geen sleutel', 'ofd-badge--warning'];
							}
							?>
							<tr>
								<?php if ($is_multisite) : ?>
									<th scope="row" class="ofd-site">
										<span class="ofd-site__name"><?php echo esc_html($site['name'] ?: $site['url']); ?></span>
										<span class="ofd-site__url"><?php echo esc_html(untrailingslashit(preg_replace('#^https?://#', '', $site['url']))); ?></span>
									</th>
								<?php endif; ?>
								<td>
									<input
										type="text"
										class="ofd-input"
										name="maps_key[<?php echo (int) $site['id']; ?>]"
										value="<?php echo esc_attr($site['maps_key']); ?>"
										placeholder="<?php echo $is_multisite ? 'Netwerksleutel gebruiken' : 'AIza…'; ?>"
										aria-label="<?php echo esc_attr('Google Maps API-sleutel van ' . $site['name']); ?>"
										autocomplete="off"
										spellcheck="false"
									>
								</td>
								<td><span class="ofd-badge <?php echo esc_attr($source[1]); ?>"><?php echo esc_html($source[0]); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>

		<div class="ofd-savebar">
			<?php submit_button('Wijzigingen opslaan', 'primary', 'submit', false); ?>
		</div>
	</form>
</div>
