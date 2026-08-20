/**
 * "accentColor" (checkboxes/paginering/sorteerpaneel) en "cardTextColor" (de
 * titel/quote/datum/link over de foto op elke kaart) als plain block-
 * attributes + eigen "Kleuren"-paneel, exact hetzelfde patroon als acf/news/
 * editor.js — die sub-elementen zijn nooit door het native "Kleuren"-paneel
 * te bereiken (CLAUDE.md Traps #7), dus block.json heeft bewust geen
 * "supports.color". "cardTextColor" is bewust ÉÉN attribuut voor titel/
 * quote/datum/link samen (niet vier losse zoals acf/news' eigen
 * cardTitleColor/cardTextColor/cardBackgroundColor/cardLinkColor) — de kaart
 * heeft geen apart te kleuren achtergrond (dat IS de foto), dus is er maar
 * één kleurzone: de tekst die over de donkere scrim heen ligt.
 *
 * "fallbackImage" — een losse afbeelding (MediaUpload, zelfde contract als
 * acf/cta's eigen singleImagePicker) die als kaartachtergrond dient voor een
 * verhaal ZONDER eigen uitgelichte afbeelding, i.p.v. een lege/kapotte
 * afbeeldingsbox. Blijft die leeg, dan valt de kaart terug op een effen
 * kleurvak (".own-verhalen-card--fallback", verhalen.css) — nooit een kale
 * lege ruimte.
 *
 * Plain wp.element.createElement (no JSX), same reason as every other
 * block's editor.js in this theme: works without a Vite/babel build step.
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
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;

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

	// Eén afbeelding kiezen — zelfde contract als acf/cta's eigen
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

	addFilter('editor.BlockEdit', 'own-framework/verhalen-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/verhalen') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Instellingen', initialOpen: true },
						el(RangeControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Aantal per pagina',
							min: 3,
							max: 24,
							value: attrs.perPage || 12,
							onChange: function (value) {
								props.setAttributes({ perPage: value });
							},
						})
					)
				),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Kaart zonder foto', initialOpen: false },
						el('p', { style: { marginTop: 0 } },
							'Verschijnt als achtergrond op de kaart van een verhaal zonder eigen uitgelichte afbeelding. Leeg = een effen kleurvak.'
						),
						singleImagePicker(
							attrs.fallbackImage,
							function (image) {
								props.setAttributes({ fallbackImage: image });
							},
							'Fallback-afbeelding kiezen',
							'Fallback-afbeelding wijzigen'
						)
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleuren',
						initialOpen: false,
						colorSettings: [
							{
								value: attrs.accentColor || '',
								onChange: function (value) {
									props.setAttributes({ accentColor: resolveToPresetVar(value) });
								},
								label: 'Accentkleur (filters, paginering)',
							},
							{
								value: attrs.cardTextColor || '',
								onChange: function (value) {
									props.setAttributes({ cardTextColor: resolveToPresetVar(value) });
								},
								label: 'Kaartinhoud (titel, quote, datum, link over de foto)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
