<?php
defined('ABSPATH') || exit();

/**
 * Zoekmodal voor blocks/header/render.php. Enige consument, dus hier en niet
 * in template-parts/. Live search: elke toetsaanslag (na live-search/load.php's
 * min_chars tekens, gedebounced) vraagt live-search/includes/api.php's eigen
 * REST-route (own-live-search/v1/query) aan en toont titel/excerpt/thumbnail/
 * type inline, zonder page reload. <form method="get" action="/">
 * blijft intact als fallback als JS/Alpine ooit niet laadt.
 *
 * Globale $store.searchDialog/$store.liveSearch (src/js/scripts/
 * live-search.js), NIET lokale x-data op <header> zoals own-mobile-menu's
 * mobileMenuOpen: ⌘K/Ctrl+K (hieronder) moet de modal vanaf ELKE plek op de
 * pagina kunnen openen, niet alleen vanuit een klik binnen header's eigen
 * Alpine-scope — een globale store is overal bereikbaar, header's eigen
 * x-data niet.
 *
 * esc_attr() op elke Alpine-expressie die zelf een ">" bevat (vergelijkingen,
 * niet de simpele "$store.x.y()" calls): zelfde reden als render.php's eigen
 * $scroll_expr — dit block's output loopt door the_content()'s wpautop()/
 * wptexturize(), die een kale ">" binnen een attribuutwaarde aanziet voor een
 * tag-einde en daarna elke volgende straight quote corrumpeert tot een curly
 * quote.
 *
 * Titel/placeholder/suggestietekst/zoektermen/kleuren komen uit de "Live
 * search" options page (acf-json/ui_options_page_own_live_search_settings.json
 * + group_own_live_search_settings.json) — editorial content die een editor
 * zonder code-toegang moet kunnen aanpassen, in tegenstelling tot live-
 * search/load.php's eigen config-array (post_types, debounce, etc.): dát
 * zijn developer-keuzes zonder editor-relevante UI, dít is precies wat een
 * editor WEL ziet/kiest. Geen aparte "brand settings"-achtige structuur
 * (root CLAUDE.md's "Brand identity"-sectie) — die sectie gaat over kleuren/
 * fonts die al een native Global Styles-equivalent hebben; er is geen Global
 * Styles-equivalent voor "welke van deze 4 palet-kleuren is de zoekmodal z'n
 * achtergrond", en SCF-options-pages zijn al de gewone manier waarop dit
 * thema editor-input structureert (elk block's eigen velden). De 3 kleur-
 * velden gebruiken inc/scf/color-palette.php's own_color_palette-veldtype —
 * dezelfde swatch-picker die vanaf nu ook toekomstige blocks voor hun eigen
 * kleurvelden gebruiken, dus ze lezen ook hetzelfde, live Ontwerp-palet —
 * met return_format "value": die geeft al een resolved hex/"transparent"
 * terug (zie format_value() in dat bestand), in tegenstelling tot
 * render.php's eigen $header_bg_value die WEL zelf een
 * var(--wp--preset--color--<slug>) moet bouwen omdat WP's NATIVE colour
 * support alleen een kale slug teruggeeft.
 *
 * "contents"/"colors" zijn ACF group-velden (group_own_live_search_settings.
 * json), geen tabs — een tab is puur UI en laat get_field() ongewijzigd op
 * de kale veldnaam werken, maar een group NEST de teruggegeven waarde onder
 * de group's eigen naam. get_field('live_search_title', 'option') werkt
 * daarom niet meer; het moet via get_field('contents', 'option') en dan de
 * kale veldnaam als array-key op dat resultaat (idem voor "colors").
 */

$has_query_expr = esc_attr('$store.liveSearch.query.length > 0 && !$store.liveSearch.loading');
$has_results_expr = esc_attr('$store.liveSearch.searched && $store.liveSearch.results.length > 0');
$results_list_expr = esc_attr('$store.liveSearch.results.length > 0');

