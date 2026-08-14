/**
 * "Sticky" als plain block attribute (zie block.json) — de kop erboven is
 * InnerBlocks (een los core/heading, render.php's eigen docblock), dus geen
 * "Titel"-TextControl meer hier: die kop stylet een editor voortaan gewoon
 * rechtstreeks met de native kop-toolbar (niveau/uitlijning/kleur/
 * lettergrootte), en de kaart-achtergrond/tekstkleur komen van het native
 * "Kleuren"-paneel (block.json's supports.color) — geen eigen
 * PanelColorSettings nodig zoals acf/cta, zie render.php's docblock voor
 * waarom dat verschil hier terecht is. "Bron" en de paginaboom-specifieke
 * velden staan NIET hier: dat zijn echte ACF-velden
 * (acf-json/group_block_zijbalk_navigatie.json) met onderlinge
 * conditional_logic, die SCF zelf al rendert via de "Navigatie-
 * instellingen"-knop. Zelfde afweging als acf/faqs's eigen editor.js.
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
	var ToggleControl = wp.components.ToggleControl;

	addFilter('editor.BlockEdit', 'own-framework/zijbalk-navigatie-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/zijbalk-navigatie') {
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
						el(ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: 'Blijft zichtbaar tijdens scrollen',
							checked: attrs.sticky !== false,
							onChange: setAttr('sticky'),
						})
					)
				)
			);
		};
	});
})(window.wp);
