/**
 * "Layout" (uitlijning/broodkruimelpad) en "Kleuren" (broodkruimelpad/
 * titel/datum/uittreksel/badges/quote — elk een eigen attribute, zie
 * render.php's docblock for why) — structureel een kopie van acf/vacature-single-hero/
 * acf/news-single-hero's eigen editor.js. De hero's eigen BACKGROUND blijft
 * de native "Kleuren"-paneel (block.json's "supports.color.background") —
 * alleen "text" staat uit, vervangen door de losse attributes hieronder.
 *
 * Geen "Verticale uitlijning"-veld (meer) — die bepaalde hoe de tekstkolom
 * t.o.v. de foto uitlijnde toen beide nog in één gedeelde flex-rij stonden;
 * de foto staat nu in een eigen, overlappende rij ERONDER (render.php's
 * eigen docblock), dus dat concept bestaat niet meer.
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

	addFilter('editor.BlockEdit', 'own-framework/verhaal-single-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/verhaal-single-hero') {
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

			function setColorAttr(name) {
				return function (value) {
					var next = {};
					next[name] = resolveToPresetVar(value);
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
							label: 'Uitlijning inhoud',
							help: 'Broodkruimelpad, titel en uittreksel links uitgelijnd, of gecentreerd (alleen zichtbaar effect zonder foto).',
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
							checked: attrs.showBreadcrumbs !== false,
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
								value: attrs.breadcrumbColor || '',
								onChange: setColorAttr('breadcrumbColor'),
								label: 'Broodkruimelpad',
							},
							{
								value: attrs.titleColor || '',
								onChange: setColorAttr('titleColor'),
								label: 'Titel',
							},
							{
								value: attrs.dateColor || '',
								onChange: setColorAttr('dateColor'),
								label: 'Publicatiedatum',
							},
							{
								value: attrs.descriptionColor || '',
								onChange: setColorAttr('descriptionColor'),
								label: 'Uittreksel',
							},
							{
								value: attrs.badgeAccentColor || '',
								onChange: setColorAttr('badgeAccentColor'),
								label: 'Categorie-badges (accentkleur)',
							},
							{
								value: attrs.quoteColor || '',
								onChange: setColorAttr('quoteColor'),
								label: 'Quote (alleen zichtbaar zonder foto)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
