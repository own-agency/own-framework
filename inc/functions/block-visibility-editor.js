/**
 * "Weergave per apparaat" — one Inspector panel added to EVERY block (this
 * theme's own and every native core block alike), not per-block editor.js
 * files: see inc/functions/block-visibility.php's own docblock for the full
 * reasoning (same centralised-filter approach as inc/functions/
 * default-block-align.php's "wide" default, just for a brand-new attribute
 * pair instead of an existing one).
 *
 * Plain wp.element.createElement (geen JSX), hand-written with no Vite/
 * build step — same approach as every block's own editor.js (see e.g.
 * blocks/hero/editor.js's own docblock). A plain function wrapping
 * BlockEdit, not wp.compose.createHigherOrderComponent: this theme's own
 * per-block editor.js files (hero, header, …) already use exactly this
 * simpler form (a plain closure checking props.name), so this reuses that
 * same idiom rather than introducing a second one just because this filter
 * happens to apply to every block instead of one.
 *
 * "core/columns" gets a THIRD toggle in this same panel ("Kolommen
 * omdraaien op mobiel", inc/core-blocks/columns-reverse-mobile.php) — kept
 * in the same panel rather than a second one, since both are "how this
 * block behaves once stacked on a phone" settings an editor would look for
 * in the same place.
 */
(function (wp) {
	var addFilter = wp.hooks.addFilter;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var VStack = wp.components.VStack || wp.components.__experimentalVStack;

	addFilter('editor.BlockEdit', 'own-framework/block-visibility', function (BlockEdit) {
		return function (props) {
			var attrs = props.attributes || {};
			var isColumns = props.name === 'core/columns';

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
					el(PanelBody, { title: 'Weergave per apparaat', initialOpen: false },
						el(VStack, { spacing: 4 },
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Verbergen op mobiel',
								help: 'Dit blok wordt dan niet getoond onder 1024px breedte (telefoon/tablet).',
								checked: !!attrs.ownHideOnMobile,
								onChange: setAttr('ownHideOnMobile'),
							}),
							el(ToggleControl, {
								__nextHasNoMarginBottom: true,
								label: 'Verbergen op desktop',
								help: 'Dit blok wordt dan niet getoond vanaf 1024px breedte.',
								checked: !!attrs.ownHideOnDesktop,
								onChange: setAttr('ownHideOnDesktop'),
							}),
							isColumns
								? el(ToggleControl, {
										__nextHasNoMarginBottom: true,
										label: 'Kolommen omdraaien op mobiel',
										help: 'Draait alleen de GESTAPELDE volgorde op mobiel om — de kolomvolgorde op desktop blijft ongewijzigd.',
										checked: !!attrs.ownReverseOnMobile,
										onChange: setAttr('ownReverseOnMobile'),
								  })
								: null
						)
					)
				)
			);
		};
	});

	/**
	 * Purely visual feedback INSIDE the editor canvas — the Inspector toggles
	 * above already work without this (inc/functions/block-visibility.php's
	 * own render_block filter handles the real front-end hiding), but an
	 * editor who turns "Verbergen op mobiel" on would otherwise see nothing
	 * change here at all and have no way to tell, while editing, which
	 * blocks are set to disappear at which width. wrapperProps.className
	 * merges into the SAME element Gutenberg already gives
	 * "position: relative" (the block-list-block's own outer li), which is
	 * what src/css/components/editor-layout.css's own "::after" badges (the
	 * visible dashed outline + label) anchor against.
	 */
	addFilter('editor.BlockListBlock', 'own-framework/block-visibility-canvas', function (BlockListBlock) {
		return function (props) {
			var attrs = props.block && props.block.attributes ? props.block.attributes : {};
			var classNames = [];
			if (attrs.ownHideOnMobile) {
				classNames.push('own-editor-hidden-mobile');
			}
			if (attrs.ownHideOnDesktop) {
				classNames.push('own-editor-hidden-desktop');
			}

			if (classNames.length === 0) {
				return el(BlockListBlock, props);
			}

			var existingClassName = (props.wrapperProps && props.wrapperProps.className) || '';
			var wrapperProps = Object.assign({}, props.wrapperProps, {
				className: (existingClassName + ' ' + classNames.join(' ')).trim(),
			});

			return el(BlockListBlock, Object.assign({}, props, { wrapperProps: wrapperProps }));
		};
	});
})(window.wp);
