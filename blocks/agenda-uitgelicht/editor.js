/**
 * "Instellingen" (bron/aantal/weergave/paginatie) en "Kleuren" (accentkleur
 * voor de categorie-badges, datumbadge-kleur, linkkleur, en — alleen bij
 * "Slider" — navigatiekleur en paginatiekleur) — de handmatige moment-
 * selectie zelf zit NIET hier: dat is een echt ACF-veld (Relationship,
 * acf-json/group_own_agenda_uitgelicht_selectie.json), dat SCF zelf al in de
 * zijbalk rendert. 1:1 poort van blocks/vacatures-uitgelicht/editor.js, met
 * één extra kleurveld ("Datumbadge") voor het "dag/maand"-vierkant dat
 * blocks/agenda/parts/card.php's kaart heeft en de vacature-kaart niet.
 *
 * Meerdere velden verbergen zich afhankelijk van een andere keuze: "Aantal
 * momenten" alleen bij "Bron" op "Eerstvolgende" (bij "Handmatig gekozen"
 * bepaalt het ACF-veld zelf hoeveel er zijn), "Paginatie-bolletjes tonen"/
 * "Navigatiekleur"/"Paginatiekleur" alleen bij "Weergave" op "Slider" (een
 * raster heeft geen knoppen/bolletjes) en "Paginatiekleur" daarbinnen ook
 * pas als "Paginatie-bolletjes tonen" zelf aanstaat.
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;

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

	addFilter('editor.BlockEdit', 'own-framework/agenda-uitgelicht-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/agenda-uitgelicht') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var isSlider = (attrs.layout || 'grid') === 'slider';

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			function setColorAttr(name) {
				return function (value) {
					var next = {};
					next[name] = resolveToPresetVar(value);
					props.setAttributes(next);
				};
			}

			var colorSettings = [
				{
					value: attrs.accentColor || '',
					onChange: setColorAttr('accentColor'),
					label: 'Accentkleur (categorie-badges)',
				},
				{
					value: attrs.dateBadgeColor || '',
					onChange: setColorAttr('dateBadgeColor'),
					label: 'Datumbadge (los van de accentkleur, leeg = zelfde kleur)',
				},
				{
					value: attrs.cardLinkColor || '',
					onChange: setColorAttr('cardLinkColor'),
					label: 'Linkkleur ("Moment bekijken")',
				},
			];

			if (isSlider) {
				colorSettings.push({
					value: attrs.navColor || '',
					onChange: setColorAttr('navColor'),
					label: 'Navigatiekleur (pijlen)',
				});

				if (attrs.showPagination) {
					colorSettings.push({
						value: attrs.paginationColor || '',
						onChange: setColorAttr('paginationColor'),
						label: 'Paginatiekleur (bolletjes)',
					});
				}
			}

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Instellingen', initialOpen: true },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Bron',
							value: attrs.source || 'upcoming',
							options: [
								{ label: 'Eerstvolgende momenten', value: 'upcoming' },
								{ label: 'Handmatig gekozen', value: 'handpicked' },
							],
							onChange: setAttr('source'),
						}),
						(attrs.source || 'upcoming') === 'upcoming' && el(RangeControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Aantal momenten',
							value: typeof attrs.count === 'number' ? attrs.count : 3,
							onChange: setAttr('count'),
							min: 1,
							max: 12,
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Weergave',
							value: attrs.layout || 'grid',
							options: [
								{ label: 'Raster', value: 'grid' },
								{ label: 'Slider', value: 'slider' },
							],
							onChange: setAttr('layout'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Kop-niveau titel kaart',
							help: 'Standaard H3, ondergeschikt aan de H2 van de titel hierboven — pas dit alleen aan als de pagina-structuur elders om een ander niveau vraagt.',
							value: String(attrs.cardTitleLevel || 3),
							options: [
								{ label: 'H2', value: '2' },
								{ label: 'H3', value: '3' },
								{ label: 'H4', value: '4' },
								{ label: 'H5', value: '5' },
								{ label: 'H6', value: '6' },
							],
							onChange: function (value) {
								props.setAttributes({ cardTitleLevel: parseInt(value, 10) });
							},
						}),
						isSlider && el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Paginatie-bolletjes tonen',
							checked: !!attrs.showPagination,
							onChange: setAttr('showPagination'),
						})
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: colorSettings,
					})
				)
			);
		};
	});
})(window.wp);
