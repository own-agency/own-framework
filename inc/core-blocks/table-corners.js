/**
 * "Afgeronde hoeken" op core/table (Stijlen-tab → Hoeken). Aan = de radius
 * uit Thema opties → Stijlen, zoals de rest van de site; uit = rechte hoeken.
 * Bewust een schakelaar en geen vrije pixelwaarde: tabellen blijven zo in
 * lijn met de radius-schaal van de site.
 *
 * Opgeslagen als het OMGEKEERDE ("ownTableSquare", default false), zodat de
 * markup alleen verandert wanneer iemand de hoeken uitzet. Een default-true
 * attribuut met een class zou elke bestaande tabel ongeldig maken, want die
 * class staat nog niet in hun opgeslagen HTML.
 *
 * Zelfde opbouw als button-arrow-icon.js: attribuut, schakelaar, marker-class
 * in de editor-canvas én in de opgeslagen markup (core/table heeft geen PHP
 * render-callback). De vormgeving zelf: src/css/components/table.css.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var cloneElement = wp.element.cloneElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;

	var BLOCK_NAME = 'core/table';
	var MARKER_CLASS = 'has-own-square-corners';

	function withMarkerClass(className, isSquare) {
		var classes = (className || '').split(' ').filter(function (name) {
			return name && name !== MARKER_CLASS;
		});
		if (isSquare) {
			classes.push(MARKER_CLASS);
		}
		return classes.join(' ');
	}

	addFilter('blocks.registerBlockType', 'own-framework/table-corners-attribute', function (settings, name) {
		if (name !== BLOCK_NAME) {
			return settings;
		}
		settings.attributes = Object.assign({}, settings.attributes, {
			ownTableSquare: { type: 'boolean', default: false },
		});
		return settings;
	});

	addFilter('editor.BlockEdit', 'own-framework/table-corners-control', function (BlockEdit) {
		return function (props) {
			if (props.name !== BLOCK_NAME) {
				return el(BlockEdit, props);
			}
			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, { group: 'styles' },
					el(PanelBody, { title: 'Hoeken', initialOpen: true },
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Afgeronde hoeken',
							help: props.attributes.ownTableSquare
								? 'Rechte hoeken.'
								: 'Volgt de radius uit Thema opties → Stijlen.',
							checked: !props.attributes.ownTableSquare,
							onChange: function (rounded) {
								props.setAttributes({ ownTableSquare: !rounded });
							},
						})
					)
				)
			);
		};
	});

	// Editor-canvas: meteen zichtbaar, niet pas na opslaan.
	addFilter('editor.BlockListBlock', 'own-framework/table-corners-canvas-class', function (BlockListBlock) {
		return function (props) {
			if (props.name !== BLOCK_NAME || !props.attributes.ownTableSquare) {
				return el(BlockListBlock, props);
			}
			return el(BlockListBlock, Object.assign({}, props, {
				className: withMarkerClass(props.className, true),
			}));
		};
	});

	// Opgeslagen markup: de class op de <figure>.
	addFilter('blocks.getSaveElement', 'own-framework/table-corners-save-class', function (element, blockType, attributes) {
		if (blockType.name !== BLOCK_NAME || !attributes.ownTableSquare) {
			return element;
		}
		return cloneElement(element, {
			className: withMarkerClass((element.props && element.props.className) || '', true),
		});
	});
})(window.wp);
