/**
 * "image" (los attribute, geen SCF-veld) + "imageLayout"/"imagePosition"/
 * "overlayOpacity"/"cardBackgroundColor" als plain block attributes (zie
 * block.json) — kop/tekst/knoppen zijn InnerBlocks (render.php's letterlijke
 * "<InnerBlocks />"-string), die krijgen hun tekstkleur al per element via
 * hun eigen native kleurkeuze (of via de section's eigen native tekstkleur,
 * die gewoon doorcascadeert) — geen apart "Kleur kaart"-veld daarvoor nodig.
 *
 * Eigen "Kleur kaart"-paneel (PanelColorSettings) voor alléén de achtergrond,
 * naast (niet in plaats van) het native "Kleuren"-paneel: dat native paneel
 * kleurt via render.php's get_block_wrapper_attributes() de VOLLE <section>
 * (WordPress' eigen color-support voegt dat daar automatisch aan toe), maar
 * raakt nooit de afgeronde kaart erbinnen. "cardBackgroundColor" + dit eigen
 * paneel is de enige manier om specifiek DIE kaart een andere kleur te geven
 * dan de section eromheen — zelfde patroon als acf/hero's paginatie-/
 * navigatieknoppenkleur, om precies dezelfde reden.
 *
 * block.json's "editorScript" enqueue't dit bestand automatisch, alleen
 * wanneer acf/cta daadwerkelijk gebruikt wordt.
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
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;

	// Resolves a raw swatch colour back to its theme.json slug, zelfde reden
	// als acf/header's resolveToPresetVar: de attribute blijft dynamisch als
	// een klant de kleur later via Ontwerp aanpast, i.p.v. een vaste hexwaarde.
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

	// Eén afbeelding kiezen — zelfde contract als acf/hero's eigen
	// singleImagePicker (MediaUpload zonder "multiple"/"gallery").
	function singleImagePicker(value, onSelect, chooseLabel, changeLabel) {
		return el(MediaUploadCheck, null,
			el(MediaUpload, {
				onSelect: function (media) {
					onSelect({ id: media.id, url: media.url, alt: media.alt || '' });
				},
				allowedTypes: ['image'],
				value: value && value.id,
				render: function (obj) {
					return el('div', { style: { display: 'flex', flexDirection: 'column', gap: '8px' } },
						value && value.url
							? el('img', {
									src: value.url,
									alt: value.alt || '',
									style: { width: '100%', height: '96px', objectFit: 'cover', borderRadius: '2px' },
							  })
							: null,
						el(Button, { variant: 'secondary', onClick: obj.open },
							value && value.url ? changeLabel : chooseLabel
						)
					);
				},
			})
		);
	}

	addFilter('editor.BlockEdit', 'own-framework/cta-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/cta') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var imageLayout = attrs.imageLayout || 'side';
			var isSideLayout = imageLayout === 'side';

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
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Achtergrond over volle breedte',
							help: 'De kleur of afbeelding van de kaart loopt tot de randen van het scherm; kop, tekst en knoppen (en de afbeelding bij "Naast de inhoud") blijven binnen de normale containerbreedte.',
							checked: !!attrs.fullWidth,
							onChange: setAttr('fullWidth'),
						})
					)
				),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Afbeelding', initialOpen: true },
						singleImagePicker(
							attrs.image,
							setAttr('image'),
							'Afbeelding kiezen',
							'Afbeelding wijzigen'
						),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Weergave',
							help: isSideLayout
								? 'Toont de afbeelding naast de inhoud — optioneel, zonder gekozen afbeelding blijft dit gewoon een kleurvak.'
								: 'Gebruikt de afbeelding als achtergrond van de kaart, met een kleurtint erover.',
							value: imageLayout,
							options: [
								{ label: 'Naast de inhoud', value: 'side' },
								{ label: 'Achtergrond', value: 'background' },
							],
							onChange: setAttr('imageLayout'),
						}),
						isSideLayout
							? el(SelectControl, {
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
									label: 'Positie afbeelding',
									value: attrs.imagePosition || 'right',
									options: [
										{ label: 'Rechts', value: 'right' },
										{ label: 'Links', value: 'left' },
									],
									onChange: setAttr('imagePosition'),
							  })
							: el(RangeControl, {
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
									label: 'Overlay-donkerheid',
									help: 'Kleur van de overlay komt van de "Achtergrond" hieronder (paneel "Kleur kaart"). Heeft geen effect zolang er geen afbeelding gekozen is.',
									value: typeof attrs.overlayOpacity === 'number' ? attrs.overlayOpacity : 40,
									onChange: setAttr('overlayOpacity'),
									min: 0,
									max: 100,
							  })
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur kaart',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.cardBackgroundColor || '',
								onChange: function (value) {
									props.setAttributes({ cardBackgroundColor: resolveToPresetVar(value) });
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
