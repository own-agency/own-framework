/**
 * "Telefoonnummer" and "E-mailadres" as plain block attributes (phone/email,
 * see block.json), not SCF fields — same reasoning as blocks/header/editor.js:
 * they belong with this block's other native settings (align, color) rather
 * than the SCF fields modal. block.json's "editorScript" enqueues this
 * automatically, only when acf/header-topbar is in use.
 *
 * "showLanguageSwitcher" ("Toon taalkiezer") renders the GTranslate plugin's
 * own "dropdown" widget at the end of the topbar's own right-hand side (menu,
 * if any, then the switcher) — see render.php's own docblock for why this
 * lives here (the topbar) rather than in acf/header's main row, and why it's
 * a plain toggle rather than an actual "gtranslate/language-switcher" block
 * placed via InnerBlocks.
 *
 * "align" ("Breedte inhoud" below) is a plain attribute + custom
 * SelectControl too, not the native toolbar align control (block.json's
 * "supports.align" is false) — see blocks/header/editor.js's own docblock
 * for exactly why the native control can't work here (this block is the
 * same kind of bare, unwrapped template-part-level block).
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
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken.
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

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
							el(VStack, { spacing: 4 },
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
						),
						el(
							PanelBody,
							{ title: 'Taalkiezer', initialOpen: true },
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Toon taalkiezer (GTranslate)',
								help: 'Toont de GTranslate-taalkiezer aan het einde van de topbalk (alleen zichtbaar als de GTranslate-plugin actief is).',
								checked: !!props.attributes.showLanguageSwitcher,
								onChange: function (value) {
									props.setAttributes({ showLanguageSwitcher: value });
								},
							})
						),
						el(
							PanelBody,
							{ title: 'Breedte', initialOpen: true },
							el(SelectControl, {
								label: 'Breedte inhoud',
								help: 'De achtergrondkleur loopt altijd tot de randen van het scherm — dit bepaalt alleen de breedte van de contactgegevens/het menu erbinnen.',
								value: props.attributes.align || 'wide',
								options: [
									{ label: 'Smal', value: '' },
									{ label: 'Normaal', value: 'wide' },
									{ label: 'Breed', value: 'full' },
								],
								onChange: function (value) {
									props.setAttributes({ align: value });
								},
							})
						)
					)
				);
			};
		}
	);
})(window.wp);
