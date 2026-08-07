<?php
defined('ABSPATH') || exit();

if (!class_exists('acf')) {
	return;
}

/**
 * Rough perceptual luminance — only used to decide the selected swatch's own
 * checkmark colour (dark check on a light swatch, light check on a dark
 * one), so it doesn't need to be colour-science-accurate, only needs to pick
 * the legible side.
 */
function ownframework_color_is_light(string $hex): bool
{
	$hex = ltrim($hex, '#');
	if (strlen($hex) === 3) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
		// Not a real hex (a CSS function, a keyword, ...) — no reliable way
		// to compute this, so default to the more common case.
		return true;
	}

	$r = hexdec(substr($hex, 0, 2));
	$g = hexdec(substr($hex, 2, 2));
	$b = hexdec(substr($hex, 4, 2));

	return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.6;
}

/**
 * Field-type assets, not the front-end Vite bundle: this only ever renders
 * inside wp-admin (options pages, classic meta boxes) or the block editor's
 * ACF settings modal, never the front end. acf/input/admin_enqueue_scripts is
 * ACF's own dedicated hook for exactly this — fires once per admin screen
 * that actually has ACF fields on it (block-editor modal included), unlike
 * echoing a <style> tag from render_field() itself, which would otherwise
 * repeat once per FIELD INSTANCE (every repeater row, every block using this
 * field type on the same screen).
 */
add_action('acf/input/admin_enqueue_scripts', function () {
	wp_enqueue_style(
		'own-color-palette-field',
		get_template_directory_uri() . '/inc/scf/color-palette.css',
		[],
		wp_get_theme()->get('Version')
	);
});

/**
 * Kleurenpalet — swatch-picker die rechtstreeks het Ontwerp-kleurenpalet
 * toont (theme.json's settings.color.palette, of wat een klant sindsdien via
 * de Site Editor heeft aangepast — ownframework_get_theme_json_colors(),
 * inc/scf/custom-color-palette.php, leest al precies dezelfde, live-resolved
 * bron). Bedoeld als HET standaardveld voor elke "kies een merkkleur"-situatie
 * in toekomstige blocks — zie root CLAUDE.md's "Brand identity"-sectie: een
 * blockveld hoort altijd naar het echte, dynamische palet te wijzen, nooit
 * een eigen vaste kleurenlijst.
 *
 * Radio-inputs, niet <div>+JS-state: selectie is dan native browser-gedrag
 * (:checked), dus overleeft dit veld ACF's eigen DOM-hermontages (repeater-
 * rijen klonen, het block-instellingen-modal opnieuw openen) zonder dat er
 * ook maar één regel JS nodig is om die state opnieuw te synchroniseren.
 */
class Own_Field_Color_Palette extends acf_field
{
	// Wit/transparant/zwart bestaan nooit als slug in een client-palet (dat
	// is precies waarom ze hier vast staan) — geen conflict mogelijk met een
	// echte theme.json-slug, dus geen aparte namespace-prefix nodig.
	const STANDARD_COLORS = [
		'white' => '#ffffff',
		'black' => '#000000',
	];

	function __construct()
	{
		$this->name = 'own_color_palette';
		$this->label = __('Kleurenpalet', 'own-framework');
		$this->category = 'choice';
		$this->defaults = [
			'allowed_colors' => '',
			'default_value' => '',
			'return_format' => 'key',
		];

		parent::__construct();
	}

	/**
	 * @return array<string, array{name: string, color: string}>
	 */
	private function get_swatches(array $field): array
	{
		$palette = ownframework_get_theme_json_colors();

		$allowed = array_filter(array_map('trim', explode(',', (string) ($field['allowed_colors'] ?? ''))));
		if (!empty($allowed)) {
			$palette = array_intersect_key($palette, array_flip($allowed));
		}

		// Wit/transparant/zwart al altijd vóór de divider (get_standard_
		// swatches()) — een palet-kleur die TOEVALLIG exact wit of zwart is
		// (dit project's eigen "Basis"/"Contrast": #FFFFFF/#000000, een
		// andere slug maar identieke hex) zou anders na de divider hetzelfde
		// zwatch een 2e keer tonen. Filtert op KLEURWAARDE, niet op slug —
		// een project noemt zijn witte kleur zelden ook letterlijk "white".
		return array_filter($palette, fn ($entry) => !$this->is_standard_color($entry['color']));
	}

