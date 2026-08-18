/**
 * "accentColor", "dateBadgeColor" and the kaart's own "cardTitleColor"/
 * "cardTextColor"/"cardBackgroundColor"/"cardLinkColor" as plain block
 * attributes (block.json) + one shared "Kleuren" panel — same reasoning as
 * acf/vacatures' own editor.js: badges/checkboxes/paginering/de kaart's
 * eigen titel/tekst/achtergrond/link zijn allemaal sub-elementen die het
 * native "Kleuren"-paneel nooit bereikt (CLAUDE.md Traps #7), dus block.json
 * heeft bewust geen "supports.color".
 *
 * "dateBadgeColor" staat LEEG als het niet expliciet gekozen is (net als elk
 * ander veld hier) — src/css/components/agenda.css's eigen
 * ".own-agenda-date-badge" valt dan simpelweg terug op "accentColor" (en
 * daarna de site-brede fallback), dus een editor hoeft 'm niet apart te
 * zetten om nog steeds één samenhangende kleur te zien.
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
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;

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

	addFilter('editor.BlockEdit', 'own-framework/agenda-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/agenda') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Resultaten per pagina', initialOpen: true },
						el(RangeControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Momenten per pagina',
							value: typeof attrs.perPage === 'number' ? attrs.perPage : 10,
							onChange: function (value) {
								props.setAttributes({ perPage: value });
							},
							min: 3,
							max: 30,
						})
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
								label: 'Accentkleur (badges, filters, paginering)',
							},
							{
								value: attrs.dateBadgeColor || '',
								onChange: function (value) {
									props.setAttributes({ dateBadgeColor: resolveToPresetVar(value) });
								},
								label: 'Datumbadge (los van de accentkleur, leeg = zelfde kleur)',
							},
							{
								value: attrs.cardTitleColor || '',
								onChange: function (value) {
									props.setAttributes({ cardTitleColor: resolveToPresetVar(value) });
								},
								label: 'Agenda-kaart: titelkleur',
							},
							{
								value: attrs.cardTextColor || '',
								onChange: function (value) {
									props.setAttributes({ cardTextColor: resolveToPresetVar(value) });
								},
								label: 'Agenda-kaart: tekstkleur',
							},
							{
								value: attrs.cardBackgroundColor || '',
								onChange: function (value) {
									props.setAttributes({ cardBackgroundColor: resolveToPresetVar(value) });
								},
								label: 'Agenda-kaart: achtergrondkleur',
							},
							{
								value: attrs.cardLinkColor || '',
								onChange: function (value) {
									props.setAttributes({ cardLinkColor: resolveToPresetVar(value) });
								},
								label: 'Agenda-kaart: linkkleur (los van de badges)',
							},
						],
					})
				)
			);
		};
	});
})(window.wp);
