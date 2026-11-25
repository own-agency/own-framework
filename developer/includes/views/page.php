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

		<section class="ofd-card" id="instagram">
			<div class="ofd-card__head">
				<h2 class="ofd-card__title">Instagram</h2>
				<p class="ofd-card__lead">Voor het blok "Instagram-feed". Eén Meta-app van OWN voor alle sites; per site koppelt de klant het eigen Instagram-account. De site ververst elk uur zelf en bewaart de beelden lokaal.</p>
			</div>

			<?php
			$instagram_messages = [
				'gekoppeld' => ['success', 'Instagram is gekoppeld en de eerste posts zijn opgehaald.'],
				'ververst' => ['success', 'De posts zijn ververst.'],
				'ontkoppeld' => ['success', 'Instagram is ontkoppeld. De posts en beelden van deze site zijn verwijderd.'],
				'link' => ['success', 'De koppellink staat klaar. Stuur hem naar de beheerder van het Instagram-account.'],
				'fout' => ['error', $instagram_notice !== '' ? $instagram_notice : 'Er ging iets mis bij Instagram.'],
			];
			if (isset($instagram_messages[$instagram_result])) :
				[$notice_type, $notice_text] = $instagram_messages[$instagram_result];
				?>
				<div class="notice notice-<?php echo esc_attr($notice_type); ?> inline"><p><?php echo esc_html($notice_text); ?></p></div>
			<?php endif; ?>

			<details class="ofd-details">
				<summary>De Meta-app instellen (eenmalig)</summary>
				<ul>
					<li>Maak op <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com</a> een app van het type <strong>Business</strong> en voeg het product <strong>Instagram</strong> toe, met "API setup with Instagram login".</li>
					<li>Neem het <strong>Instagram app ID</strong> en de <strong>Instagram app secret</strong> over in de velden hieronder. Let op: dat zijn andere waarden dan het gewone app-ID van de Meta-app.</li>
					<li>Zet de redirect-URI hieronder bij <strong>Business login settings → OAuth redirect URIs</strong>, letterlijk zoals hij hier staat. Met een relay staat daar alleen de relay, één keer voor alle installaties.</li>
					<li>De app moet <strong>Live</strong> staan met advanced access voor <strong>instagram_business_basic</strong>. Staat hij in ontwikkelmodus, dan werkt alleen een account met de rol Instagram Tester. Alleen zakelijke en creator-accounts werken.</li>
					<li>Meta vraagt één keer per jaar om een "Data Use Checkup" van de app. Rond die af, anders wordt de app beperkt en stoppen alle feeds tegelijk.</li>
				</ul>
			</details>

			<label class="ofd-field">
				<span class="ofd-field__label">Instagram app ID</span>
				<input type="text" class="ofd-input" name="instagram_app_id" value="<?php echo esc_attr((string) ($instagram_app_stored['id'] ?? '')); ?>" placeholder="<?php echo defined('OWN_INSTAGRAM_APP_ID') ? 'Uit wp-config.php' : '1234567890'; ?>" autocomplete="off" spellcheck="false" inputmode="numeric">
			</label>
			<div class="ofd-field">
				<label class="ofd-field__label" for="ofd-instagram-secret">Instagram app secret</label>
				<input type="password" id="ofd-instagram-secret" class="ofd-input" name="instagram_app_secret" value="" placeholder="<?php echo !empty($instagram_app_stored['secret']) ? 'Opgeslagen. Laat leeg om te behouden.' : (defined('OWN_INSTAGRAM_APP_SECRET') ? 'Uit wp-config.php' : ''); ?>" autocomplete="new-password" spellcheck="false">
				<?php if (!empty($instagram_app_stored['secret'])) : ?>
					<span class="ofd-hint">
						<span class="ofd-badge">Opgeslagen</span>
						eindigt op <code>…<?php echo esc_html(substr((string) $instagram_app_stored['secret'], -4)); ?></code>. Het veld blijft leeg zodat het secret niet in de pagina staat; vul het alleen in om het te vervangen.
					</span>
					<label class="ofd-hint"><input type="checkbox" name="instagram_app_secret_clear" value="1"> Opgeslagen secret wissen</label>
				<?php elseif (defined('OWN_INSTAGRAM_APP_SECRET')) : ?>
					<span class="ofd-hint">Komt uit wp-config.php (OWN_INSTAGRAM_APP_SECRET).</span>
				<?php else : ?>
					<span class="ofd-hint"><span class="ofd-badge ofd-badge--warning">Nog niet ingevuld</span></span>
				<?php endif; ?>
			</div>
			<label class="ofd-field">
				<span class="ofd-field__label">Relay-URL</span>
				<input type="url" class="ofd-input" name="instagram_relay_url" value="<?php echo esc_attr($instagram_relay_stored); ?>" placeholder="<?php echo defined('OWN_INSTAGRAM_RELAY_URL') ? 'Uit wp-config.php' : 'https://own-instagram-relay.….workers.dev/'; ?>" autocomplete="off" spellcheck="false">
				<span class="ofd-hint">De Cloudflare Worker van OWN (developer/instagram-relay/ in dit theme). Met een relay hoeft dit netwerk niet in de Meta-app. Leeg = de eigen callback hieronder gebruiken.</span>
			</label>
			<label class="ofd-field">
				<span class="ofd-field__label">OAuth redirect URI</span>
				<input type="text" class="ofd-input" value="<?php echo esc_attr($instagram_redirect_uri); ?>" readonly onclick="this.select()">
				<span class="ofd-hint">
					<?php if ($instagram_relay_active) : ?>
						De relay. Staat die al in de Meta-app, dan hoeft er voor dit netwerk niets meer bij.
					<?php else : ?>
						Moet letterlijk in de Meta-app staan.
					<?php endif; ?>
				</span>
			</label>

			<div class="ofd-table-wrap">
				<table class="ofd-table ofd-table--instagram">
					<thead>
						<tr>
							<?php if ($is_multisite) : ?>
								<th scope="col">Site</th>
							<?php endif; ?>
							<th scope="col">Account</th>
							<th scope="col">Acties</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($sites as $site) :
							$ig = $site['instagram'];
							if (!$ig['connected'] && !empty($ig['removed']['at'])) {
								$ig_badge = ['Automatisch ontkoppeld', 'ofd-badge--muted'];
							} elseif (!$ig['connected']) {
								$ig_badge = ['Niet gekoppeld', 'ofd-badge--muted'];
							} elseif ($ig['needs_reconnect']) {
								$ig_badge = ['Opnieuw koppelen', 'ofd-badge--warning'];
							} elseif ($ig['error'] !== '') {
								$ig_badge = ['Laatste sync mislukt', 'ofd-badge--warning'];
							} else {
								$ig_badge = ['Gekoppeld', ''];
							}
							?>
							<tr<?php echo $instagram_result_site === (int) $site['id'] ? ' class="is-highlighted"' : ''; ?>>
								<?php if ($is_multisite) : ?>
									<th scope="row" class="ofd-site">
										<span class="ofd-site__name"><?php echo esc_html($site['name'] ?: $site['url']); ?></span>
										<span class="ofd-site__url"><?php echo esc_html(untrailingslashit(preg_replace('#^https?://#', '', $site['url']))); ?></span>
									</th>
								<?php endif; ?>
								<td>
									<span class="ofd-badge <?php echo esc_attr($ig_badge[1]); ?>"><?php echo esc_html($ig_badge[0]); ?></span>
									<?php if ($ig['connected']) : ?>
										<strong class="ofd-ig-account">@<?php echo esc_html($ig['username']); ?></strong>
										<span class="ofd-hint">
											<?php
											echo esc_html(sprintf(
												'%d posts%s. Token geldig tot %s.',
												$ig['post_count'],
												$ig['synced_at'] ? ', ververst ' . human_time_diff($ig['synced_at']) . ' geleden' : '',
												$ig['expires'] ? wp_date('j F Y', $ig['expires']) : '?'
											));
											?>
										</span>
										<?php if ($ig['error'] !== '') : ?>
											<span class="ofd-hint ofd-ig-error"><?php echo esc_html($ig['error']); ?></span>
										<?php endif; ?>
										<?php if ($ig['needs_reconnect'] && $ig['remove_at']) : ?>
											<span class="ofd-hint ofd-ig-error"><?php echo esc_html(sprintf('Zonder nieuwe koppeling worden de posts en afbeeldingen op %s verwijderd.', wp_date('j F Y', $ig['remove_at']))); ?></span>
										<?php endif; ?>
									<?php elseif (!empty($ig['removed']['at'])) : ?>
										<span class="ofd-hint"><?php echo esc_html(sprintf('@%s was 30 dagen niet bereikbaar. Posts en afbeeldingen zijn op %s verwijderd.', $ig['removed']['username'] ?? '?', wp_date('j F Y', (int) $ig['removed']['at']))); ?></span>
									<?php endif; ?>
									<?php if ($ig['invite'] && (!$ig['connected'] || $ig['needs_reconnect'])) : ?>
										<span class="ofd-ig-invite">
											<input type="text" class="ofd-input" value="<?php echo esc_attr($ig['invite']['url']); ?>" readonly onclick="this.select()" aria-label="<?php echo esc_attr('Koppellink voor ' . $site['name']); ?>">
											<span class="ofd-hint">Koppellink, eenmalig, geldig tot <?php echo esc_html(wp_date('j F Y', $ig['invite']['expires'])); ?>. Stuur hem naar de beheerder van het Instagram-account.</span>
										</span>
									<?php endif; ?>
								</td>
								<td class="ofd-ig-actions">
									<?php if (!$ig['connected'] || $ig['needs_reconnect']) : ?>
										<a class="button button-small" href="<?php echo esc_url(ownframework_instagram_action_url('invite', (int) $site['id'])); ?>"><?php echo $ig['invite'] ? 'Nieuwe koppellink' : 'Koppellink maken'; ?></a>
										<a class="button button-small" href="<?php echo esc_url(ownframework_instagram_action_url('connect', (int) $site['id'])); ?>">Zelf koppelen</a>
									<?php endif; ?>
									<?php if ($ig['connected']) : ?>
										<?php if (!$ig['needs_reconnect']) : ?>
											<a class="button button-small" href="<?php echo esc_url(ownframework_instagram_action_url('sync', (int) $site['id'])); ?>">Nu verversen</a>
										<?php endif; ?>
										<a class="button button-small button-link-delete" href="<?php echo esc_url(ownframework_instagram_action_url('disconnect', (int) $site['id'])); ?>" onclick="return confirm('Instagram ontkoppelen? De feed verdwijnt van de site.');">Ontkoppelen</a>
									<?php endif; ?>
								</td>
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
