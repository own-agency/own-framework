/**
 * "Titel" en "Kolommen" als plain block attributes (zie block.json) — zelfde
 * reden als acf/proces' eigen editor.js. Geen kleurenpaneel (in tegenstelling
 * tot acf/proces) — elk voordeel's eigen icoon is een genest "core/icon"-
 * block met zijn EIGEN native kleurenpaneel, dus er is hier niets meer te
 * kleuren namens de kinderen (zie render.php's eigen docblock).
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;

	addFilter('editor.BlockEdit', 'own-framework/voordelen-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/voordelen') {
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
							help: 'Verschijnt boven de voordelen. Leeg laten om te verbergen.',
							value: attrs.title || '',
							onChange: setAttr('title'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Kolommen',
							help: 'Alleen vanaf tablet-breedte — op mobiel staat het altijd in één kolom.',
							value: attrs.columns || '2',
							options: [
								{ label: '2 kolommen', value: '2' },
								{ label: '3 kolommen', value: '3' },
							],
							onChange: setAttr('columns'),
						})
					)
				)
			);
		};
	});
})(window.wp);
