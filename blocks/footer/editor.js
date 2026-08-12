/**
 * "align" ("Breedte inhoud" below) is a plain block attribute (see
 * block.json) + custom SelectControl, not the native toolbar align control
 * (block.json's "supports.align" is false) — this block has no
 * is-layout-constrained ancestor (it's a bare top-level block in
 * parts/footer.html, not wrapped in a core/group with layout:constrained),
 * and WordPress' own alignment-control logic (packages/block-editor/src/
 * layouts/flow.js's getAlignments) deliberately never offers "wide"/"full"
 * for a block-based theme's default ("flow") layout — only a "constrained"
 * layout ancestor's getAlignments checks contentSize/wideSize at all.
 * Wrapping this block in such a group to get the native control back would
 * ALSO make WordPress' own useRootPaddingAwareAlignments CSS constrain THIS
 * block's own wrapper (the same is-layout-constrained rule from acf/hero's
 * docblock) — exactly what would break its own always-edge-to-edge
 * background. render.php reads this attribute identically either way; only
 * the UI differs. Same reasoning as blocks/header/editor.js's own docblock.
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
	var SelectControl = wp.components.SelectControl;

	addFilter(
		'editor.BlockEdit',
		'own-framework/footer-settings',
		function (BlockEdit) {
			return function (props) {
				if (props.name !== 'acf/footer') {
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
							{ title: 'Breedte', initialOpen: true },
							el(SelectControl, {
								label: 'Breedte inhoud',
								help: 'De achtergrondkleur loopt altijd tot de randen van het scherm — dit bepaalt alleen de breedte van logo/kolommen erbinnen.',
								value: props.attributes.align || '',
								options: [
									{ label: 'Standaard (inhoudsbreedte)', value: '' },
									{ label: 'Breed', value: 'wide' },
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
