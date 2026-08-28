/**
 * "Titel", "sliderVersion" (default/thumbs), paginatie-toggle en de
 * kleurkeuzes als plain block attributes (zie block.json), zelfde reden als
 * acf/header's eigen editor.js-comment: puur visuele/inhoudelijke keuzes
 * zonder losse SCF-velden nodig. block.json's "editorScript" enqueue't dit
 * bestand automatisch, alleen wanneer acf/afbeeldingen-slider daadwerkelijk
 * gebruikt wordt.
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
	var FocalPointPicker = wp.components.FocalPointPicker;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken.
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

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

	function removeImageAt(images, index) {
		var next = images.slice();
		next.splice(index, 1);
		return next;
	}

	addFilter('editor.BlockEdit', 'own-framework/afbeeldingen-slider-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/afbeeldingen-slider') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var images = attrs.images || [];
			var sliderVersion = attrs.sliderVersion || 'default';
			var isThumbs = sliderVersion === 'thumbs';

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			var paginationColorControl = !isThumbs && attrs.showPagination
				? el(PanelColorSettings, {
						title: 'Kleur paginatie',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.paginationColor || '',
								onChange: function (value) {
									props.setAttributes({ paginationColor: resolveToPresetVar(value) });
								},
								label: 'Actieve bolletje',
							},
						],
				  })
				: null;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Inhoud', initialOpen: true },
						el(VStack, { spacing: 4 },
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Titel',
								value: attrs.title || '',
								onChange: setAttr('title'),
							}),
							el(SelectControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Type slider',
								help: isThumbs
									? 'Toont een miniaturenstrook onder de hoofdafbeelding, die de navigatie overneemt van de paginatie-bolletjes.'
									: 'Meerdere afbeeldingen naast elkaar, met optioneel paginatie-bolletjes.',
								value: sliderVersion,
								options: [
									{ label: 'Standaard', value: 'default' },
									{ label: 'Met miniaturen', value: 'thumbs' },
								],
								onChange: setAttr('sliderVersion'),
							}),
							!isThumbs && el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Paginatie-bolletjes tonen',
								help: 'Toont klikbare bolletjes onder de slider, één per afbeelding.',
								checked: !!attrs.showPagination,
								onChange: setAttr('showPagination'),
							}),
							el('div', { className: 'own-image-slider-editor-images' },
								el(VStack, { spacing: 4 },
									images.length > 0
										// Eén FocalPointPicker per afbeelding i.p.v. een los statisch
										// thumbnail-grid — zelfde reden/patroon als acf/hero's eigen
										// sliderImages-lijst: de picker toont de afbeelding zelf al.
										? el('div', { style: { display: 'flex', flexDirection: 'column', gap: '12px' } },
											images.map(function (image, index) {
												return el('div', { key: image.id || index, style: { position: 'relative', border: '1px solid #ddd', borderRadius: '2px', padding: '8px' } },
													el(FocalPointPicker, {
														__next40pxDefaultSize: true,
														__nextHasNoMarginBottom: true,
														label: 'Afbeelding ' + (index + 1),
														url: image.url,
														value: image.position || { x: 0.5, y: 0.5 },
														onChange: function (value) {
															var next = images.slice();
															next[index] = Object.assign({}, next[index], { position: value });
															props.setAttributes({ images: next });
														},
													}),
													el(Button, {
														icon: 'no-alt',
														label: 'Afbeelding verwijderen',
														isSmall: true,
														style: {
															position: 'absolute',
															top: '4px',
															right: '4px',
															minWidth: '20px',
															height: '20px',
															padding: 0,
															background: '#fff',
														},
														onClick: function () {
															props.setAttributes({ images: removeImageAt(images, index) });
														},
													})
												);
											})
										)
										: null,
									el(MediaUploadCheck, null,
										el(MediaUpload, {
											onSelect: function (media) {
												// Behoudt een al gekozen brandpunt voor een afbeelding die in
												// de selectie blijft staan — zie acf/hero's eigen onSelect voor
												// dezelfde reden.
												props.setAttributes({
													images: media.map(function (item) {
														var existing = images.filter(function (image) {
															return image.id === item.id;
														})[0];
														return {
															id: item.id,
															url: item.url,
															alt: item.alt || '',
															position: existing ? existing.position : undefined,
														};
													}),
												});
											},
											allowedTypes: ['image'],
											multiple: true,
											gallery: true,
											value: images.map(function (image) { return image.id; }),
											render: function (obj) {
												return el(Button, { variant: 'secondary', onClick: obj.open },
													images.length > 0 ? 'Afbeeldingen wijzigen' : 'Afbeeldingen kiezen'
												);
											},
										})
									)
								)
							)
						)
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur navigatieknoppen',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.navColor || '',
								onChange: function (value) {
									props.setAttributes({ navColor: resolveToPresetVar(value) });
								},
								label: 'Vorige / volgende knoppen',
							},
						],
					}),
					paginationColorControl
				)
			);
		};
	});
})(window.wp);
