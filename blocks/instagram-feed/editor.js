/**
 * Weergave, aantal posts en afgeronde hoeken als plain block attributes
 * (block.json).
 * Geen account- of tokenveld: de koppeling is per site en staat op de
 * ontwikkelaarspagina, zodat een redacteur hem niet kan breken.
 *
 * Plain wp.element.createElement (geen JSX), zelfde reden als de andere
 * blocks: geen build-stap voor editor-scripts.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

	addFilter('editor.BlockEdit', 'own-framework/instagram-feed-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/instagram-feed') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Feedinstellingen', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Weergave',
								value: attrs.layout === 'grid' ? 'grid' : 'cards',
								options: [
									{ label: 'Kaarten (met naam en bijschrift)', value: 'cards' },
									{ label: "Alleen foto's", value: 'grid' },
								],
								onChange: function (value) {
									props.setAttributes({ layout: value });
								},
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Aantal posts',
								help: 'Hoeveel posts naast elkaar passen, hangt af van de breedte van het blok.',
								value: String(attrs.count || 4),
								options: [
									{ label: '3', value: '3' },
									{ label: '4', value: '4' },
									{ label: '6', value: '6' },
									{ label: '8', value: '8' },
								],
								onChange: function (value) {
									props.setAttributes({ count: parseInt(value, 10) || 4 });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Afgeronde hoeken',
								checked: attrs.rounded !== false,
								onChange: function (value) {
									props.setAttributes({ rounded: value });
								},
							})
						)
					)
				)
			);
		};
	});
})(window.wp);
