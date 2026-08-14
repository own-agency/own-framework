/**
 * "Layout" (height/uitlijning/breadcrumbs) and "Zoekknop" (achtergrond-/
 * tekstkleur + een per-hoek pixel-schuifregelaar voor de hoekafronding) live
 * here — the hero's OWN background/text colour still use WordPress' native
 * "Kleuren" panel (block.json's "supports.color"), so unlike acf/hero (which
 * still has its full media-mechanism InspectorControls) there's no custom
 * colour panel needed for that.
 *
 * The button's own colours use a hand-rolled ColorPalette + BaseControl pair
 * rather than PanelColorSettings (used everywhere else in this theme, see
 * acf/vacatures' editor.js): PanelColorSettings always renders its OWN
 * separate panel, so it can't sit inside the SAME "Zoekknop" PanelBody
 * alongside the radius sliders — this block's colour + radius controls are
 * meant to live together in one place, not split across two collapsible
 * panels.
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var ColorPalette = wp.components.ColorPalette;
	var BaseControl = wp.components.BaseControl;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

	var RADIUS_CORNERS = [
		{ key: 'topLeft', label: 'Linksboven' },
		{ key: 'topRight', label: 'Rechtsboven' },
		{ key: 'bottomRight', label: 'Rechtsonder' },
		{ key: 'bottomLeft', label: 'Linksonder' },
	];
	var DEFAULT_RADIUS = { topLeft: 100, topRight: 100, bottomRight: 100, bottomLeft: 100 };

	// Resolves a raw swatch colour back to its theme.json slug — see acf/
	// vacatures' editor.js (and acf/cta's) for the full reasoning: keeps the
	// attribute dynamic if a client later changes the colour via Ontwerp.
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

	addFilter('editor.BlockEdit', 'own-framework/vacature-hero-settings', function (BlockEdit) {
		return function (props) {
			// Called unconditionally, before the early return below, so this
			// hook's call order never depends on which block is selected —
			// see this file's own docblock reasoning for why that matters.
			var linkedState = useState(true);
			var cornersLinked = linkedState[0];
			var setCornersLinked = linkedState[1];

			if (props.name !== 'acf/vacature-hero') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			// A block saved before "searchButtonRadius" became an object (it
			// used to be a "none"/"small"/.../"full" string) falls back to the
			// same default here, rather than reading properties off a string.
			var radius = (attrs.searchButtonRadius && typeof attrs.searchButtonRadius === 'object')
				? attrs.searchButtonRadius
				: DEFAULT_RADIUS;

			var settings = wp.data.select('core/block-editor').getSettings();
			var palette = (settings && settings.colors) || [];

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			function setRadiusAllCorners(value) {
				props.setAttributes({
					searchButtonRadius: {
						topLeft: value,
						topRight: value,
						bottomRight: value,
						bottomLeft: value,
					},
				});
			}

			function setRadiusCorner(corner) {
				return function (value) {
					var next = Object.assign({}, radius);
					next[corner] = value;
					props.setAttributes({ searchButtonRadius: next });
				};
			}

			var radiusControls = cornersLinked
				? el(RangeControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Hoekafronding (px)',
						help: 'Op deze knop’s eigen hoogte geeft de bovengrens (100px) een volledig ronde knop, hetzelfde resultaat als de oude "Volledig rond"-keuze.',
						value: radius.topLeft,
						min: 0,
						max: 100,
						onChange: setRadiusAllCorners,
				  })
				: el(Fragment, null, RADIUS_CORNERS.map(function (corner) {
						return el(RangeControl, {
							key: corner.key,
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: corner.label + ' (px)',
							value: radius[corner.key],
							min: 0,
							max: 100,
							onChange: setRadiusCorner(corner.key),
						});
				  }));

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
					),
					el(PanelBody, { title: 'Zoekknop', initialOpen: false },
						el(BaseControl, { label: 'Achtergrondkleur', __nextHasNoMarginBottom: true },
							el(ColorPalette, {
								colors: palette,
								value: attrs.searchButtonColor || '',
								onChange: function (value) {
									props.setAttributes({ searchButtonColor: resolveToPresetVar(value) });
								},
							})
						),
						el(BaseControl, { label: 'Tekstkleur', __nextHasNoMarginBottom: true },
							el(ColorPalette, {
								colors: palette,
								value: attrs.searchButtonTextColor || '',
								onChange: function (value) {
									props.setAttributes({ searchButtonTextColor: resolveToPresetVar(value) });
								},
							})
						),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Hoeken gelijk',
							help: 'Uit = elke hoek z’n eigen afronding.',
							checked: cornersLinked,
							onChange: setCornersLinked,
						}),
						radiusControls
					)
				)
			);
		};
	});
})(window.wp);
