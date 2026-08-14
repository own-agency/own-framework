/**
 * "Kaart" (achtergrondkleur) — the only setting this block needs of its own.
 * The title/text are real InnerBlocks (core/heading + core/paragraph,
 * render.php) with their own native "Kleuren" panel each. The "Solliciteren"
 * button deliberately has NO colour/radius setting here at all: it uses the
 * theme's site-wide Knop styling directly ("wp-element-button
 * wp-block-button__link" classes in render.php), so it always matches
 * Ontwerp → Stijlen → Knoppen rather than a one-off per-block colour.
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var ColorPalette = wp.components.ColorPalette;
	var BaseControl = wp.components.BaseControl;
	var PanelBody = wp.components.PanelBody;

	// Resolves a raw swatch colour back to its theme.json slug — see acf/
	// vacature-hero's/acf/vacatures' own editor.js for the full reasoning:
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

	addFilter('editor.BlockEdit', 'own-framework/vacature-apply-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/vacature-apply') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var settings = wp.data.select('core/block-editor').getSettings();
			var palette = (settings && settings.colors) || [];

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Kaart', initialOpen: true },
						el(BaseControl, { label: 'Achtergrondkleur', __nextHasNoMarginBottom: true },
							el(ColorPalette, {
								colors: palette,
								value: attrs.cardBackgroundColor || '',
								onChange: function (value) {
									props.setAttributes({ cardBackgroundColor: resolveToPresetVar(value) });
								},
							})
						)
					)
				)
			);
		};
	});
})(window.wp);