$content = get_field('contents', 'option') ?: [];
// This group field's own name is now "live_search_colors" (acf-json/
// group_own_live_search_settings.json), NOT the generic "colors" it used to
// share with Melding(en)'s and Formulieren's own Kleuren group. That sharing
// used to cross-contaminate all 3: SCF's runtime "values" store caches a
// loaded value under "{$post_id}:{$field['name']}" (acf_get_value(),
// includes/acf-value-functions.php) — a bare NAME, not the field key, and
// not scoped per field group — so on any request where more than one of the
// 3 loads (this file + notifications/includes/render.php's colours both run
// on every front-end page load), whichever loaded FIRST won the single
// shared cache slot for the rest of the request; the other 2 silently got
// its colours back instead of their own. Each page's own group field now
// has a page-unique name, which is the actual fix. get_field() is still
// called by field KEY (not name) below, as an extra guard against a milder,
// SEPARATE ambiguity (which field DEFINITION a bare name resolves to) —
// that alone would NOT have fixed the cache collision above, since the
// cache key is always the field's raw name regardless of which selector
// get_field() is called with.
$colors = get_field('field_6a746c59fc3d0', 'option') ?: [];

$title = trim((string) ($content['live_search_title'] ?? ''));
$placeholder = trim((string) ($content['live_search_placeholder'] ?? '')) ?: __('Waar ben je naar op zoek?', 'own-framework');
// 1 veld, 2 rollen: staat onder het zoekveld als een gewone beschrijving, EN
// dient als kopje boven de voorgestelde zoektermen als die er zijn — was
// eerst 2 losse velden (Beschrijving/Suggestietekst), maar die zeiden allebei
// hetzelfde tegen een editor. field name bleef live_search_suggestions_title
// (niet hernoemd naar live_search_description) om reeds ingevulde waarden op
// bestaande sites niet te laten verweesd raken.
$description = trim((string) ($content['live_search_suggestions_title'] ?? ''));
$terms = array_values(array_filter(
	$content['live_search_terms'] ?? [],
	fn($row) => !empty($row['term'])
));

// own_color_palette (inc/scf/color-palette.php) met return_format "value"
// geeft al een resolved CSS-kleur terug (hex, of het literal keyword
// "transparent") — geen var(--wp--preset--color--<slug>)-indirectie nodig
// zoals render.php's eigen $header_bg_value die WEL bouwt, want dat leest een
// kale slug van WP's NATIVE colour support terug. Alleen als custom property
// gezet wanneer een editor 'm daadwerkelijk kiest; header.css's eigen
// var(..., fallback)-rules vallen anders terug op het huidige, ongewijzigde
// uiterlijk. 4 losse tekstkleuren (title/query/description/badge), niet 1
// generieke "tekstkleur": de titel boven het zoekveld, wat je zelf intypt, de
// beschrijving eronder en de accent-badge/highlight zijn 4 losse plekken die
// een editor onafhankelijk van elkaar wil kunnen inkleuren.
// The page behind the open modal must not scroll. Its own class, not the
// mobile menu's overflow-clip toggle: the search button stays reachable
// while that menu is open, and closing search would otherwise also lift the
// menu's lock. The rule itself is in header.css.
$scroll_lock_expr = "document.documentElement.classList.toggle('own-search-scroll-lock', \$store.searchDialog.isOpen); document.body.classList.toggle('own-search-scroll-lock', \$store.searchDialog.isOpen)";

