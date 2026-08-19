/**
 * Titel/subtekst zijn GEEN block-attributen (meer) — dat is nu een echte,
 * klikbaar bewerkbare InnerBlocks-intro (core/heading + core/paragraph,
 * render.php), rechtstreeks op het canvas bewerkt, inclusief de volledige
 * native H1-H6-toolbar voor de kop. "Balk tonen" (aan/uit, render.php's eigen
 * $enabled) en "CTA-knop" (rechterkant, alleen buiten multisite) blijven wél
 * plain block attributes hier: geen content-achtig veld, hoort bij dit
 * block's eigen instellingen i.p.v. de SCF fields modal. block.json's
 * "editorScript" enqueuet dit automatisch, alleen wanneer acf/footer-topbar
 * in gebruik is.
 *
 * Geen eigen paneel voor de site-wisselaar: die knoptekst is altijd de echte
 * sitenaam (get_bloginfo('name'), render.php) en de lijst komt altijd
 * automatisch uit get_sites() — niets om hier in te stellen, dus geen los
 * override-veld dat een editor per ongeluk een verouderde tekst kan laten
 * tonen. Het CTA-paneel blijft daarom altijd zichtbaar, ook op een
 * multisite-omgeving, met helptekst die uitlegt wanneer het wel/niet
 * verschijnt — zie render.php's eigen docblock voor waarom dit geen dynamisch
 * panel is dat zou moeten weten of dít environment multisite is.
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
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;

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
							{ title: 'CTA-knop', initialOpen: false },
							el('p', { style: { fontSize: '12px', fontStyle: 'italic', marginTop: 0 } }, 'Verschijnt alleen buiten een multisite-netwerk — daarbinnen toont deze balk automatisch de site-wisselaar in plaats hiervan.'),
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
							})
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
