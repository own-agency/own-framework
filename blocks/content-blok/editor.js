/**
 * "image" (los attribute, geen SCF-veld) + "direction"/"imageAspect"/
 * "imagePosition"/"imageFullWidth" als plain block attributes (zie
 * block.json) — kop/tekst/knoppen zijn InnerBlocks (render.php's letterlijke
 * "<InnerBlocks />"-string), die krijgen hun tekstkleur al per element via
 * hun eigen native kleurkeuze.
 *
 * "Kleur"-paneel (PanelColorSettings) voor de achtergrond, in de "Blok"-tab
 * naast Layout/Afbeelding — het native "Kleuren"-paneel (supports.color,
 * block.json) staat in de APARTE "Stijlen"-tab, dus zonder dit eigen paneel
 * is de achtergrondkleur wel instelbaar maar niet te vinden naast de rest van
 * dit block's instellingen. supports.color.background/gradients staan
 * daarom uit in block.json: dit paneel is de vervanging, niet een aanvulling
 * — twee controls voor dezelfde achtergrond zou verwarrend zijn, niet
 * handiger. supports.color.text blijft wel aan (native, Stijlen-tab): elk
 * InnerBlocks-element heeft al zijn eigen tekstkleur-keuze, dus daar is geen
 * duplicaat-risico.
 *
 * block.json's "editorScript" enqueue't dit bestand automatisch, alleen
 * wanneer acf/content-blok daadwerkelijk gebruikt wordt.
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
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;

	// Resolves a raw swatch colour back to its theme.json slug, zelfde reden
	// als acf/cta's/acf/header's eigen resolveToPresetVar: de attribute blijft
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

	// Eén afbeelding kiezen — zelfde contract als acf/cta's/acf/hero's eigen
	// singleImagePicker.
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

	addFilter('editor.BlockEdit', 'own-framework/content-blok-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/content-blok') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var direction = attrs.direction || 'vertical';
			var isHorizontal = direction === 'horizontal';

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
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Richting',
							help: isHorizontal
								? 'Afbeelding naast de inhoud — schakelt zelf terug naar verticaal zodra de kolom waarin dit blok staat te smal wordt.'
								: 'Afbeelding boven de inhoud (of gewoon een kleurvak zonder afbeelding).',
							value: direction,
							options: [
								{ label: 'Verticaal', value: 'vertical' },
								{ label: 'Horizontaal', value: 'horizontal' },
							],
							onChange: setAttr('direction'),
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
							label: 'Beeldverhouding',
							value: attrs.imageAspect || 'square',
							options: [
								{ label: 'Vierkant', value: 'square' },
								{ label: 'Breed (16:9)', value: 'video' },
								{ label: 'Staand', value: 'portrait' },
								{ label: 'Origineel (geen crop)', value: 'auto' },
							],
							onChange: setAttr('imageAspect'),
						}),
						isHorizontal
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
							: el(ToggleControl, {
									__nextHasNoMarginBottom: true,
									label: 'Afbeelding over volle breedte',
									help: 'De afbeelding boven de inhoud loopt tot de randen van dit blok, zonder padding.',
									checked: !!attrs.imageFullWidth,
									onChange: setAttr('imageFullWidth'),
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
