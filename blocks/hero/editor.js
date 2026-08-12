/**
 * "mediaType" (none/image/slider/video) en "imageLayout" (background/side,
 * alleen bij mediaType "image" of "none") zijn losse velden i.p.v. één
 * samengevoegde keuze — zie render.php's docblock voor waarom. Plain block attributes
 * (zie block.json), geen SCF-velden: de inhoud zelf (kop/tekst/knoppen) is
 * InnerBlocks (render.php's letterlijke "<InnerBlocks />"-string), en wat
 * overblijft (achtergrond-configuratie) is simpel genoeg voor dit theme's
 * gewone patroon van attributes + editor.js — zelfde aanpak als acf/footer-
 * cta, acf/header, acf/afbeeldingen-slider. block.json's "editorScript"
 * enqueue't dit bestand automatisch, alleen wanneer acf/hero daadwerkelijk
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

	function removeImageAt(images, index) {
		var next = images.slice();
		next.splice(index, 1);
		return next;
	}

	// Eén afbeelding kiezen (backgroundImage/posterImage) — MediaUpload zonder
	// "multiple"/"gallery", onSelect krijgt dan één media-object i.p.v. een
	// array (ander contract dan de gallery-picker hieronder).
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

	addFilter('editor.BlockEdit', 'own-framework/hero-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/hero') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var mediaType = attrs.mediaType || 'none';
			var imageLayout = attrs.imageLayout || 'background';
			var sliderImages = attrs.sliderImages || [];
			var isSideLayout = (mediaType === 'image' || mediaType === 'none') && imageLayout === 'side';

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			// Alleen relevant zolang er ook daadwerkelijk volle-breedte media is
			// (zie render.php's $has_media_background) — bij mediaType "none" is
			// er nooit media om te overlayen, dus die krijgt deze control nooit.
			var needsOverlay = mediaType !== 'none' && !isSideLayout;

			var mediaFields = null;
			if (mediaType === 'none') {
				mediaFields = el(Fragment, null,
					el('p', { style: { marginTop: 0, marginBottom: '8px', fontSize: '12px', fontStyle: 'italic' } },
						'Kleur of verloop stel je in via het "Stijlen"-paneel in de zijbalk.'
					),
					el(SelectControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Weergave',
						help: isSideLayout
							? 'Toont een afbeelding naast de inhoud — optioneel, zonder gekozen afbeelding blijft dat vlak leeg.'
							: 'Alleen de inhoud, over de gekozen kleur of het verloop.',
						value: imageLayout,
						options: [
							{ label: 'Geen afbeelding', value: 'background' },
							{ label: 'Afbeelding naast de inhoud', value: 'side' },
						],
						onChange: setAttr('imageLayout'),
					}),
					isSideLayout
						? el(Fragment, null,
							singleImagePicker(
								attrs.backgroundImage,
								setAttr('backgroundImage'),
								'Afbeelding kiezen',
								'Afbeelding wijzigen'
							),
							el(SelectControl, {
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
						  )
						: null
				);
			} else if (mediaType === 'image') {
				mediaFields = el(Fragment, null,
					el(SelectControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Weergave',
						help: isSideLayout
							? 'Toont de gekozen afbeelding naast de inhoud.'
							: 'Toont de gekozen afbeelding over de volle breedte, met de inhoud erboven.',
						value: imageLayout,
						options: [
							{ label: 'Achtergrond', value: 'background' },
							{ label: 'Naast de inhoud', value: 'side' },
						],
						onChange: setAttr('imageLayout'),
					}),
					singleImagePicker(
						attrs.backgroundImage,
						setAttr('backgroundImage'),
						'Afbeelding kiezen',
						'Afbeelding wijzigen'
					),
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
						: null
				);
			} else if (mediaType === 'slider') {
				mediaFields = el('div', { style: { marginTop: '8px' } },
					sliderImages.length > 0
						? el('div', {
								style: {
									display: 'grid',
									gridTemplateColumns: 'repeat(3, 1fr)',
									gap: '8px',
									marginBottom: '12px',
								},
							},
							sliderImages.map(function (image, index) {
								return el('div', { key: image.id || index, style: { position: 'relative' } },
									el('img', {
										src: image.url,
										alt: image.alt || '',
										style: { width: '100%', height: '64px', objectFit: 'cover', borderRadius: '2px' },
									}),
									el(Button, {
										icon: 'no-alt',
										label: 'Afbeelding verwijderen',
										isSmall: true,
										style: {
											position: 'absolute',
											top: '2px',
											right: '2px',
											minWidth: '20px',
											height: '20px',
											padding: 0,
											background: '#fff',
										},
										onClick: function () {
											props.setAttributes({ sliderImages: removeImageAt(sliderImages, index) });
										},
									})
								);
							})
						)
						: null,
					el(MediaUploadCheck, null,
						el(MediaUpload, {
							onSelect: function (media) {
								props.setAttributes({
									sliderImages: media.map(function (item) {
										return { id: item.id, url: item.url, alt: item.alt || '' };
									}),
								});
							},
							allowedTypes: ['image'],
							multiple: true,
							gallery: true,
							value: sliderImages.map(function (image) { return image.id; }),
							render: function (obj) {
								return el(Button, { variant: 'secondary', onClick: obj.open },
									sliderImages.length > 0 ? 'Afbeeldingen wijzigen' : 'Afbeeldingen kiezen'
								);
							},
						})
					),
					el(SelectControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Overgang',
						value: attrs.sliderEffect || 'slide',
						options: [
							{ label: 'Schuiven', value: 'slide' },
							{ label: 'Vervagen', value: 'fade' },
						],
						onChange: setAttr('sliderEffect'),
					}),
					el(ToggleControl, {
						__nextHasNoMarginBottom: true,
						label: 'Paginatie-bolletjes tonen',
						help: 'Toont klikbare bolletjes onderin de hero, één per afbeelding.',
						checked: !!attrs.showPagination,
						onChange: setAttr('showPagination'),
					})
				);
			} else if (mediaType === 'video') {
				mediaFields = el(Fragment, null,
					el(MediaUploadCheck, null,
						el(MediaUpload, {
							onSelect: function (media) {
								props.setAttributes({ videoId: media.id, videoUrl: media.url });
							},
							allowedTypes: ['video'],
							value: attrs.videoId,
							render: function (obj) {
								return el('div', { style: { marginBottom: '12px' } },
									el(Button, { variant: 'secondary', onClick: obj.open },
										attrs.videoUrl ? 'Video wijzigen' : 'Video kiezen'
									)
								);
							},
						})
					),
					el('p', { style: { marginTop: 0, marginBottom: '8px', fontSize: '12px' } }, 'Poster (toont terwijl de video laadt):'),
					singleImagePicker(
						attrs.posterImage,
						setAttr('posterImage'),
						'Poster kiezen',
						'Poster wijzigen'
					)
				);
			}

			var paginationColorControl = mediaType === 'slider' && attrs.showPagination
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
					el(PanelBody, { title: 'Achtergrond', initialOpen: true },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Type media',
							help: 'Bepaalt wat er achter (of naast) de inhoud staat.',
							value: mediaType,
							options: [
								{ label: 'Geen — alleen kleur of verloop', value: 'none' },
								{ label: 'Afbeelding', value: 'image' },
								{ label: 'Meerdere afbeeldingen (slideshow)', value: 'slider' },
								{ label: 'Video', value: 'video' },
							],
							onChange: setAttr('mediaType'),
						}),
						mediaFields,
						needsOverlay
							? el(RangeControl, {
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
									label: 'Overlay-donkerheid',
									help: 'Kleur van de overlay komt van dit block’s eigen "Achtergrondkleur" (Kleuren-paneel). Heeft geen effect zolang er geen afbeelding/video/slideshow gekozen is.',
									value: typeof attrs.overlayOpacity === 'number' ? attrs.overlayOpacity : 40,
									onChange: setAttr('overlayOpacity'),
									min: 0,
									max: 100,
							  })
							: null
					),
					paginationColorControl,
					el(PanelBody, { title: 'Layout', initialOpen: false },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Hoogte',
							help: '"Standaard" bepaalt de hoogte aan de hand van de inhoud, de rest garandeert een minimumhoogte ook bij weinig inhoud.',
							value: attrs.height || 'auto',
							options: [
								{ label: 'Standaard (op basis van inhoud)', value: 'auto' },
								{ label: 'Middel', value: 'medium' },
								{ label: 'Groot', value: 'large' },
								{ label: 'Volledig scherm', value: 'screen' },
							],
							onChange: setAttr('height'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Broodkruimelpad tonen',
							help: 'Toont Home > ... bovenaan de hero, boven de inhoud. Blijft leeg op de voorpagina zelf (daar is niks om naar terug te navigeren).',
							checked: !!attrs.showBreadcrumbs,
							onChange: setAttr('showBreadcrumbs'),
						})
					)
				)
			);
		};
	});
})(window.wp);
