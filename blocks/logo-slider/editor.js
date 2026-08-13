/**
 * "Titel", "Grootte logo's", "Richting", "Grijstinten", "Pauzeren bij hover",
 * "Volledige schermbreedte" en "Snelheid" als plain block attributes (zie
 * block.json) — zelfde reden als acf/feitjes-slider's eigen editor.js: puur
 * visuele/inhoudelijke keuzes zonder losse SCF-velden nodig.
 *
 * Geen navigatie-/paginatie-/autoplay-toggles meer, en geen kleurenpanelen
 * voor knoppen/bolletjes: dit block is sinds render.php's herschrijving een
 * pure CSS-marquee (src/css/components/logo-slider.css) zonder Swiper, dus
 * zonder knoppen of bolletjes om te kleuren — de band loopt altijd door,
 * "Automatisch doorlopen" is niet langer een keuze.
 *
 * "fullWidth" ("Volledige schermbreedte") is dezelfde attribute-naam als
 * acf/cta's "Achtergrond over volle breedte" (zelfde patroon, ander doel —
 * zie render.php's docblock voor het verschil), bewust geen eigen
 * kleurenpaneel: hier komt de achtergrondkleur puur uit het native
 * "Kleuren"-paneel (InspectorControls hoeft daar dus niets voor toe te
 * voegen).
 *
 * De logo's zelf worden hier beheerd via een MediaUpload-galerij (meerdere
 * afbeeldingen tegelijk kiezen, met verwijderknop per thumbnail) — zelfde
 * patroon als acf/afbeeldingen-slider's eigen "images"-veld, niet als losse
 * tekstvelden zoals acf/feitjes-slider's "facts": een logo IS een afbeelding,
 * geen data om in te typen.
 *
 * Plain wp.element.createElement (geen JSX) zodat dit zonder Vite/babel-
 * build-stap werkt, zelfde aanpak als de andere blocks in deze theme.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var Button = wp.components.Button;

	function removeLogoAt(logos, index) {
		var next = logos.slice();
		next.splice(index, 1);
		return next;
	}

	addFilter('editor.BlockEdit', 'own-framework/logo-slider-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/logo-slider') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var logos = attrs.logos || [];

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
							label: 'Titel',
							help: 'Verschijnt boven de band. Leeg laten om te verbergen.',
							value: attrs.title || '',
							onChange: setAttr('title'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: "Grootte logo's",
							help: "Bepaalt de hoogte van elk logo. De breedte schaalt vanzelf mee met de eigen beeldverhouding — logo's worden nooit gecropt.",
							value: attrs.logoSize || 'medium',
							options: [
								{ label: 'Klein', value: 'small' },
								{ label: 'Middel', value: 'medium' },
								{ label: 'Groot', value: 'large' },
							],
							onChange: setAttr('logoSize'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Snelheid',
							help: 'Hoe lang één volledige ronde duurt — langzamer voelt rustiger, sneller valt meer op.',
							value: attrs.speed || 'medium',
							options: [
								{ label: 'Langzaam', value: 'slow' },
								{ label: 'Middel', value: 'medium' },
								{ label: 'Snel', value: 'fast' },
							],
							onChange: setAttr('speed'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Richting',
							value: attrs.direction || 'left',
							options: [
								{ label: 'Naar links', value: 'left' },
								{ label: 'Naar rechts', value: 'right' },
							],
							onChange: setAttr('direction'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Grijstinten',
							help: "Toont elk logo in grijstinten i.p.v. de eigen kleuren.",
							checked: !!attrs.grayscale,
							onChange: setAttr('grayscale'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Pauzeren bij hover',
							help: 'Stopt de band zolang de muisaanwijzer erover staat.',
							checked: !!attrs.pauseOnHover,
							onChange: setAttr('pauseOnHover'),
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Volledige schermbreedte',
							help: 'Laat alleen de logo-band van rand tot rand van het scherm lopen, los van de uitlijning hierboven — de titel blijft op de normale breedte staan.',
							checked: !!attrs.fullWidth,
							onChange: setAttr('fullWidth'),
						})
					),
					el(PanelBody, { title: "Logo's", initialOpen: true },
						logos.length > 0
							? el('div', {
									style: {
										display: 'grid',
										gridTemplateColumns: 'repeat(3, 1fr)',
										gap: '8px',
										marginBottom: '12px',
									},
								},
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
											// "contain", niet "cover": hetzelfde uitgangspunt als
											// render.php's eigen "own-logo-slider-logo" — een
											// preview die een logo bijsnijdt, laat een editor een
											// ander resultaat verwachten dan wat er straks echt
											// op de site staat.
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
												props.setAttributes({ logos: removeLogoAt(logos, index) });
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
						)
					)
				)
			);
		};
	});
})(window.wp);
