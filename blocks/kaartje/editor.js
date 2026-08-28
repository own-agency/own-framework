/**
 * "imageFullWidth" is een plain block attribute (zie block.json) die de
 * genest core/image-InnerBlock tot de zijkanten van de kaart laat lopen —
 * zie render.php's eigen docblock voor de volle CSS-aanpak
 * (src/css/components/kaartje.css). Titel/subtekst/tekst/knoppen zijn
 * InnerBlocks (render.php's letterlijke "<InnerBlocks />"-string) en krijgen
 * hun eigen instellingen (tekstkleur, uitlijning, …) al via hun eigen native
 * controls — daar is geen paneel hier voor nodig.
 *
 * "Kleur"-paneel (PanelColorSettings) voor de achtergrond, in de "Blok"-tab
 * — het native "Kleuren"-paneel (supports.color, block.json) staat in de
 * aparte "Stijlen"-tab, dus zonder dit eigen paneel is de achtergrondkleur
 * wel instelbaar maar niet te vinden naast dit block's overige
 * instellingen. supports.color.background/gradients staan daarom in
 * block.json op false: dit paneel is de vervanging, niet een aanvulling.
 * Zelfde patroon, zelfde resolveToPresetVar()-aanpak als acf/content-blok's
 * eigen editor.js (zie dat bestand's eigen docblock voor de volle uitleg).
 *
 * Plain wp.element.createElement (geen JSX) zodat dit zonder Vite/babel-
 * build-stap werkt, zelfde aanpak als de andere blocks in deze theme.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;

	// Resolves a raw swatch colour back to its theme.json slug, zelfde reden
	// als acf/content-blok's eigen resolveToPresetVar: de attribute blijft
	// dynamisch als een klant de kleur later via Ontwerp aanpast, i.p.v. een
	// vaste hexwaarde.
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

	addFilter('editor.BlockEdit', 'own-framework/kaartje-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/kaartje') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Afbeelding', initialOpen: true },
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Afbeelding over volle breedte',
							help: 'De afbeelding loopt tot de zijkanten van de kaart, zonder padding.',
							checked: !!attrs.imageFullWidth,
							onChange: function (value) {
								props.setAttributes({ imageFullWidth: value });
							},
						})
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.backgroundColor || '',
								onChange: function (value) {
									props.setAttributes({ backgroundColor: resolveToPresetVar(value) });
								},
								label: 'Achtergrond',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
