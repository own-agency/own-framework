/**
 * "Titel", "Aantal naast elkaar", de paginatie-toggle en de kleurkeuzes als
 * plain block attributes (zie block.json), zelfde reden als acf/afbeeldingen-
 * slider's eigen editor.js: puur visuele keuzes, geen losse SCF-velden nodig.
 * De slides zelf zijn geneste acf/content-blok-blocks (InnerBlocks, zie
 * render.php) — die houden hun eigen instellingen, daar hoort hier dus niets
 * voor te staan.
 *
 * Plain wp.element.createElement (geen JSX) zodat dit zonder Vite/babel-
 * build-stap werkt, zelfde aanpak als de andere blocks in deze theme.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken.
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

	// Resolves a raw swatch colour back to its theme.json slug, zelfde reden
	// als acf/header's resolveToPresetVar: de attribute blijft dynamisch als
	// een klant de kleur later via Ontwerp aanpast, i.p.v. een vaste hexwaarde.
	function resolveToPresetVar(rawColor) {
		if (!rawColor) {
			return '';
		}
		var settings = wp.data.select('core/block-editor').getSettings();
		var palette = (settings && settings.colors) || [];
		for (var i = 0; i < palette.length; i++) {
			if (palette[i].color === rawColor) {
				return 'var(--wp--preset--color--' + palette[i].slug + ')';
			}
		}
		return rawColor;
	}

	addFilter('editor.BlockEdit', 'own-framework/content-blokken-slider-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/content-blokken-slider') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			var paginationColorControl = attrs.showPagination
				? el(PanelColorSettings, {
						title: 'Kleur paginatie',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.paginationColor || '',
								onChange: function (value) {
									props.setAttributes({ paginationColor: resolveToPresetVar(value) });
								},
								label: 'Actieve bolletje',
							},
						],
				  })
				: null;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Inhoud', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Titel',
								help: 'Verschijnt links boven de slider. Leeg laten om te verbergen.',
								value: attrs.title || '',
								onChange: setAttr('title'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Aantal naast elkaar',
								help: 'Geldt vanaf grote schermen. Op tablet staan er altijd 2 naast elkaar, op mobiel 1.',
								// SelectControl geeft altijd een string terug, maar het
								// attribuut is een number (block.json) — zonder deze
								// omzetting slaat het "3" op i.p.v. 3, en valt render.php's
								// eigen in_array(..., true)-check terug op de default.
								value: String(attrs.slidesPerView || 3),
								options: [
									{ label: '2 blokken', value: '2' },
									{ label: '3 blokken', value: '3' },
									{ label: '4 blokken', value: '4' },
								],
								onChange: function (value) {
									props.setAttributes({ slidesPerView: parseInt(value, 10) });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Paginatie-bolletjes tonen',
								help: 'Toont klikbare bolletjes onder de slider.',
								checked: !!attrs.showPagination,
								onChange: setAttr('showPagination'),
							})
						)
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur navigatieknoppen',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.navColor || '',
								onChange: function (value) {
									props.setAttributes({ navColor: resolveToPresetVar(value) });
								},
								label: 'Vorige / volgende knoppen',
							},
						],
					}),
					paginationColorControl
				)
			);
		};
	});
})(window.wp);
