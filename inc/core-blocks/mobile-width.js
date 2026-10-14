/**
 * "Breedte op mobiel" op core/image en core/site-logo — de editor-kant van
 * mobile-width.php (attribute + front-end-class/custom property daar).
 *
 * De front-end-markup zet PHP (render_block_<name>), zodat de opgeslagen
 * markup ongewijzigd blijft en bestaande blocks hun validatie houden. Hier
 * alleen dezelfde class + --own-mobile-width op de canvas, zodat de
 * mobiel-preview van de editor het meteen laat zien.
 *
 * Plain wp.element.createElement (geen JSX), zelfde aanpak als
 * columns-reverse-mobile.js.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;

	var BLOCK_NAMES = ['core/image', 'core/site-logo'];
	var MARKER_CLASS = 'own-has-mobile-width';

	// Also registered server-side; declaring it here too keeps the editor
	// from depending on server-definition bootstrapping for a core block.
	addFilter('blocks.registerBlockType', 'own-framework/mobile-width-attribute', function (settings, name) {
		if (BLOCK_NAMES.indexOf(name) === -1) {
			return settings;
		}
		settings.attributes = Object.assign({}, settings.attributes, {
			ownMobileWidth: { type: 'integer' },
		});
		return settings;
	});

	addFilter('editor.BlockEdit', 'own-framework/mobile-width-control', function (BlockEdit) {
		return function (props) {
			if (BLOCK_NAMES.indexOf(props.name) === -1) {
				return el(BlockEdit, props);
			}
			var value = props.attributes.ownMobileWidth;
			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Mobiel', initialOpen: !!value },
						el(TextControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							type: 'number',
							min: 1,
							label: 'Breedte op mobiel (px)',
							help: 'Overschrijft de breedte onder 782px. Leeg = zelfde breedte als op desktop.',
							value: value ? String(value) : '',
							onChange: function (next) {
								var width = parseInt(next, 10);
								props.setAttributes({ ownMobileWidth: width > 0 ? width : undefined });
							},
						})
					)
				)
			);
		};
	});

	addFilter('editor.BlockListBlock', 'own-framework/mobile-width-canvas', function (BlockListBlock) {
		return function (props) {
			var width = props.attributes.ownMobileWidth;
			if (BLOCK_NAMES.indexOf(props.name) === -1 || !width) {
				return el(BlockListBlock, props);
			}
			var wrapperProps = props.wrapperProps || {};
			return el(BlockListBlock, Object.assign({}, props, {
				className: [props.className, MARKER_CLASS].filter(Boolean).join(' '),
				wrapperProps: Object.assign({}, wrapperProps, {
					style: Object.assign({}, wrapperProps.style, { '--own-mobile-width': width + 'px' }),
				}),
			}));
		};
	});
})(window.wp);
