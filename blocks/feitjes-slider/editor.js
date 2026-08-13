/**
 * "Titel", de feitjes zelf, "Aantal naast elkaar", de paginatie-toggle en de
 * kleurkeuzes als plain block attributes (zie block.json), zelfde reden als
 * acf/afbeeldingen-slider's eigen editor.js: puur visuele/inhoudelijke keuzes
 * zonder losse SCF-velden nodig.
 *
 * De feitjes worden hier in de zijbalk beheerd (twee tekstvelden per rij, plus
 * toevoegen/verwijderen/volgorde) i.p.v. als geneste blocks zoals
 * acf/content-blokken-slider — zie render.php's docblock voor waarom een
 * feitje als data behandeld wordt en niet als bewerkbare content.
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
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
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

	addFilter('editor.BlockEdit', 'own-framework/feitjes-slider-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/feitjes-slider') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;
			var facts = attrs.facts || [];

			function setAttr(name) {
				return function (value) {
					var next = {};
					next[name] = value;
					props.setAttributes(next);
				};
			}

			// Altijd een nieuwe array/objecten teruggeven i.p.v. de bestaande
			// muteren: Gutenberg vergelijkt attributes op referentie, dus een
			// in-place wijziging zou geen re-render (en geen "niet-opgeslagen
			// wijzigingen") triggeren.
			function updateFact(index, key, value) {
				props.setAttributes({
					facts: facts.map(function (fact, i) {
						if (i !== index) {
							return fact;
						}
						var next = Object.assign({}, fact);
						next[key] = value;
						return next;
					}),
				});
			}

			function removeFact(index) {
				props.setAttributes({
					facts: facts.filter(function (fact, i) {
						return i !== index;
					}),
				});
			}

			function moveFact(index, delta) {
				var target = index + delta;
				if (target < 0 || target >= facts.length) {
					return;
				}
				var next = facts.slice();
				var moved = next.splice(index, 1)[0];
				next.splice(target, 0, moved);
				props.setAttributes({ facts: next });
			}

			function addFact() {
				props.setAttributes({
					facts: facts.concat([{ value: '', label: '' }]),
				});
			}

			var factRows = facts.map(function (fact, index) {
				return el('div', {
					key: index,
					style: {
						border: '1px solid #e0e0e0',
						borderRadius: '2px',
						padding: '12px',
						marginBottom: '8px',
					},
				},
					el(TextControl, {
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
						label: 'Getal',
						placeholder: '200+',
						value: fact.value || '',
						onChange: function (value) {
							updateFact(index, 'value', value);
						},
					}),
					el('div', { style: { marginTop: '8px' } },
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Label',
							placeholder: 'leerlingen',
							value: fact.label || '',
							onChange: function (value) {
								updateFact(index, 'label', value);
							},
						})
					),
					el('div', { style: { display: 'flex', gap: '4px', marginTop: '8px' } },
						el(Button, {
							icon: 'arrow-up-alt2',
							label: 'Omhoog',
							size: 'small',
							disabled: index === 0,
							onClick: function () {
								moveFact(index, -1);
							},
						}),
						el(Button, {
							icon: 'arrow-down-alt2',
							label: 'Omlaag',
							size: 'small',
							disabled: index === facts.length - 1,
							onClick: function () {
								moveFact(index, 1);
							},
						}),
						el(Button, {
							icon: 'trash',
							label: 'Feitje verwijderen',
							size: 'small',
							isDestructive: true,
							style: { marginLeft: 'auto' },
							onClick: function () {
								removeFact(index);
							},
						})
					)
				);
			});

			var paginationColorControl = attrs.showPagination
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
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Titel',
							help: 'Verschijnt links boven de slider. Leeg laten om te verbergen.',
							value: attrs.title || '',
							onChange: setAttr('title'),
						}),
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Aantal naast elkaar',
							help: 'Geldt vanaf grote schermen. Daaronder staan er altijd 2 naast elkaar, op mobiel 1.',
							// SelectControl geeft altijd een string terug, maar het
							// attribuut is een number (block.json) — zonder deze
							// omzetting slaat het "4" op i.p.v. 4, en valt render.php's
							// eigen in_array(..., true)-check terug op de default.
							value: String(attrs.slidesPerView || 4),
							options: [
								{ label: '2 feitjes', value: '2' },
								{ label: '3 feitjes', value: '3' },
								{ label: '4 feitjes', value: '4' },
								{ label: '5 feitjes', value: '5' },
							],
							onChange: function (value) {
								props.setAttributes({ slidesPerView: parseInt(value, 10) });
							},
						}),
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Paginatie-bolletjes tonen',
							help: 'Toont klikbare bolletjes onder de slider.',
							checked: !!attrs.showPagination,
							onChange: setAttr('showPagination'),
						})
					),
					el(PanelBody, { title: 'Feitjes', initialOpen: true },
						factRows,
						el(Button, {
							variant: 'secondary',
							onClick: addFact,
						}, 'Feitje toevoegen')
					)
				),
				el(InspectorControls, null,
					el(PanelColorSettings, {
						title: 'Kleur feitjes',
						initialOpen: true,
						colorSettings: [
							{
								value: attrs.valueColor || '',
								onChange: function (value) {
									props.setAttributes({ valueColor: resolveToPresetVar(value) });
								},
								label: 'Getal',
							},
							{
								value: attrs.labelColor || '',
								onChange: function (value) {
									props.setAttributes({ labelColor: resolveToPresetVar(value) });
								},
								label: 'Label',
							},
						],
					}),
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
