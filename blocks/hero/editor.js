/**
 * "mediaType" (none/image/slider/video) en "imageLayout" (background/side/logos,
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
	var FocalPointPicker = wp.components.FocalPointPicker;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;
	// Native spacing-primitief — bepaalt de ruimte tussen settings die naast
	// elkaar in één paneel staan. Elk los control zet via
	// __nextHasNoMarginBottom al zijn eigen marge uit, dus zonder dit zouden
	// ze tegen elkaar aan plakken. Overal in dit bestand dezelfde
	// spacing={4}, i.p.v. de losse marginTop/marginBottom-waarden per veld
	// die dit vroeger deden.
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
						el('div', { style: { display: 'flex', gap: '8px' } },
							el(Button, { variant: 'secondary', onClick: obj.open },
								value && value.url ? changeLabel : chooseLabel
							),
							value && value.url
								? el(Button, {
										variant: 'tertiary',
										isDestructive: true,
										onClick: function () {
											onSelect({});
										},
								  }, 'Verwijderen')
								: null
						)
					);
				},
			})
		);
	}

	// Uitlijning van backgroundImage — zelfde FocalPointPicker als core/cover
	// gebruikt voor zijn achtergrondafbeelding, hier ook zinvol omdat
	// backgroundImage altijd met object-cover rendert (render.php's
	// $background_image_position). Alleen zichtbaar zodra er een afbeelding
	// gekozen is; zonder afbeelding is er niets om uit te lijnen.
	function backgroundImageFocalPointPicker(attrs, setAttr) {
		if (!attrs.backgroundImage || !attrs.backgroundImage.url) {
			return null;
		}
		return el(FocalPointPicker, {
			__next40pxDefaultSize: true,
			__nextHasNoMarginBottom: true,
			label: 'Uitlijning afbeelding',
			url: attrs.backgroundImage.url,
			value: attrs.backgroundImagePosition || { x: 0.5, y: 0.5 },
			onChange: setAttr('backgroundImagePosition'),
		});
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

			// Eén SelectControl-vorm voor alle keuzelijstjes hieronder.
			function logoSelect(name, label, fallback, options, help) {
				return el(SelectControl, {
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
					label: label,
					help: help,
					value: attrs[name] || fallback,
					options: options,
					onChange: setAttr(name),
				});
			}

			// Pas zichtbaar zodra er logo's gekozen zijn: zonder logo's is er
			// niets om te plaatsen of te schalen.
			function logoSettings() {
				var isColumn = attrs.logoArrangement === 'column';
				return el(VStack, { spacing: 4 },
					logoSelect('logoPosition', 'Positie', 'right', [
						{ label: 'Rechts van de inhoud', value: 'right' },
						{ label: 'Links van de inhoud', value: 'left' },
					], 'Op mobiel staan de logo’s altijd onder de inhoud.'),
					logoSelect('logoColumns', 'Verhouding inhoud / logo’s', 'equal', [
						{ label: 'Gelijk (½ – ½)', value: 'equal' },
						{ label: 'Meer ruimte voor inhoud (⅗ – ⅖)', value: 'content' },
						{ label: 'Smalle logokolom (⅔ – ⅓)', value: 'logos-narrow' },
					]),
					el(RangeControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Hoogte logo’s (px)',
						help: 'Breedte volgt de verhouding van het logo. Op mobiel 70% van deze hoogte.',
						value: typeof attrs.logoHeight === 'number' ? attrs.logoHeight : 96,
						onChange: setAttr('logoHeight'),
						min: 24,
						max: 240,
						step: 4,
					}),
					logoSelect('logoArrangement', 'Rangschikking', 'row', [
						{ label: 'Naast elkaar', value: 'row' },
						{ label: 'Onder elkaar', value: 'column' },
					]),
					logoSelect('logoGap', 'Tussenruimte', 'medium', [
						{ label: 'Klein', value: 'small' },
						{ label: 'Middel', value: 'medium' },
						{ label: 'Groot', value: 'large' },
					]),
					logoSelect('logoAlign', 'Horizontale uitlijning', 'end', [
						{ label: 'Links', value: 'start' },
						{ label: 'Midden', value: 'center' },
						{ label: 'Rechts', value: 'end' },
					], isColumn
						? 'Binnen de logokolom, vanaf desktop.'
						: 'Binnen de logokolom, vanaf desktop. Op mobiel lijnen de logo’s links uit, net als de tekst.'),
					logoSelect('logoVerticalAlign', 'Verticale uitlijning', 'center', [
						{ label: 'Boven', value: 'start' },
						{ label: 'Midden', value: 'center' },
						{ label: 'Onder', value: 'end' },
					], 'Ten opzichte van de inhoud ernaast.'),
					logoSelect('logoColor', 'Kleur', 'original', [
						{ label: 'Origineel', value: 'original' },
						{ label: 'Grijstinten', value: 'grayscale' },
						{ label: 'Wit', value: 'white' },
						{ label: 'Zwart', value: 'black' },
					], 'Wit en zwart maken het hele logo één kleur — handig op een donkere of drukke achtergrond.'),
					el(ToggleControl, {
						__nextHasNoMarginBottom: true,
						label: 'Verbergen op mobiel',
						checked: !!attrs.logoHideOnMobile,
						onChange: setAttr('logoHideOnMobile'),
					})
				);
			}

			// Logo's naast de inhoud (imageLayout "logos", alleen bij mediaType
			// "none") — zelfde galerij-picker + 3-koloms preview als acf/logo-slider.
			var logos = attrs.logos || [];
			var logoFields = el(VStack, { spacing: 4 },
				logos.length > 0
					? el('div', { style: { display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px' } },
						logos.map(function (logo, index) {
							return el('div', {
								key: logo.id || index,
								style: {
									position: 'relative',
									background: '#f0f0f0',
									borderRadius: '2px',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									height: '64px',
								},
							},
								el('img', {
									src: logo.url,
									alt: logo.alt || '',
									style: { maxWidth: '100%', maxHeight: '100%', objectFit: 'contain' },
								}),
								el(Button, {
									icon: 'no-alt',
									label: 'Logo verwijderen',
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
										props.setAttributes({ logos: removeImageAt(logos, index) });
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
								logos: media.map(function (item) {
									return { id: item.id, url: item.url, alt: item.alt || '' };
								}),
							});
						},
						allowedTypes: ['image'],
						multiple: true,
						gallery: true,
						value: logos.map(function (logo) { return logo.id; }),
						render: function (obj) {
							return el(Button, { variant: 'secondary', onClick: obj.open },
								logos.length > 0 ? "Logo's wijzigen" : "Logo's kiezen"
							);
						},
					})
				),
				logos.length > 0 ? logoSettings() : null
			);

			var mediaFields = null;
			if (mediaType === 'none') {
				mediaFields = el(VStack, { spacing: 4 },
					el('p', { style: { fontSize: '12px', fontStyle: 'italic' } },
						'Kleur of verloop stel je in via het "Stijlen"-paneel in de zijbalk.'
					),
					el(SelectControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Weergave',
						help: isSideLayout
							? 'Toont een afbeelding naast de inhoud — optioneel, zonder gekozen afbeelding blijft dat vlak leeg.'
							: imageLayout === 'logos'
								? "Toont één of meer logo's naast de inhoud, bijvoorbeeld van een partner of keurmerk."
								: 'Alleen de inhoud, over de gekozen kleur of het verloop.',
						value: imageLayout,
						options: [
							{ label: 'Alleen inhoud', value: 'background' },
							{ label: 'Afbeelding naast de inhoud', value: 'side' },
							{ label: "Logo's naast de inhoud", value: 'logos' },
						],
						onChange: setAttr('imageLayout'),
					}),
					isSideLayout
						? el(VStack, { spacing: 4 },
							singleImagePicker(
								attrs.backgroundImage,
								setAttr('backgroundImage'),
								'Afbeelding kiezen',
								'Afbeelding wijzigen'
							),
							backgroundImageFocalPointPicker(attrs, setAttr),
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
						: null,
					imageLayout === 'logos' ? logoFields : null
				);
			} else if (mediaType === 'image') {
				mediaFields = el(VStack, { spacing: 4 },
					el(SelectControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Weergave',
						help: isSideLayout
							? 'Toont de gekozen afbeelding naast de inhoud.'
							: 'Toont de gekozen afbeelding over de volle breedte, met de inhoud erboven.',
						value: isSideLayout ? 'side' : 'background',
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
					backgroundImageFocalPointPicker(attrs, setAttr),
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
				mediaFields = el(VStack, { spacing: 4 },
					sliderImages.length > 0
						// Eén FocalPointPicker per slide i.p.v. een los statisch
						// thumbnail-grid — de picker toont de afbeelding zelf al, dus
						// een aparte preview ernaast zou dubbelop zijn. Daarom een
						// verticale lijst i.p.v. het compacte 3-koloms grid dat
						// singleImagePicker's eigen thumbnail wél gebruikt.
						? el('div', { style: { display: 'flex', flexDirection: 'column', gap: '12px' } },
							sliderImages.map(function (image, index) {
								return el('div', { key: image.id || index, style: { position: 'relative', border: '1px solid #ddd', borderRadius: '2px', padding: '8px' } },
									el(FocalPointPicker, {
										__next40pxDefaultSize: true,
										__nextHasNoMarginBottom: true,
										label: 'Afbeelding ' + (index + 1),
										url: image.url,
										value: image.position || { x: 0.5, y: 0.5 },
										onChange: function (value) {
											var next = sliderImages.slice();
											next[index] = Object.assign({}, next[index], { position: value });
											props.setAttributes({ sliderImages: next });
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
								// Behoudt een al gekozen brandpunt voor een afbeelding die
								// in de selectie blijft staan — zonder dit reset opnieuw
								// openen van de galerij (ook bij een selectie die verder
								// ongewijzigd is) alle uitlijning terug naar het midden.
								props.setAttributes({
									sliderImages: media.map(function (item) {
										var existing = sliderImages.filter(function (image) {
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
				mediaFields = el(VStack, { spacing: 4 },
					el(MediaUploadCheck, null,
						el(MediaUpload, {
							onSelect: function (media) {
								props.setAttributes({ videoId: media.id, videoUrl: media.url });
							},
							allowedTypes: ['video'],
							value: attrs.videoId,
							render: function (obj) {
								return el(Button, { variant: 'secondary', onClick: obj.open },
									attrs.videoUrl ? 'Video wijzigen' : 'Video kiezen'
								);
							},
						})
					),
					el('p', { style: { fontSize: '12px' } }, 'Poster (toont terwijl de video laadt):'),
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

			// Paneelvolgorde: inhoud/layout eerst, kleur altijd als laatste paneel
			// — zelfde conventie als acf/cta ("Layout" → "Afbeelding" → "Kleur
			// kaart") en acf/content-blok ("Layout" → "Afbeelding" → "Kleur"),
			// zodat de kleurkeuze niet tussen twee layout-panelen in verdwaalt.
			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Achtergrond', initialOpen: true },
						el(VStack, { spacing: 4 },
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
						)
					),
					el(PanelBody, { title: 'Layout', initialOpen: false },
						el(VStack, { spacing: 4 },
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
					),
					paginationColorControl
				)
			);
		};
	});
})(window.wp);
