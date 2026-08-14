/**
 * "Titel" als plain block attribute (zie block.json) — "Bron" zelf staat NIET
 * hier, maar als ACF select-veld ("faqs_source",
 * acf-json/group_block_faqs.json): zo kan ACF's eigen conditional_logic de
 * drie bron-specifieke velden (repeater/relationship/taxonomy) een voor een
 * tonen/verbergen op basis van die keuze — een block-attribute kan dat niet,
 * ACF-conditional_logic kan alleen op een ANDER ACF-veld reageren. Anders dan
 * acf/vacatures-uitgelicht (waar "Bron" wél een attribute + SelectControl is,
 * maar dat blok maar één alternatief veld heeft om te tonen/verbergen) heeft
 * dit blok drie inhoudelijk verschillende velden tegelijk — zonder
 * conditional_logic zouden die alle drie tegelijk in de "FAQ's beheren"-
 * knop staan, verwarrender dan één simpele instructions-tekst kan
 * compenseren.
 *
 * Plain wp.element.createElement (geen JSX), zelfde reden als elk ander
 * block's editor.js in dit theme: werkt zonder Vite/babel-buildstap.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;

	addFilter('editor.BlockEdit', 'own-framework/faqs-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/faqs') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

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
					el(PanelBody, { title: 'Instellingen', initialOpen: true },
						el(TextControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Titel',
							help: 'Verschijnt boven de FAQ-lijst. Leeg laten om te verbergen.',
							value: attrs.title || '',
							onChange: setAttr('title'),
						})
					)
				)
			);
		};
	});
})(window.wp);
