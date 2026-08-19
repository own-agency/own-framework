/**
 * "Layout" (uitlijning/broodkruimelpad) and "Kleuren" (broodkruimelpad/
 * titel/datum/badges — elk een eigen attribute, zie render.php's docblock
 * for why) live here. Structureel een kopie van acf/vacature-single-hero/
 * editor.js. The hero's own BACKGROUND still uses WordPress' native
 * "Kleuren" panel (block.json's "supports.color.background") — only "text"
 * is off there, replaced by the granular attributes below.
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

	addFilter('editor.BlockEdit', 'own-framework/news-single-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/news-single-hero') {
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
							help: 'Broodkruimelpad, titel, datum en badges links uitgelijnd, of gecentreerd.',
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
								value: attrs.badgeAccentColor || '',
								onChange: setColorAttr('badgeAccentColor'),
								label: 'Categorie-badges (accentkleur)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
