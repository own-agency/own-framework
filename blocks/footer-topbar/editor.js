/**
 * Titel/subtekst zijn GEEN block-attributen (meer) — dat is nu een echte,
 * klikbaar bewerkbare InnerBlocks-intro (core/heading + core/paragraph,
 * render.php), rechtstreeks op het canvas bewerkt, inclusief de volledige
 * native H1-H6-toolbar voor de kop. "Balk tonen" (aan/uit, render.php's eigen
 * $enabled), "Rechterkant" (rightSideContent, alleen relevant binnen
 * multisite) en "CTA-knop" blijven wél plain block attributes hier: geen
 * content-achtig veld, hoort bij dit block's eigen instellingen i.p.v. de SCF
 * fields modal. block.json's "editorScript" enqueuet dit automatisch, alleen
 * wanneer acf/footer-topbar in gebruik is.
 *
 * Geen los TEKST-override-veld voor de site-wisselaar: de knoptekst is altijd
 * de echte sitenaam (get_bloginfo('name'), render.php) en de lijst komt altijd
 * automatisch uit get_sites() — niets om daaraan in te stellen. Wél een eigen
 * "Kleuren"-paneel (PanelColorSettings hieronder) voor de sitenaam-tekst zelf
 * (knop + lijst) — dat is GEEN native "Kleuren"-support (block.json's
 * "supports.color" kleurt alleen de wrapper, CLAUDE.md Traps #7), dus dit
 * sub-element heeft z'n eigen attribute + panel nodig, zelfde patroon als
 * acf/vacature-single-hero's eigen editor.js. "Rechterkant" en "CTA-knop"
 * blijven altijd allebei zichtbaar, ook op een multisite-omgeving, met
 * helptekst die uitlegt wanneer welke telt — zie render.php's eigen docblock
 * voor waarom dit geen dynamische panelen zijn die zouden moeten weten of dít
 * environment multisite is.
 *
 * "align" ("Breedte inhoud" hieronder) is een plain attribute + eigen
 * SelectControl, geen native toolbar-control (block.json's "supports.align"
 * is false) — zelfde reden als blocks/header-topbar/editor.js's eigen
 * docblock (dit block is óók een bare, top-level template-part-block).
 *
 * Plain wp.element.createElement (no JSX) so this ships as-is, no Vite/babel
 * build step required.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;

	// Resolves a raw swatch colour back to its theme.json slug — zelfde
	// reden/patroon als acf/vacature-single-hero's eigen editor.js: houdt het
	// attribute dynamisch als een klant de kleur later via Ontwerp aanpast.
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

	addFilter(
		'editor.BlockEdit',
		'own-framework/footer-topbar-settings',
		function (BlockEdit) {
			return function (props) {
				if (props.name !== 'acf/footer-topbar') {
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
							{ title: 'Zichtbaarheid', initialOpen: true },
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Balk tonen',
								help: 'Uitzetten verbergt de balk op de site, maar bewaart alle tekst/instellingen hieronder — makkelijk weer aan te zetten zonder opnieuw in te vullen.',
								checked: props.attributes.enabled !== false,
								onChange: function (value) {
									props.setAttributes({ enabled: value });
								},
							})
						),
						el(
							PanelBody,
							{ title: 'Rechterkant', initialOpen: false },
							el(SelectControl, {
								label: 'Tonen binnen een multisite-netwerk',
								help: 'Op een losse site staat hier altijd de CTA-knop (er is dan geen netwerk om naar te wisselen) — deze keuze doet dan niets. Binnen multisite kies je hier zelf tussen de site-wisselaar of de CTA-knop.',
								value: props.attributes.rightSideContent || 'switcher',
								options: [
									{ label: 'Site-wisselaar', value: 'switcher' },
									{ label: 'CTA-knop', value: 'cta' },
								],
								onChange: function (value) {
									props.setAttributes({ rightSideContent: value });
								},
							})
						),
						el(
							PanelBody,
							{ title: 'CTA-knop', initialOpen: false },
							el('p', { style: { fontSize: '12px', fontStyle: 'italic', marginTop: 0 } }, 'Verschijnt altijd op een losse site. Binnen een multisite-netwerk alleen als hierboven bij "Rechterkant" voor de CTA-knop gekozen is.'),
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Knoptekst',
								value: props.attributes.ctaLabel || '',
								onChange: function (value) {
									props.setAttributes({ ctaLabel: value });
								},
							}),
							el(TextControl, {
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true,
								label: 'Link',
								value: props.attributes.ctaUrl || '',
								onChange: function (value) {
									props.setAttributes({ ctaUrl: value });
								},
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Open in nieuw tabblad',
								checked: !!props.attributes.ctaOpenInNewTab,
								onChange: function (value) {
									props.setAttributes({ ctaOpenInNewTab: value });
								},
							}),
							el(SelectControl, {
								label: 'Stijl',
								help: 'Dezelfde stijlen als een gewoon Knop-block.',
								value: props.attributes.ctaStyle || '',
								options: [
									{ label: 'Fill', value: '' },
									{ label: 'Outline', value: 'outline' },
									{ label: 'Tekstlink', value: 'link' },
									{ label: 'Secundair', value: 'secondary' },
									{ label: 'Secundair (rand)', value: 'secondary-outline' },
								],
								onChange: function (value) {
									props.setAttributes({ ctaStyle: value });
								},
							})
						),
						el(
							PanelColorSettings,
							{
								title: 'Kleuren',
								initialOpen: false,
								colorSettings: [
									{
										value: props.attributes.switcherTextColor || '',
										onChange: function (value) {
											props.setAttributes({ switcherTextColor: resolveToPresetVar(value) });
										},
										label: 'Site-wisselaar: sitenaam-tekst',
									},
								],
							}
						),
						el(
							PanelBody,
							{ title: 'Breedte', initialOpen: false },
							el(SelectControl, {
								label: 'Breedte inhoud',
								help: 'De achtergrondkleur loopt altijd tot de randen van het scherm — dit bepaalt alleen de breedte van de tekst/knop erbinnen.',
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