	private function is_standard_color(string $value): bool
	{
		$normalized = strtolower(trim($value));
		if ($normalized === 'transparent') {
			return true;
		}

		$hex = ltrim($normalized, '#');
		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		foreach (self::STANDARD_COLORS as $standard_hex) {
			if ($hex === ltrim(strtolower($standard_hex), '#')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Wit, transparant en zwart staan hier ALTIJD, ongeacht "Toegestane
	 * kleuren" — in tegenstelling tot elke theme.json-slug (die per project
	 * kan ontbreken of wijzigen), zijn dit de 3 kleuren die letterlijk elk
	 * project altijd heeft, dus ook de enige 3 die een developer via
	 * "Standaardkleur" hieronder zinvol als universeel fallback-default kan
	 * kiezen (bv. een achtergrondveld → wit, een tekstveld → zwart, een
	 * overlay-veld → transparant) zonder dat die default plots ongeldig
	 * wordt zodra een ander project een ander palet heeft.
	 *
	 * @return list<array{0: string, 1: string, 2: string, 3: ?string}>
	 */
	private function get_standard_swatches(): array
	{
		return [
			['transparent', __('Transparant', 'own-framework'), 'transparent', null],
			['white', __('Wit', 'own-framework'), 'light', self::STANDARD_COLORS['white']],
			['black', __('Zwart', 'own-framework'), 'dark', self::STANDARD_COLORS['black']],
		];
	}

	function render_field($field)
	{
		$swatches = $this->get_swatches($field);
		$selected = $field['value'] ?? '';

		echo '<div class="own-color-palette-field">';
		printf(
			'<div class="own-color-palette-field__grid" role="radiogroup" aria-label="%s">',
			esc_attr($field['label'])
		);

		// "Geen kleur" — expliciete manier om de keuze weer leeg te maken.
		// Zonder deze optie kan een editor, eenmaal een swatch gekozen, nooit
		// meer terug naar "geen voorkeur" zonder de HTML zelf aan te passen.
		$this->render_swatch($field, '', __('Geen kleur', 'own-framework'), $selected === '', 'none', null, true);

		// Wit/transparant/zwart altijd, vóór het echte palet — zie
		// get_standard_swatches()'s eigen docblok voor waarom.
		foreach ($this->get_standard_swatches() as [$value, $label, $variant, $color]) {
			$this->render_swatch($field, $value, $label, $selected === $value, $variant, $color);
		}

		if (!empty($swatches)) {
			echo '<span class="own-color-palette-field__divider" aria-hidden="true"></span>';

			foreach ($swatches as $slug => $entry) {
				$this->render_swatch(
					$field,
					$slug,
					$entry['name'],
					$selected === $slug,
					ownframework_color_is_light($entry['color']) ? 'light' : 'dark',
					$entry['color']
				);
			}
		}

		echo '</div>';
		echo '</div>';
	}

	private function render_swatch(array $field, string $value, string $label, bool $checked, string $variant, ?string $color = null, bool $is_first = false)
	{
		$style = $color ? sprintf(' style="--own-swatch-color: %s;"', esc_attr($color)) : '';

		printf(
			'<label class="own-color-swatch own-color-swatch--%s" title="%s">
				<input type="radio"%s name="%s" value="%s" class="own-color-swatch__radio"%s>
				<span class="own-color-swatch__circle"%s>
					<svg class="own-color-swatch__check" viewBox="0 0 16 16" aria-hidden="true"><path d="M13.5 4.5 6 12 2.5 8.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</span>
				<span class="screen-reader-text">%s</span>
			</label>',
			esc_attr($variant),
			esc_attr($label),
			$is_first ? ' id="' . esc_attr($field['id']) . '"' : '',
			esc_attr($field['name']),
			esc_attr($value),
			$checked ? ' checked' : '',
			$style,
			esc_html($label)
		);
	}

	function render_field_settings($field)
	{
		acf_render_field_setting($field, [
			'label' => __('Toegestane kleuren', 'own-framework'),
			'instructions' => __('Komma-gescheiden slugs uit het kleurenpalet (bv. "primary, secondary"). Leeg = alle kleuren uit het palet.', 'own-framework'),
			'type' => 'text',
			'name' => 'allowed_colors',
			'placeholder' => 'primary, secondary',
		]);

		// Alleen wit/transparant/zwart — niet een willekeurige palet-slug —
		// omdat dit de enige 3 kleuren zijn die letterlijk elk project altijd
		// heeft (get_standard_swatches()'s eigen docblok); een default op een
		// specifieke merkkleur zou stilletjes ongeldig worden zodra een ander
		// project dit veld hergebruikt met een ander palet.
		acf_render_field_setting($field, [
			'label' => __('Standaardkleur', 'own-framework'),
			'instructions' => __('Voorgeselecteerd zolang er nog geen kleur gekozen is. Kies wat het meest logisch is voor waar dit veld voor dient (bv. een achtergrondveld → wit, een tekstveld → zwart, een overlay-veld → transparant).', 'own-framework'),
			'type' => 'radio',
			'name' => 'default_value',
			'choices' => [
				'' => __('Geen', 'own-framework'),
				'white' => __('Wit', 'own-framework'),
				'transparent' => __('Transparant', 'own-framework'),
				'black' => __('Zwart', 'own-framework'),
			],
			'layout' => 'horizontal',
		]);

		acf_render_field_setting($field, [
			'label' => __('Retourformaat', 'own-framework'),
			'instructions' => __('Wat get_field() teruggeeft voor dit veld.', 'own-framework'),
			'type' => 'radio',
			'name' => 'return_format',
			'choices' => [
				'key' => __('Slug (bv. "primary")', 'own-framework'),
				'value' => __('Kleurwaarde (bv. "#ff0000")', 'own-framework'),
				'css_var' => __('CSS var() (bv. "var(--wp--preset--color--primary)")', 'own-framework'),
				'both' => __('Alle drie (array met key, value en css_var)', 'own-framework'),
			],
			'layout' => 'vertical',
		]);
	}

	function format_value($value, $post_id, $field)
	{
		if ($value === '' || $value === null) {
			return '';
		}

		$format = $field['return_format'] ?? 'key';

		if ($value === 'transparent') {
			// Geen echte hex en geen preset-slug om een var() op te bouwen —
			// in elk formaat gewoon het literal keyword teruggeven.
			$resolved = ['key' => 'transparent', 'value' => 'transparent', 'css_var' => 'transparent'];
		} elseif (isset(self::STANDARD_COLORS[$value])) {
			// Ook geen preset-slug: css_var valt hier terug op de kale hex
			// zelf, want er bestaat geen --wp--preset--color--white/black die
			// gegarandeerd hetzelfde betekent (een project kan zelf al een
			// eigen "white"/"black"-slug met een AFWIJKENDE kleur hebben).
			$hex = self::STANDARD_COLORS[$value];
			$resolved = ['key' => $value, 'value' => $hex, 'css_var' => $hex];
		} else {
			$palette = ownframework_get_theme_json_colors();
			$resolved = [
				'key' => $value,
				'value' => $palette[$value]['color'] ?? '',
				'css_var' => 'var(--wp--preset--color--' . $value . ')',
			];
		}

		return $format === 'both' ? $resolved : ($resolved[$format] ?? $resolved['key']);
	}
}

function ownframework_register_color_palette_field()
{
	new Own_Field_Color_Palette();
}
add_action('acf/include_field_types', 'ownframework_register_color_palette_field');
