/**
 * "accentColor" as a plain block attribute (block.json) + one "Kleuren"
 * panel, exact same pattern as acf/vacatures' own "accentColor" — it drives
 * the active-page indicator's border/tint/text, the row hover tint and the
 * nested-tree guide-line (render.php/zijbalk-navigatie.css), all
 * sub-elements the native "Kleuren" panel can't reach on their own (CLAUDE.md
 * Traps #7) since that panel only ever colours the block's own <nav> wrapper
 * (background/text, still native via block.json's "supports.color"). The
 * active link's text is NOT a separate setting: render.php auto-adjusts it
 * FROM this one colour (dark accent → text in the accent itself, light/
 * pastel accent → a neutral dark fallback, since light-on-light would be
 * unreadable against the indicator's light background tint) — one control
 * to reason about instead of a second colour that could clash with it. Left
 * empty, everything falls back to the theme's own primary colour token — no
 * choice forced on an editor who just wants the default look.
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

	addFilter('editor.BlockEdit', 'own-framework/zijbalk-navigatie-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/zijbalk-navigatie') {
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
								value: attrs.accentColor || '',
								onChange: function (value) {
									props.setAttributes({ accentColor: resolveToPresetVar(value) });
								},
								label: 'Accentkleur (actieve pagina, hover, lijnen)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
