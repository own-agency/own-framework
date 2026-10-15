/**
 * Instellingen voor acf/melding: soort, weergave, icoon, wegklikken en de
 * kleuren. Kleuren lopen via de native PanelColorSettings (zelfde
 * dropdown-veld met Thema/Aangepast-swatches en vrije kleurkiezer als
 * WordPress' eigen "Achtergrond"-paneel). Bij soort "Aangepast" verschijnt
 * daarnaast een eigen "Kleur"-paneel voor de basiskleur. Een lege kleur valt
 * terug op de standaard van de gekozen soort (src/css/components/
 * melding.css), dus "Wissen" betekent "terug naar de soort", niet "geen
 * kleur".
 *
 * Plain wp.element.createElement (geen JSX): werkt zonder build-stap.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

	// Slaat een gekozen paletkleur op als var(--wp--preset--color--<slug>),
	// zodat de melding meekleurt als de kleur later via Ontwerp verandert.
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

	// Omgekeerde richting: het kleurveld toont/vinkt alleen een echte kleur aan.
	function resolveFromPresetVar(value, palette) {
		var match = /^var\(--wp--preset--color--(.+)\)$/.exec(value || '');
		if (!match) {
			return value || '';
		}
		for (var i = 0; i < palette.length; i++) {
			if (palette[i].slug === match[1]) {
				return palette[i].color;
			}
		}
		return value;
	}

	addFilter('editor.BlockEdit', 'own-framework/melding-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/melding') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var settings = wp.data.select('core/block-editor').getSettings();
			var palette = (settings && settings.colors) || [];

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			function colorSetting(label, name) {
				return {
					label: label,
					value: resolveFromPresetVar(attrs[name], palette),
					onChange: function (value) {
						var next = {};
						next[name] = resolveToPresetVar(value);
						props.setAttributes(next);
					},
				};
			}

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Melding', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Soort',
								help: 'Bepaalt de standaardkleuren en het standaardicoon.',
								value: attrs.variant || 'info',
								options: [
									{ label: 'Informatie', value: 'info' },
									{ label: 'Succes', value: 'succes' },
									{ label: 'Waarschuwing', value: 'waarschuwing' },
									{ label: 'Fout', value: 'fout' },
									{ label: 'Aangepast', value: 'aangepast' },
								],
								onChange: setAttr('variant'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Weergave',
								value: attrs.appearance || 'rounded',
								options: [
									{ label: 'Afgeronde kaart', value: 'rounded' },
									{ label: 'Accentlijn links', value: 'accent' },
								],
								onChange: setAttr('appearance'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Icoon',
								value: attrs.icon || 'auto',
								options: [
									{ label: 'Automatisch (bij soort)', value: 'auto' },
									{ label: 'Informatie', value: 'info' },
									{ label: 'Vinkje', value: 'succes' },
									{ label: 'Uitroepteken', value: 'waarschuwing' },
									{ label: 'Kruis', value: 'fout' },
									{ label: 'Geen icoon', value: 'none' },
								],
								onChange: setAttr('icon'),
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Wegklikbaar',
								help: 'Toont een sluitknop rechtsboven.',
								checked: !!attrs.dismissible,
								onChange: setAttr('dismissible'),
							}),
							attrs.dismissible && el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Wegklikken onthouden',
								help: 'Een bezoeker die de melding sluit, ziet hem niet opnieuw — tot de tekst wordt aangepast.',
								checked: !!attrs.rememberDismiss,
								onChange: setAttr('rememberDismiss'),
							})
						)
					),
					attrs.variant === 'aangepast' && el(PanelColorSettings, {
						title: 'Kleur',
						colorSettings: [
							colorSetting('Basiskleur (icoon; achtergrond en tekst worden afgeleid)', 'customColor'),
						],
					}),
					el(PanelColorSettings, {
						title: 'Kleuren',
						colorSettings: [
							colorSetting('Achtergrond', 'noticeBackgroundColor'),
							colorSetting('Icoon en accent', 'noticeAccentColor'),
							colorSetting('Titel', 'noticeTitleColor'),
							colorSetting('Tekst', 'noticeTextColor'),
						],
					})
				)
			);
		};
	});
})(window.wp);
