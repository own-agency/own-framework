/**
 * Instellingen voor acf/zoekbalk: teksten, formaat en kleuren. Een lege
 * kleur valt terug op de standaard (zoekbalk.css); voor de knop is dat de
 * site-brede Knop-stijl uit Ontwerp. Kleurvelden via de native
 * PanelColorSettings (Thema/Aangepast-swatches + vrije kleurkiezer).
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
	var TextControl = wp.components.TextControl;
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

	addFilter('editor.BlockEdit', 'own-framework/zoekbalk-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/zoekbalk') {
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
					el(PanelBody, { title: 'Zoekbalk', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Placeholdertekst',
								value: attrs.placeholder || '',
								onChange: setAttr('placeholder'),
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Knop tonen',
								checked: !!attrs.showButton,
								onChange: setAttr('showButton'),
							}),
							attrs.showButton && el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Knoptekst',
								value: attrs.buttonText || '',
								onChange: setAttr('buttonText'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Formaat',
								value: attrs.size || 'normal',
								options: [
									{ label: 'Normaal', value: 'normal' },
									{ label: 'Groot', value: 'large' },
								],
								onChange: setAttr('size'),
							})
						)
					),
					el(PanelColorSettings, {
						title: 'Kleuren',
						colorSettings: [
							colorSetting('Achtergrond veld', 'fieldBackgroundColor'),
							colorSetting('Rand veld', 'fieldBorderColor'),
							colorSetting('Tekst en icoon', 'fieldTextColor'),
						].concat(attrs.showButton ? [
							colorSetting('Achtergrond knop', 'buttonBackgroundColor'),
							colorSetting('Tekst knop', 'buttonTextColor'),
						] : []),
					})
				)
			);
		};
	});
})(window.wp);
