/**
 * Hoogte, zoomniveau en de twee stijl-toggles als plain block attributes (zie
 * block.json), zelfde reden als de andere blocks in deze theme: puur visuele
 * keuzes zonder losse SCF-velden nodig.
 *
 * Er is met opzet GEEN adresveld hier — het adres komt uit Thema opties →
 * Algemeen → Contact (zie render.php), zodat het maar op één plek onderhouden
 * hoeft te worden.
 *
 * Plain wp.element.createElement (geen JSX) zodat dit zonder Vite/babel-
 * build-stap werkt.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;

	addFilter('editor.BlockEdit', 'own-framework/google-maps-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/google-maps') {
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
					el(PanelBody, { title: 'Kaartinstellingen', initialOpen: true },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Hoogte',
							value: attrs.height || 'medium',
							options: [
								{ label: 'Klein', value: 'small' },
								{ label: 'Middel', value: 'medium' },
								{ label: 'Groot', value: 'large' },
							],
							onChange: setAttr('height'),
						}),
						el(RangeControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Zoomniveau',
							help: 'Lager toont meer omgeving, hoger zoomt in op straatniveau.',
							value: attrs.zoom || 15,
							min: 1,
							max: 21,
							onChange: function (value) {
								// RangeControl geeft undefined terug als het veld leeg
								// wordt gesleept/getypt; het attribuut is een number
								// (block.json), dus dan terug naar de default i.p.v.
								// undefined opslaan.
								props.setAttributes({ zoom: typeof value === 'number' ? value : 15 });
							},
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Afgeronde hoeken',
							checked: attrs.rounded !== false,
							onChange: setAttr('rounded'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Zwart-wit',
							help: 'Toont de kaart in grijstinten, zodat die rustiger meekleurt met de rest van de pagina.',
							checked: !!attrs.grayscale,
							onChange: setAttr('grayscale'),
						})
					)
				)
			);
		};
	});
})(window.wp);
