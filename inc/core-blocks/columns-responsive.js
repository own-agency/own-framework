/**
 * "Responsive"-paneel op core/columns: kolommen per rij op tablet en mobiel
 * (ownColumnsTablet/ownColumnsMobile, columns-responsive.php) plus de toggle
 * "Omdraaien op mobiel" (ownReverseOnMobile, columns-reverse-mobile.php) —
 * dat attribute bestond al, maar had tot nu toe geen UI.
 *
 * Alleen editor-kant: de front-end-classes zet PHP (render_block_core/
 * columns), zodat de opgeslagen markup ongewijzigd blijft en bestaande
 * blocks hun validatie houden. Hier komen dezelfde classes alleen op de
 * canvas, zodat de tablet/mobiel-preview meteen klopt.
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
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

	var BLOCK_NAME = 'core/columns';

	// Gelijk houden met OWNFRAMEWORK_COLUMNS_*_OPTIONS in columns-responsive.php.
	var TABLET_OPTIONS = [
		{ label: 'Standaard (zoals desktop)', value: '0' },
		{ label: '1 kolom', value: '1' },
		{ label: '2 kolommen', value: '2' },
		{ label: '3 kolommen', value: '3' },
		{ label: '4 kolommen', value: '4' },
	];
	var MOBILE_OPTIONS = [
		{ label: 'Standaard (volgt "Onder elkaar op mobiel")', value: '0' },
		{ label: '2 kolommen', value: '2' },
		{ label: '3 kolommen', value: '3' },
	];

	function responsiveClasses(attributes) {
		var classes = [];
		if (attributes.ownColumnsTablet) {
			classes.push('own-cols-tablet-' + attributes.ownColumnsTablet);
		}
		if (attributes.ownColumnsMobile) {
			classes.push('own-cols-mobile-' + attributes.ownColumnsMobile);
		}
		if (attributes.ownReverseOnMobile) {
			classes.push('own-columns-reverse-mobile');
		}
		return classes;
	}

	// Also registered server-side; declaring them here too keeps the editor
	// from depending on server-definition bootstrapping for a core block.
	addFilter('blocks.registerBlockType', 'own-framework/columns-responsive-attributes', function (settings, name) {
		if (name !== BLOCK_NAME) {
			return settings;
		}
		settings.attributes = Object.assign({}, settings.attributes, {
			ownColumnsTablet: { type: 'integer', default: 0 },
			ownColumnsMobile: { type: 'integer', default: 0 },
			ownReverseOnMobile: { type: 'boolean', default: false },
		});
		return settings;
	});

	addFilter('editor.BlockEdit', 'own-framework/columns-responsive-controls', function (BlockEdit) {
		return function (props) {
			if (props.name !== BLOCK_NAME) {
				return el(BlockEdit, props);
			}
			var attributes = props.attributes;
			var mobileIsGrid = !!attributes.ownColumnsMobile;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Responsive', initialOpen: false },
						el('p', { style: { marginTop: 0 } },
							'Desktop (vanaf 1024px) toont altijd alle kolommen naast elkaar.'
						),
						el(SelectControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: 'Kolommen per rij op tablet',
							help: '600–1023px. Extra kolommen lopen door naar een nieuwe rij.',
							value: String(attributes.ownColumnsTablet || 0),
							options: TABLET_OPTIONS,
							onChange: function (value) {
								props.setAttributes({ ownColumnsTablet: parseInt(value, 10) || 0 });
							},
						}),
						el('div', { style: { height: 16 } }),
						el(SelectControl, {
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							label: 'Kolommen per rij op mobiel',
							help: 'Onder 600px.',
							value: String(attributes.ownColumnsMobile || 0),
							options: MOBILE_OPTIONS,
							onChange: function (value) {
								var columns = parseInt(value, 10) || 0;
								var next = { ownColumnsMobile: columns };
								// Reversing only applies to a single stacked
								// column; clear it rather than leave a stale
								// setting that silently does nothing.
								if (columns) {
									next.ownReverseOnMobile = false;
								}
								props.setAttributes(next);
							},
						}),
						!mobileIsGrid && el('div', { style: { height: 16 } }),
						!mobileIsGrid && el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Omdraaien op mobiel',
							help: 'Toont de kolommen in omgekeerde volgorde als ze onder elkaar staan.',
							checked: !!attributes.ownReverseOnMobile,
							onChange: function (value) {
								props.setAttributes({ ownReverseOnMobile: value });
							},
						})
					)
				)
			);
		};
	});

	addFilter('editor.BlockListBlock', 'own-framework/columns-responsive-canvas-class', function (BlockListBlock) {
		return function (props) {
			if (props.name !== BLOCK_NAME) {
				return el(BlockListBlock, props);
			}
			var classes = responsiveClasses(props.attributes);
			if (!classes.length) {
				return el(BlockListBlock, props);
			}
			return el(BlockListBlock, Object.assign({}, props, {
				className: [props.className].concat(classes).filter(Boolean).join(' '),
			}));
		};
	});
})(window.wp);
