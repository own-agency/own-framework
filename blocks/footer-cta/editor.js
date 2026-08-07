(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;

	addFilter('editor.BlockEdit', 'own-framework/footer-cta-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/footer-cta') {
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
					el(PanelBody, { title: 'Inhoud', initialOpen: true },
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'CTA-subtitel',
							help: 'Klein tekstje boven de CTA-titel, bv. "Hoofdwebsite van het".',
							value: attrs.ctaSubtitle || '',
							onChange: setAttr('ctaSubtitle'),
						}),
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'CTA-titel',
							value: attrs.ctaTitle || '',
							onChange: setAttr('ctaTitle'),
						}),
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							type: 'url',
							label: 'CTA-link',
							help: 'Titel en link moeten allebei gevuld zijn voordat de kaart op de live site verschijnt.',
							value: attrs.ctaUrl || '',
							onChange: setAttr('ctaUrl'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Open in nieuw tabblad',
							help: 'Bepaalt ook het icoon op de kaart: een pijl (blijft hier) of een externe-link-icoon (nieuw tabblad).',
							checked: !!attrs.ctaOpenInNewTab,
							onChange: setAttr('ctaOpenInNewTab'),
						})
					)
				)
			);
		};
	});
})(window.wp);
