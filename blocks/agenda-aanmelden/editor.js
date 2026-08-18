/**
 * "Kaart" (achtergrondkleur) — the only setting this block needs of its own,
 * same as blocks/vacature-apply/editor.js. Title/text are real InnerBlocks
 * (core/heading + core/paragraph, render.php) with their own native
 * "Kleuren" panel each. The "Meld je aan" button deliberately has NO colour/
 * radius setting here: it uses the theme's site-wide Knop styling directly.
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

	addFilter('editor.BlockEdit', 'own-framework/agenda-aanmelden-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/agenda-aanmelden') {
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
