/**
 * "Uitlijning menu", "Sticky header" en "Schaduw bij scrollen" as plain block
 * attributes (menuAlignment/isSticky/showScrollShadow, see block.json), not
 * SCF fields — they're purely visual/behavioural choices with no content
 * value, so they belong with the rest of this block's native settings
 * (align, color) rather than in the SCF fields modal. block.json's
 * "editorScript" enqueues this automatically, only when acf/header is in
 * use — no manual enqueue needed.
 *
 * Plain wp.element.createElement (no JSX) so this ships as-is, no Vite/babel
 * build step required.
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

	// Resolves a raw swatch colour (e.g. "#213547") back to its theme.json
	// slug so the attribute stores `var(--wp--preset--color--<slug>)` instead
	// of a frozen hex value — same reasoning as $header_bg_value in
	// render.php: stays dynamic if the client repaints the palette later via
	// Ontwerp. Falls back to the raw value for a genuinely custom colour that
	// isn't in the palette at all.
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

	addFilter(
		'editor.BlockEdit',
		'own-framework/header-settings',
		function (BlockEdit) {
			return function (props) {
				if (props.name !== 'acf/header') {
					return el(BlockEdit, props);
				}

				// Only meaningful while the header actually starts transparent —
				// without that toggle there's no "start colour" phase to give a
				// separate colour to.
				var transparentTextColorControl = props.attributes.transparentUntilScroll
					? el(PanelColorSettings, {
							title: 'Tekstkleur bij transparante start',
							initialOpen: true,
							colorSettings: [
								{
									value: props.attributes.transparentTextColor || '',
									onChange: function (value) {
										props.setAttributes({
											transparentTextColor: resolveToPresetVar(value),
										});
									},
									label: 'Tekstkleur (transparante staat)',
								},
							],
					  })
					: null;

				// Only relevant for the mega-menu submenu style itself — the
				// regular dropdown has no equivalent settings (it already
				// follows the header's own native background/text colour).
				var megaMenuColorControl = props.attributes.submenuStyle === 'megamenu'
					? el(PanelColorSettings, {
							title: 'Kleuren uitklapmenu',
							initialOpen: true,
							colorSettings: [
								{
									value: props.attributes.megaMenuBackgroundColor || '',
									onChange: function (value) {
										props.setAttributes({
											megaMenuBackgroundColor: resolveToPresetVar(value),
										});
									},
									label: 'Achtergrond',
								},
								{
									value: props.attributes.megaMenuTitleColor || '',
									onChange: function (value) {
										props.setAttributes({
											megaMenuTitleColor: resolveToPresetVar(value),
										});
									},
									label: 'Kolomtitel',
								},
								{
									value: props.attributes.megaMenuTextColor || '',
									onChange: function (value) {
										props.setAttributes({
											megaMenuTextColor: resolveToPresetVar(value),
										});
									},
									label: 'Tekst (links)',
								},
							],
					  })
					: null;

				return el(
					Fragment,
					null,
					el(BlockEdit, props),
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: 'Header-instellingen', initialOpen: true },
							el(SelectControl, {
								label: 'Uitlijning menu',
								help: 'Logo staat altijd links, zoeken/CTA altijd rechts — dit bepaalt alleen waar het menu ertussenin komt te staan.',
								value: props.attributes.menuAlignment || 'center',
								options: [
									{ label: 'Links (naast logo)', value: 'start' },
									{ label: 'Gecentreerd', value: 'center' },
									{ label: 'Rechts (naast zoekglas)', value: 'end' },
								],
								onChange: function (value) {
									props.setAttributes({ menuAlignment: value });
								},
							}),
							el(SelectControl, {
								label: 'Type submenu',
								help: 'Dropdown toont subitems recht onder het hoofditem. Uitklapmenu toont ze in kolommen over de volledige breedte (2e-niveau items worden kolomtitels, 3e-niveau items de links eronder) — bedoeld voor hoofditems met meerdere, gegroepeerde subitems.',
								value: props.attributes.submenuStyle || 'dropdown',
								options: [
									{ label: 'Dropdown', value: 'dropdown' },
									{ label: 'Uitklapmenu (volledige breedte)', value: 'megamenu' },
								],
								onChange: function (value) {
									props.setAttributes({ submenuStyle: value });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Sticky header',
								help: 'Header blijft bovenaan zichtbaar tijdens het scrollen.',
								checked: props.attributes.isSticky !== false,
								onChange: function (value) {
									props.setAttributes({ isSticky: value });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Transparante header',
								help: 'Header start zonder achtergrondkleur en krijgt die pas terug (met schaduw, indien aan) zodra er gescrold wordt.',
								checked: !!props.attributes.transparentUntilScroll,
								onChange: function (value) {
									props.setAttributes({ transparentUntilScroll: value });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Schaduw bij scrollen',
								help: 'Toont een schaduw onder de header zodra er gescrold wordt.',
								checked: !!props.attributes.showScrollShadow,
								onChange: function (value) {
									props.setAttributes({ showScrollShadow: value });
								},
							})
						),
						transparentTextColorControl,
						megaMenuColorControl
					)
				);
			};
		}
	);
})(window.wp);
