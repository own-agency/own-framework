/**
 * "Omdraaien op mobiel" op core/columns — de editor-kant van
 * columns-reverse-mobile.php (attribute + front-end-class daar). Alleen
 * zichtbaar zolang "Stapel op mobiel" aan staat: omdraaien doet alleen iets
 * als de kolommen onder elkaar staan.
 *
 * De front-end-class zet PHP (render_block_core/columns), zodat de
 * opgeslagen markup ongewijzigd blijft en bestaande blocks hun validatie
 * houden. Hier alleen dezelfde class op de canvas, voor de mobiel-preview.
 *
 * Plain wp.element.createElement (geen JSX), zelfde aanpak als
 * button-arrow-icon.js.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;

	var BLOCK_NAME = 'core/columns';
	var MARKER_CLASS = 'own-columns-reverse-mobile';

	// Also registered server-side; declaring it here too keeps the editor
	// from depending on server-definition bootstrapping for a core block.
	addFilter('blocks.registerBlockType', 'own-framework/columns-reverse-mobile-attribute', function (settings, name) {
		if (name !== BLOCK_NAME) {
			return settings;
		}
		settings.attributes = Object.assign({}, settings.attributes, {
			ownReverseOnMobile: { type: 'boolean', default: false },
		});
		return settings;
	});

	addFilter('editor.BlockEdit', 'own-framework/columns-reverse-mobile-control', function (BlockEdit) {
		return function (props) {
			if (props.name !== BLOCK_NAME || props.attributes.isStackedOnMobile === false) {
				return el(BlockEdit, props);
			}
			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Mobiel', initialOpen: false },
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Omdraaien op mobiel',
							help: 'Toont de kolommen in omgekeerde volgorde als ze onder elkaar staan.',
							checked: !!props.attributes.ownReverseOnMobile,
							onChange: function (value) {
								props.setAttributes({ ownReverseOnMobile: value });
							},
						})
					)
				)
			);
		};
	});

	addFilter('editor.BlockListBlock', 'own-framework/columns-reverse-mobile-canvas-class', function (BlockListBlock) {
		return function (props) {
			if (props.name !== BLOCK_NAME || !props.attributes.ownReverseOnMobile) {
				return el(BlockListBlock, props);
			}
			return el(BlockListBlock, Object.assign({}, props, {
				className: [props.className, MARKER_CLASS].filter(Boolean).join(' '),
			}));
		};
	});
})(window.wp);
