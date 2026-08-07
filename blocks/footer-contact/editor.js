(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;

	// Swatch clicks give a raw hex — resolved back to its theme.json slug so
	// the stored attribute stays a dynamic var(--wp--preset--color--<slug>)
	// instead of a hex frozen at pick-time (same helper as acf/header's own
	// editor.js, copied verbatim: a client changing that slug's colour later
	// via Ontwerp should still reach this block automatically).
	function resolveToPresetVar(rawColor) {
		if (!rawColor) return '';
		var settings = wp.data.select('core/block-editor').getSettings();
		var palette = (settings && settings.colors) || [];
		for (var i = 0; i < palette.length; i++) {
			if (palette[i].color === rawColor) {
				return 'var(--wp--preset--color--' + palette[i].slug + ')';
			}
		}
		return rawColor;
	}

	addFilter('editor.BlockEdit', 'own-framework/footer-contact-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/footer-contact') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.iconsColor,
								onChange: function (value) {
									props.setAttributes({ iconsColor: resolveToPresetVar(value) });
								},
								label: 'Iconen',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
