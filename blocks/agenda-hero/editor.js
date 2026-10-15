/**
 * "Layout" (height/uitlijning/breadcrumbs) — the hero's own background/text
 * colour use WordPress' native "Kleuren" panel (block.json's "supports.
 * color"), so unlike acf/agenda (whose badges/checkboxes/kaarten are
 * sub-elements the native panel can't reach — CLAUDE.md Traps #7) there's no
 * custom colour panel needed for THOSE here; a single colourable zone is
 * fully covered by the native control. Same "Layout" panel shape as acf/
 * vacature-hero's own editor.js, minus its "Zoekknop" panel (this hero has
 * no search field).
 *
 * "accentColor" IS its own plain attribute + panel though, same Traps #7
 * reasoning as acf/agenda's own accentColor: the filter-bar dropdown
 * triggers/badges (parts/filter-bar.php) are sub-elements the native panel
 * can't reach. Deliberately a SEPARATE attribute from acf/agenda's own
 * accentColor (not synced) — same "independent per-block colour attribute"
 * precedent as acf/vacature-hero's searchButtonColor vs. acf/vacatures' own
 * accentColor.
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
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken.
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

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

	addFilter('editor.BlockEdit', 'own-framework/agenda-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/agenda-hero') {
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
						el(VStack, { spacing: 4 },
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
								help: 'Links: op de linkerrand van de inhoud, gelijk met de resultaten eronder. Gecentreerd: titel en tekst in het midden.',
								value: attrs.contentAlign || 'left',
								options: [
									{ label: 'Links', value: 'left' },
									{ label: 'Gecentreerd', value: 'center' },
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
								label: 'Accentkleur (teller en vinkjes)',
							},
							{
								value: attrs.filterButtonColor || '',
								onChange: function (value) {
									props.setAttributes({ filterButtonColor: resolveToPresetVar(value) });
								},
								label: 'Filterknop achtergrond',
							},
							{
								value: attrs.filterButtonTextColor || '',
								onChange: function (value) {
									props.setAttributes({ filterButtonTextColor: resolveToPresetVar(value) });
								},
								label: 'Filterknop tekst',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
