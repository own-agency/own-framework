/**
 * own_color_palette field type (inc/scf/color-palette.php) — wires up the
 * one swatch that isn't a plain radio: "Aangepast" uses WP core's own
 * wp-color-picker (Iris), same widget SCF's OWN bundled "Color Picker"
 * field type uses (assets/build/js/acf-input.js, _acf-field-color-picker.js)
 * — this mirrors that field type's exact registration pattern
 * (acf.Field.extend()/acf.registerFieldType(), wait:'load',
 * this.$()-scoped queries) rather than the older jQuery
 * `$(document).on('acf/setup_fields', …)` compatibility event, which this
 * SCF version's JS doesn't drive its own field types through at all — using
 * it here silently never fired, leaving the input as a plain unenhanced
 * text field (the exact bug this replaces).
 *
 * Every OTHER swatch in the group needs zero JS — native :checked survives
 * ACF's own DOM remounts on its own. This only exists because Iris itself
 * needs initializing per DOM node, and because ITS chosen colour has to be
 * copied back onto the plain radio input that actually IS this field's
 * stored value ("custom:#rrggbb", see color-palette.php's format_value()).
 */
(function ($) {
	var Field = acf.Field.extend({
		type: 'own_color_palette',
		wait: 'load',
		events: {
			duplicateField: 'onDuplicate',
		},
		$customPicker: function () {
			return this.$('.own-color-swatch__custom-picker');
		},
		$customRadio: function () {
			return this.$('.own-color-swatch__radio--custom');
		},
		initialize: function () {
			var $picker = this.$customPicker();
			if (!$picker.length) {
				return;
			}

			var $radio = this.$customRadio();

			$picker.wpColorPicker({
				defaultColor: false,
				change: function (event, ui) {
					$radio.prop('checked', true);
					acf.val($radio, 'custom:' + ui.color.toString());
				},
				clear: function () {
					$radio.prop('checked', false);
					acf.val($radio, 'custom:');
				},
			});

			// Picking a DIFFERENT swatch leaves the stored value correct on
			// its own (only one radio in a native radio group can ever be
			// checked) — this only keeps Iris's own picker button from
			// still visually reading as "selected" once that happens.
			this.$('.own-color-swatch__radio')
				.not($radio)
				.on('change', function () {
					$radio.prop('checked', false);
				});
		},
		onDuplicate: function (e, $el, $duplicate) {
			// wpColorPicker has no destroy method — same fix SCF's own
			// bundled Color Picker field type uses for the identical
			// problem: replace a duplicated row's own enhanced picker back
			// with a plain input so it initializes cleanly instead of
			// carrying the original row's Iris instance along with it.
			$duplicate.find('.wp-picker-container').each(function () {
				var $input = $(this).find('input[type="text"]').first();
				$(this).replaceWith($input);
			});
		},
	});

	acf.registerFieldType(Field);
})(jQuery);
