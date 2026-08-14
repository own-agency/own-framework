/**
 * "accentColor" and the vacature-kaart's own "cardTitleColor"/"cardTextColor"/
 * "cardBackgroundColor"/"cardLinkColor" as plain block attributes (block.json)
 * + one shared "Kleuren" panel, exactly like acf/cta's "cardBackgroundColor"
 * — badges/checkboxes/pagination/the card's own title/text/background/link
 * all live inside sub-elements the native "Kleuren" panel can't reach (it
 * only ever colours the block's own wrapper, see CLAUDE.md Traps #7), so
 * block.json deliberately has no "supports.color".
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
	var RangeControl = wp.components.RangeControl;

	// Resolves a raw swatch colour back to its theme.json slug — see acf/
	// cta's editor.js for the full reasoning (keeps the attribute dynamic if
	// a client later changes the colour via Ontwerp).
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

	addFilter('editor.BlockEdit', 'own-framework/vacatures-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/vacatures') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Resultaten per pagina', initialOpen: true },
						el(RangeControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Vacatures per pagina',
							value: typeof attrs.perPage === 'number' ? attrs.perPage : 10,
							onChange: function (value) {
								props.setAttributes({ perPage: value });
							},
							min: 3,
							max: 30,
						})
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.accentColor || '',
								onChange: function (value) {
									props.setAttributes({ accentColor: resolveToPresetVar(value) });
								},
								label: 'Accentkleur (badges, filters, paginering)',
							},
							{
								value: attrs.cardTitleColor || '',
								onChange: function (value) {
									props.setAttributes({ cardTitleColor: resolveToPresetVar(value) });
								},
								label: 'Vacature-kaart: titelkleur',
							},
							{
								value: attrs.cardTextColor || '',
								onChange: function (value) {
									props.setAttributes({ cardTextColor: resolveToPresetVar(value) });
								},
								label: 'Vacature-kaart: tekstkleur',
							},
							{
								value: attrs.cardBackgroundColor || '',
								onChange: function (value) {
									props.setAttributes({ cardBackgroundColor: resolveToPresetVar(value) });
								},
								label: 'Vacature-kaart: achtergrondkleur',
							},
							{
								value: attrs.cardLinkColor || '',
								onChange: function (value) {
									props.setAttributes({ cardLinkColor: resolveToPresetVar(value) });
								},
								label: 'Vacature-kaart: linkkleur (los van de badges)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
