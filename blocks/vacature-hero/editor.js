/**
 * Only "Layout" settings (height/uitlijning/breadcrumbs) live here —
 * background/text colour use WordPress' own native "Kleuren" panel
 * (block.json's "supports.color"), so unlike acf/hero (which still has its
 * full media-mechanism InspectorControls) there's no custom colour panel or
 * media picker needed at all.
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
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

	addFilter('editor.BlockEdit', 'own-framework/vacature-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/vacature-hero') {
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
					el(PanelBody, { title: 'Layout', initialOpen: true },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Hoogte',
							help: '"Standaard" bepaalt de hoogte aan de hand van de inhoud, de rest garandeert een minimumhoogte ook bij weinig inhoud.',
							value: attrs.height || 'auto',
							options: [
								{ label: 'Standaard (op basis van inhoud)', value: 'auto' },
								{ label: 'Middel', value: 'medium' },
								{ label: 'Groot', value: 'large' },
								{ label: 'Volledig scherm', value: 'screen' },
							],
							onChange: setAttr('height'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Uitlijning inhoud',
							help: 'Titel, tekst en de zoekbalk samen gecentreerd, of links uitgelijnd.',
							value: attrs.contentAlign || 'center',
							options: [
								{ label: 'Gecentreerd', value: 'center' },
								{ label: 'Links', value: 'left' },
							],
							onChange: setAttr('contentAlign'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Broodkruimelpad tonen',
							help: 'Toont Home > ... bovenaan de hero, boven de inhoud. Blijft leeg op de voorpagina zelf (daar is niks om naar terug te navigeren).',
							checked: !!attrs.showBreadcrumbs,
							onChange: setAttr('showBreadcrumbs'),
						})
					)
				)
			);
		};
	});
})(window.wp);
