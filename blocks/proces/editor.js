/**
 * "Titel" en de drie stap-kleuren als plain block attributes (zie
 * block.json) — zelfde reden als acf/feitjes-slider's eigen editor.js. De
 * stappen zelf staan NIET hier: dat is InnerBlocks (acf/proces-stap), dus
 * geen herhaalveld-UI zoals feitjes-slider's "facts"-array nodig.
 *
 * "Kleur stappen" is een eigen PanelColorSettings i.p.v. het native
 * "Kleuren"-paneel: dat kleurt via get_block_wrapper_attributes() altijd de
 * hele sectie, terwijl deze drie kleuren specifiek het rondje/nummer/lijn van
 * elke stap raken — een sub-element (binnen een ANDER, genest block) dat de
 * wrapper niet bereikt. Zelfde patroon als acf/feitjes-slider's
 * "Kleur feitjes"-paneel.
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
	var TextControl = wp.components.TextControl;

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

	addFilter('editor.BlockEdit', 'own-framework/proces-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/proces') {
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

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Inhoud', initialOpen: true },
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Titel',
							help: 'Verschijnt boven de stappen. Leeg laten om te verbergen.',
							value: attrs.title || '',
							onChange: setAttr('title'),
						})
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur stappen',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.stepColor || '',
								onChange: function (value) {
									props.setAttributes({ stepColor: resolveToPresetVar(value) });
								},
								label: 'Rondje',
							},
							{
								value: attrs.stepNumberColor || '',
								onChange: function (value) {
									props.setAttributes({ stepNumberColor: resolveToPresetVar(value) });
								},
								label: 'Nummer / vinkje',
							},
							{
								value: attrs.stepLineColor || '',
								onChange: function (value) {
									props.setAttributes({ stepLineColor: resolveToPresetVar(value) });
								},
								label: 'Verbindingslijn',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
