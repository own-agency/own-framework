(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var useSelect = wp.data.useSelect;

	addFilter('editor.BlockEdit', 'own-framework/footer-menu-settings', function (BlockEdit) {
		return function (props) {
			if (props.name !== 'acf/footer-menu') {
				return el(BlockEdit, props);
			}

			var attrs = props.attributes;

			// null while still loading (useSelect's own convention) — de
			// picker toont dan alleen "Automatisch", tot de REST-request
			// (wp/v2/menus, via de "core"-store) terugkomt.
			var menus = useSelect(function (select) {
				return select('core').getEntityRecords('root', 'menu', { per_page: -1 });
			}, []);
			var menuOptions = [{ label: 'Automatisch (“Footer Menu 1”-locatie)', value: '0' }];
			(menus || []).forEach(function (menu) {
				menuOptions.push({ label: menu.name, value: String(menu.id) });
			});

			return el(Fragment, null,
				el(BlockEdit, props),
				el(InspectorControls, null,
					el(PanelBody, { title: 'Inhoud', initialOpen: true },
						el(SelectControl, {
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
							label: 'Menu',
							help: 'Welk menu hier getoond wordt.',
							value: String(attrs.menuId || 0),
							options: menuOptions,
							onChange: function (value) {
								props.setAttributes({ menuId: parseInt(value, 10) || 0 });
							},
						})
					)
				)
			);
		};
	});
})(window.wp);
