/**
 * "Telefoonnummer" and "E-mailadres" as plain block attributes (phone/email,
 * see block.json), not SCF fields — same reasoning as blocks/header/editor.js:
 * they belong with this block's other native settings (align, color) rather
 * than the SCF fields modal. block.json's "editorScript" enqueues this
 * automatically, only when acf/header-topbar is in use.
 *
 * Plain wp.element.createElement (no JSX) so this ships as-is, no Vite/babel
 * build step required.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;

	addFilter(
		'editor.BlockEdit',
		'own-framework/header-topbar-settings',
		function (BlockEdit) {
			return function (props) {
				if (props.name !== 'acf/header-topbar') {
					return el(BlockEdit, props);
				}

				return el(
					Fragment,
					null,
					el(BlockEdit, props),
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: 'Contactgegevens', initialOpen: true },
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Telefoonnummer',
								help: 'Verschijnt links in de topbalk. Leeg laten om te verbergen.',
								value: props.attributes.phone || '',
								onChange: function (value) {
									props.setAttributes({ phone: value });
								},
							}),
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'E-mailadres',
								help: 'Verschijnt links in de topbalk. Leeg laten om te verbergen.',
								value: props.attributes.email || '',
								onChange: function (value) {
									props.setAttributes({ email: value });
								},
							})
						)
					)
				);
			};
		}
	);
})(window.wp);
