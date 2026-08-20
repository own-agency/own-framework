/**
 * "Layout" (uitlijning/broodkruimelpad) and "Kleuren" (broodkruimelpad/
 * titel/omschrijving/categorie-badges/datumbadge — elk een eigen attribute,
 * zie render.php's docblock) — identieke opzet als blocks/vacature-
 * single-hero/editor.js.
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

	addFilter('editor.BlockEdit', 'own-framework/agenda-single-hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/agenda-single-hero') {
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
							help: 'Broodkruimelpad, titel, omschrijving en badges links uitgelijnd, of gecentreerd.',
							value: attrs.contentAlign || 'left',
							options: [
								{ label: 'Links', value: 'left' },
								{ label: 'Gecentreerd', value: 'center' },
							],
							onChange: setAttr('contentAlign'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Verticale uitlijning',
							help: 'Alleen zichtbaar effect als er een uitgelichte afbeelding is ingesteld — bepaalt of de tekst boven-, midden- of onderaan de afbeelding uitlijnt.',
							value: attrs.contentVerticalAlign || 'top',
							options: [
								{ label: 'Bovenaan', value: 'top' },
								{ label: 'Gecentreerd', value: 'center' },
								{ label: 'Onderaan', value: 'bottom' },
							],
							onChange: setAttr('contentVerticalAlign'),
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
								value: attrs.descriptionColor || '',
								onChange: setColorAttr('descriptionColor'),
								label: 'Omschrijving',
							},
							{
								value: attrs.badgeAccentColor || '',
								onChange: setColorAttr('badgeAccentColor'),
								label: 'Categorie-badges (accentkleur)',
							},
							{
								value: attrs.dateBadgeColor || '',
								onChange: setColorAttr('dateBadgeColor'),
								label: 'Datumbadge (los van de accentkleur, leeg = zelfde kleur)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
