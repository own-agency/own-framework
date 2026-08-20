/**
 * "Layout" (hoogte/uitlijning/breadcrumbs) — de hero's eigen achtergrond/
 * tekstkleur gebruiken WordPress' native "Kleuren"-paneel (block.json's
 * "supports.color"): één te kleuren zone is al volledig gedekt door dat
 * native paneel, dus geen eigen kleurattribuut/-paneel nodig daarvoor.
 * Zelfde "Layout"-paneel als acf/agenda-hero's eigen editor.js.
 *
 * "accentColor" IS zijn eigen plain attribute + paneel (CLAUDE.md Traps #7):
 * de filterbalk-dropdown-triggers/badges (parts/filter-bar.php) zijn
 * sub-elementen die het native paneel niet bereikt. Losstaand van acf/
 * verhalen's eigen accentColor (niet gesynchroniseerd) — zelfde precedent
 * als acf/news-hero's/acf/agenda-hero's eigen accentColor t.o.v. hun
 * archiefblock.
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;

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

	addFilter('editor.BlockEdit', 'own-framework/verhaal-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/verhaal-hero') {
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
							help: 'Titel en tekst samen gecentreerd, of links uitgelijnd.',
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
							help: 'Toont Home > ... bovenaan de hero, boven de inhoud. Blijft leeg op de archiefpagina zelf (daar is niks om naar terug te navigeren).',
							checked: !!attrs.showBreadcrumbs,
							onChange: setAttr('showBreadcrumbs'),
						})
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.accentColor || '',
								onChange: function (value) {
									props.setAttributes({ accentColor: resolveToPresetVar(value) });
								},
								label: 'Accentkleur (filterbalk)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
