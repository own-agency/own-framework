/**
 * "Toon pijl-icoon" op core/button — een eigen "ownArrowIcon"-attribute plus
 * een marker-class ("has-own-arrow-icon") op de ".wp-block-button"-wrapper,
 * zowel in de editor-canvas als in de opgeslagen markup. Geen echt
 * geïnjecteerd SVG-element: core/button's save() rendert de knoptekst via
 * RichText.Content, dat geen schone plek biedt om een sibling-icoon als
 * React-element naast in te voegen zonder Gutenberg-interne opbouw te moeten
 * namaken. Het icoon zelf is daarom puur CSS (src/css/components/
 * button-arrow-icon.css, ::after met mask-image op ".wp-block-button__link",
 * geraakt via deze marker-class op zijn ouder) — zie dat bestand's eigen
 * docblock voor de volle reden en de svg-data-URI.
 *
 * Plain wp.element.createElement (geen JSX) zodat dit zonder Vite/babel-
 * build-stap werkt, zelfde aanpak als de andere blocks in deze theme.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var cloneElement = wp.element.cloneElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;

	var BLOCK_NAME = 'core/button';
	var MARKER_CLASS = 'has-own-arrow-icon';

	function withMarkerClass(className, hasIcon) {
		var classes = (className || '').split(' ').filter(function (name) {
			return name && name !== MARKER_CLASS;
		});
		if (hasIcon) {
			classes.push(MARKER_CLASS);
		}
		return classes.join(' ');
	}

	addFilter('blocks.registerBlockType', 'own-framework/button-arrow-icon-attribute', function (settings, name) {
		if (name !== BLOCK_NAME) {
			return settings;
		}
		settings.attributes = Object.assign({}, settings.attributes, {
			ownArrowIcon: { type: 'boolean', default: false },
		});
		return settings;
	});

	// Instellingen-paneel — "Toon pijl-icoon".
	addFilter('editor.BlockEdit', 'own-framework/button-arrow-icon-control', function (BlockEdit) {
		return function (props) {
			if (props.name !== BLOCK_NAME) {
				return el(BlockEdit, props);
			}
			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Icoon', initialOpen: false },
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Toon pijl-icoon',
							help: 'Toont een pijl-icoon na de knoptekst.',
							checked: !!props.attributes.ownArrowIcon,
							onChange: function (value) {
								props.setAttributes({ ownArrowIcon: value });
							},
						})
					)
				)
			);
		};
	});

	// Marker-class op de wrapper in de editor-canvas — laat het icoon al
	// zien tijdens het bewerken, niet pas na opslaan/herladen.
	addFilter('editor.BlockListBlock', 'own-framework/button-arrow-icon-canvas-class', function (BlockListBlock) {
		return function (props) {
			if (props.name !== BLOCK_NAME || !props.attributes.ownArrowIcon) {
				return el(BlockListBlock, props);
			}
			return el(BlockListBlock, Object.assign({}, props, {
				className: withMarkerClass(props.className, true),
			}));
		};
	});

	// Dezelfde marker-class op de wrapper in de OPGESLAGEN markup — core/
	// button heeft geen PHP render-callback, dus dit (niet een render_block-
	// filter) is wat de front-end daadwerkelijk laat zien.
	addFilter('blocks.getSaveElement', 'own-framework/button-arrow-icon-save-class', function (element, blockType, attributes) {
		if (blockType.name !== BLOCK_NAME || !attributes.ownArrowIcon) {
			return element;
		}
		var existingClass = (element.props && element.props.className) || '';
		return cloneElement(element, {
			className: withMarkerClass(existingClass, true),
		});
	});
})(window.wp);