$panel_style = '';
foreach (
	[
		'live_search_bg_color' => '--own-search-bg',
		'live_search_title_color' => '--own-search-title-color',
		'live_search_query_color' => '--own-search-query-color',
		'live_search_description_color' => '--own-search-description-color',
		'live_search_badge_color' => '--own-search-badge-color',
	] as $field_name => $css_var
) {
	$color_value = $colors[$field_name] ?? '';
	if (!empty($color_value)) {
		$panel_style .= sprintf('%s:%s;', $css_var, esc_attr($color_value));
	}
}
?>
<div
	x-data
	x-effect="<?= esc_attr($scroll_lock_expr); ?>"
	@keydown.escape.window="$store.searchDialog.close()"
	@keydown.cmd.k.window.prevent="$store.searchDialog.toggle()"
	@keydown.ctrl.k.window.prevent="$store.searchDialog.toggle()">

	<div
		x-show="$store.searchDialog.isOpen"
		x-cloak
		class="own-search-modal fixed inset-0 z-50 flex items-start justify-center pt-16 px-4 sm:px-6 sm:pt-24 md:pt-32">

		<!-- Backdrop -->
		<div
			x-show="$store.searchDialog.isOpen"
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0"
			x-transition:enter-end="opacity-100"
			x-transition:leave="transition ease-in duration-150"
			x-transition:leave-start="opacity-100"
			x-transition:leave-end="opacity-0"
			@click="$store.searchDialog.close()"
			class="absolute inset-0 bg-black/50"
			aria-hidden="true"></div>

		<!-- Panel -->
		<div
			x-show="$store.searchDialog.isOpen"
			x-transition:enter="transition ease-out duration-200"
			x-transition:enter-start="opacity-0 -translate-y-4"
			x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition ease-in duration-150"
			x-transition:leave-start="opacity-100 translate-y-0"
			x-transition:leave-end="opacity-0 -translate-y-4"
			@click.outside="$store.searchDialog.close()"
			class="own-search-modal__panel relative flex w-full max-w-xl max-h-[calc(100vh-8rem)] flex-col overflow-hidden rounded-xl shadow-xl sm:max-h-[calc(100vh-12rem)] md:max-h-[calc(100vh-16rem)]"
			style="<?= $panel_style; ?>"
			role="dialog"
			aria-modal="true"
			<?php if ($title !== '') : ?>
			aria-labelledby="own-search-modal-title"
			<?php else : ?>
			aria-label="<?= esc_attr__('Zoeken', 'own-framework'); ?>"
			<?php endif; ?>>

			<!-- Titel (optioneel) + sluiten. Altijd een sluitknop hier, ook zonder
			     titel, want de zoekregel eronder heeft er zelf geen meer. -->
			<div class="flex items-center gap-4 border-b border-black/5 px-4 py-3 <?= $title !== '' ? 'justify-between' : 'justify-end'; ?>">
				<?php if ($title !== '') : ?>
					<h2 class="own-search-modal__title-heading text-(length:--own-h3-font-size)! leading-(--own-h3-line-height)! m-0! font-semibold" id="own-search-modal-title"><?= esc_html($title); ?></h2>
				<?php endif; ?>
				<button
					type="button"
					@click="$store.searchDialog.close()"
					class="own-header-icon-btn inline-flex items-center justify-center rounded-lg p-2 text-gray-400"
					aria-label="<?= esc_attr__('Sluiten', 'own-framework'); ?>">
					<?= ownframework_get_icon('close', 'w-5 h-5'); ?>
				</button>
			</div>

			<!-- Zoekregel -->
			<form
				role="search"
				method="get"
				action="<?= esc_url(home_url('/')); ?>"
				@submit.prevent="$store.liveSearch.search()"
				class="flex items-center gap-2 border-b border-black/5 p-2">
				<?= ownframework_get_icon('search', 'w-5 h-5 ms-3 shrink-0 text-gray-400'); ?>
				<label class="sr-only" for="own-header-search-input"><?= esc_html__('Zoeken', 'own-framework'); ?></label>
				<input
					id="own-header-search-input"
					type="search"
					name="s"
					x-ref="searchInput"
					x-effect="$store.searchDialog.isOpen && $nextTick(function () { $refs.searchInput.focus() })"
					:value="$store.liveSearch.query"
					@input="$store.liveSearch.query = $event.target.value; $store.liveSearch.search()"
					autocomplete="off"
					placeholder="<?= esc_attr($placeholder); ?>"
					class="own-search-modal__input flex-1 appearance-none border-0 py-3 text-base focus:outline-none focus:ring-0"
					aria-controls="own-search-modal-results"
					aria-autocomplete="list">

				<!-- Spinner tijdens het laden -->
				<span
					class="inline-block w-5 h-5 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent text-gray-300"
					x-show="$store.liveSearch.loading"
					x-cloak
					aria-hidden="true"></span>

				<!-- Zoekterm wissen, alleen zolang er iets te wissen is én niet tijdens het laden (anders overlapt met de spinner hierboven) -->
				<button
					type="button"
					x-show="<?= $has_query_expr; ?>"
					x-cloak
					@click="$store.liveSearch.clear(); $refs.searchInput.focus()"
					class="own-header-icon-btn inline-flex items-center justify-center rounded-lg p-2 me-1 text-gray-400"
					aria-label="<?= esc_attr__('Zoekterm wissen', 'own-framework'); ?>">
					<?= ownframework_get_icon('close', 'w-4 h-4'); ?>
				</button>
			</form>

			<?php if ($description !== '' || !empty($terms)) : ?>
				<!-- Beschrijving + voorgestelde zoektermen: allebei alleen
				     zolang er nog niet gezocht is. -->
				<div class="px-4 pt-3" x-show="!$store.liveSearch.searched">
					<?php if ($description !== '') : ?>
						<p class="own-search-modal__description text-sm" id="own-search-modal-description"><?= esc_html($description); ?></p>
					<?php endif; ?>

					<?php if (!empty($terms)) : ?>
						<div
							class="flex flex-wrap gap-2 <?= $description !== '' ? 'mt-2' : ''; ?>"
							role="group"
							<?php if ($description !== '') : ?>
							aria-labelledby="own-search-modal-description"
							<?php else : ?>
							aria-label="<?= esc_attr__('Suggesties', 'own-framework'); ?>"
							<?php endif; ?>>
							<?php foreach ($terms as $row) :
								$term = $row['term'];
								// own-search-modal__term (header.css): eigen class, geen
								// hergebruik van own-mobile-menu__quicklink — die volgt de
								// topbar's eigen tekstkleur (of anders de primary-preset),
								// dit moet net als own-search-modal__type meebewegen met
								// "Badge kleur" (Live search instellingen) in plaats daarvan.
								$click_expr = esc_attr('$store.liveSearch.query = ' . wp_json_encode($term) . '; $store.liveSearch.search()');
							?>
								<button
									type="button"
									class="own-search-modal__term"
									@click="<?= $click_expr; ?>"><?= esc_html($term); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Resultaten -->
			<div
				id="own-search-modal-results"
				class="own-search-modal__results flex-1 overflow-y-auto p-2"
				role="region"
				aria-live="polite">

				<p class="px-3 py-2 text-sm text-gray-500" x-show="<?= $has_results_expr; ?>">
					<span x-text="$store.liveSearch.total"></span>
					<?= esc_html__('resultaten voor', 'own-framework'); ?>
					&ldquo;<span x-text="$store.liveSearch.query"></span>&rdquo;
				</p>

				<p
					class="px-3 py-2 text-sm text-red-600"
					x-show="$store.liveSearch.error"
					x-text="$store.liveSearch.error"
					role="alert"></p>

				<ul class="own-search-modal__list" x-show="<?= $results_list_expr; ?>" role="listbox">
					<template x-for="result in $store.liveSearch.results" :key="result.id">
						<li role="option">
							<a
								:href="result.url"
								@click="$store.searchDialog.close()"
								class="own-search-modal__link flex items-start gap-3 rounded-lg p-2 hover:bg-black/3">
								<div
									class="own-search-modal__thumbnail h-12 w-12 shrink-0 overflow-hidden rounded-md bg-black/5 sm:h-14 sm:w-14"
									x-show="result.thumbnail">
									<img :src="result.thumbnail" :alt="result.title" class="h-full w-full object-cover" loading="lazy">
								</div>
								<div class="min-w-0 flex-1">
									<strong class="own-search-modal__title block text-sm font-medium" x-html="$store.liveSearch.highlight(result.title)"></strong>
									<p
										class="own-search-modal__excerpt mt-0.5 line-clamp-2 text-sm text-gray-500"
										x-show="result.excerpt"
										x-html="$store.liveSearch.highlight(result.excerpt)"></p>
								</div>
								<span class="own-search-modal__type shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" x-text="result.type"></span>
							</a>
						</li>
					</template>
				</ul>

				<p
					class="own-search-modal__empty px-3 py-6 text-center text-sm text-gray-500"
					x-show="$store.liveSearch.searched && !$store.liveSearch.loading && $store.liveSearch.results.length === 0">
					<?= esc_html__('Geen resultaten gevonden.', 'own-framework'); ?>
				</p>

			</div>
		</div>
	</div>
</div>