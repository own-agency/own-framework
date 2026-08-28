/**
 * "Layout" (uitlijning/broodkruimelpad) and "Kleuren" (broodkruimelpad/
 * titel/omschrijving/badges — elk een eigen attribute, zie render.php's
 * docblock for why) live here. The hero's own BACKGROUND still uses
 * WordPress' native "Kleuren" panel (block.json's "supports.color.
 * background") — only "text" is off there, replaced by the four granular
 * attributes below, so PanelColorSettings (one shared panel, same as acf/
 * vacatures' editor.js) fits without needing a hand-rolled ColorPalette like
 * acf/vacature-hero's own "Zoekknop" panel (that one also needed a
 * non-colour radius control in the SAME panel; this block's colour settings
 * are colours only, so the standard component works here).
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
	var ToggleControl = wp.components.ToggleControl;
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken. Overal in dit bestand dezelfde
	// spacing={4}, i.p.v. de losse marginTop/marginBottom-waarden per veld
	// die dit vroeger deden.
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

	// Resolves a raw swatch colour back to its theme.json slug — see acf/
	// vacatures'/acf/vacature-hero's own editor.js for the full reasoning:
	// keeps the attribute dynamic if a client later changes the colour via
	// Ontwerp.
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

	addFilter('editor.BlockEdit', 'own-framework/vacature-single-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/vacature-single-hero') {
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

			function setColorAttr(name) {
				return function (value) {
					var next = {};
					next[name] = resolveToPresetVar(value);
					props.setAttributes(next);
				};
			}

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Layout', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Uitlijning inhoud',
								help: 'Broodkruimelpad, titel, omschrijving en badges links uitgelijnd, of gecentreerd.',
								value: attrs.contentAlign || 'left',
								options: [
									{ label: 'Links', value: 'left' },
									{ label: 'Gecentreerd', value: 'center' },
								],
								onChange: setAttr('contentAlign'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Verticale uitlijning',
								help: 'Alleen zichtbaar effect als er een uitgelichte afbeelding is ingesteld — bepaalt of de tekst boven-, midden- of onderaan de afbeelding uitlijnt.',
								value: attrs.contentVerticalAlign || 'top',
								options: [
									{ label: 'Bovenaan', value: 'top' },
									{ label: 'Gecentreerd', value: 'center' },
									{ label: 'Onderaan', value: 'bottom' },
								],
								onChange: setAttr('contentVerticalAlign'),
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Broodkruimelpad tonen',
								checked: attrs.showBreadcrumbs !== false,
								onChange: setAttr('showBreadcrumbs'),
							})
						)
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.breadcrumbColor || '',
								onChange: setColorAttr('breadcrumbColor'),
								label: 'Broodkruimelpad',
							},
							{
								value: attrs.titleColor || '',
								onChange: setColorAttr('titleColor'),
								label: 'Titel',
							},
							{
								value: attrs.descriptionColor || '',
								onChange: setColorAttr('descriptionColor'),
								label: 'Omschrijving (en datum)',
							},
							{
								value: attrs.badgeAccentColor || '',
								onChange: setColorAttr('badgeAccentColor'),
								label: 'Badges (accentkleur)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
