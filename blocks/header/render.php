<?php
defined('ABSPATH') || exit();

/**
 * "Eenvoudig" header: logo, primary-menu met dropdowns (3 niveaus, het 3e
 * niveau als side-flyout), zoek-toggle, CTA-knop en mobielmenu-toggle. Eén
 * selecteerbaar block — net als acf/header-topbar ervoor (parts/header.html)
 * — met eigen native achtergrond-/tekstkleur (block.json's "supports.color").
 * Hoogte van de balk is bewust vast (py-2.5/lg:py-3.5), geen instelbare
 * padding. Linkhoverkleur is bewust niet instelbaar: vast op de site z'n
 * primaire kleur (var(--wp--preset--color--primary)), niet een los veld.
 *
 * Logo (core/site-logo) en CTA (core/buttons › core/button) zijn beide echte
 * geneste blocks — InnerBlocks hieronder, native SCF-functionaliteit (zie
 * includes/blocks.php in het secure-custom-fields-plugin) — zodat een editor
 * het logo en de knop(pen) met hun eigen native controls bewerkt, niet via
 * een ACF-veld. Eén SCF-block ondersteunt maar één InnerBlocks-gebied (meer
 * dan één <InnerBlocks>-tag geeft overal dezelfde inhoud terug), dus logo én
 * CTA delen noodgedwongen hetzelfde gebied. `.own-header-inline-blocks`
 * (display:contents, header.css) maakt die gedeelde wrapper onzichtbaar voor
 * de flex-layout, zodat de twee geneste blocks zich gedragen als 2
 * onafhankelijke flex-items van .own-header zelf — logo links, CTA rechts,
 * met .own-header-nav-portion (order via CSS) ertussenin.
 *
 * templateLock="insert": geen blocks toevoegen/verwijderen (voorkomt dat
 * logo of CTA per ongeluk weg is zonder duidelijke manier om het terug te
 * zetten), maar wél vrij verplaatsbaar en met alle eigen native controls
 * (tekst, link, kleur, afbeelding) — CSS `order` bepaalt de visuele volgorde
 * toch al, niet de DOM-volgorde, dus verplaatsen kan niets breken.
 *
 * $menu_alignment, $is_sticky, $is_transparent_until_scroll en
 * $show_scroll_shadow komen uit gewone block-attributen (block.json), geen
 * SCF-velden — puur visuele/gedragskeuzes zonder inhoud, dus die horen bij
 * de rest van dit block's native instellingen (uitlijning, kleur) i.p.v. in
 * de SCF-veldenmodal. Bediening via blocks/header/editor.js (native
 * SelectControl/ToggleControls in het Instellingen-paneel).
 *
 * $is_transparent_until_scroll zet de <header>'s eigen background-color naar
 * transparent (!important, zie .own-header--transparent in header.css —
 * nodig omdat WP's eigen kleur-support die achtergrond als class óf inline
 * style kan renderen, met wisselende specificity); de dropdowns lezen nog
 * altijd de echte gekozen kleur via --own-header-bg, dus die blijven zelf
 * altijd degelijk gevuld.
 *
 * $transparent_text_color (editor.js, geen SCF-veld — zelfde reden als
 * hierboven) is een TWEEDE, eigen tekstkleur die alléén geldt zolang de
 * header nog transparant/niet-gescrold is; zodra own-header--scrolled actief
 * wordt, valt de tekst vanzelf terug op de normale, native tekstkleur (WP's
 * eigen kleur-support, altijd al aanwezig als class/inline style op
 * <header>) — geen aparte "scrolled"-variant nodig, want :not(.own-header--
 * scrolled) in header.css stopt dan gewoon met matchen. Ook als
 * --own-header-transparent-text custom property (net als --own-header-bg),
 * niet als losse class: zo kan de editor elke kleur uit het native palet
 * kiezen (editor.js zoekt de gekozen swatch terug op naar zijn slug en slaat
 * `var(--wp--preset--color--<slug>)` op, niet de losse hexwaarde — blijft zo
 * dynamisch als de client die kleur later via Ontwerp aanpast) zonder dat
 * hier een aparte preset-class-naam voor hoeft te bestaan. Alleen relevant
 * (en alleen getoond in de Instellingen) als $is_transparent_until_scroll
 * aan staat — zonder transparante header is er nooit een "startkleur" om
 * tijdelijk te tonen.
 *
 * De header is verder gewoon position:relative — own-header--transparent
 * (header.css) geeft 'm daarnaast een negatieve margin-bottom ter grootte
 * van zijn eigen hoogte (--site-header-height, header.js), die de
 * eerstvolgende echte pagina-content omhoog trekt tot onder de header —
 * anders toont een transparante achtergrond alleen wat er in de normale
 * flow achter zit (niets, visueel), niet die content. Werkt pas goed sinds
 * header:has(> .own-header-topbar) (header.css) de omringende <header>-tag
 * van de template part (templates/*.html's "tagName":"header") display:
 * contents geeft — anders is DIE tag, niet de pagina zelf, waar deze margin
 * tegenaan botst. own-header--sticky-on-scroll (alleen als ook $is_sticky)
 * zet 'm meteen op position:sticky (header.css), niet pas ná scrollen —
 * sticky rendert vanzelf identiek aan relative zolang de header zijn eigen
 * rustpositie nog niet voorbij is gescrold, dus dat verandert niets aan het
 * beginbeeld. Beide (de margin én de sticky) staan bewust altijd aan, nooit
 * gekoppeld aan own-header--scrolled: zou een van beide pas ná scrollen
 * inschakelen, dan verandert de totale paginahoogte precies op het
 * scroll-omslagpunt — en dat omslagpunt ligt altijd vlak bij de bovenkant
 * van de pagina, dus precies waar de viewport op dat moment ook staat. De
 * content sprong daardoor zichtbaar omhoog/omlaag op exact dat punt, vooral
 * merkbaar bij terugscrollen naar boven. Zonder $is_sticky blijft de header
 * gewoon relative en scrolt 'm normaal mee weg, wél nog steeds over de
 * content heen vanaf het begin.
 *
 * $menu_alignment bepaalt alleen waar het menu tussen logo en zoeken/CTA komt
 * te staan: "start" (naast het logo), "center" (absolute-positioning-based,
 * zie own-header-nav-portion--center in header.css) of "end" (naast
 * zoeken/CTA). Logo blijft links, zoeken/CTA blijven rechts in alle
 * gevallen.
 *
 * $use_mega_menu ("Type submenu", editor.js) is een header-brede keuze —
 * niet per menu-item — tussen de bestaande dropdown (default) en een
 * uitklapmenu over de volledige breedte, bedoeld voor hoofditems met meerdere
 * gegroepeerde subitems (zie own-header-megamenu in header.css). Zelfde
 * wp_nav_menu()-aanroep, 3 niveaus diep in beide gevallen — alleen de
 * li_class_N/a_class_N/submenu_atts_N-argumenten verschillen (generiek
 * opgelost door inc/functions/nav-menu-classes.php, geen aparte Walker
 * nodig): 2e-niveau items worden kolomtitels (own-header-megamenu__column-
 * title) i.p.v. dropdown-items, hun eigen submenu (own-header-megamenu__
 * column-links) wordt een gewone, altijd-zichtbare lijst i.p.v. de zijwaartse
 * own-header-submenu--flyout — een kolom se links horen nooit onafhankelijk
 * van hun kolom te verbergen/tonen. 3e-niveau items hergebruiken gewoon de
 * bestaande own-header-submenu__item/__link-classes: een platte linklijst
 * ziet er in beide varianten identiek uit. Alleen relevant vanaf $is_sticky
 * of $is_transparent_until_scroll onafhankelijk: own-header-megamenu
 * positioneert zichzelf altijd tegen déze <header> (inset-inline:0), dus
 * $header_classes krijgt hieronder altijd minstens 'relative', ook in de
 * combinatie zonder sticky én zonder transparant, waar dat voorheen nergens
 * voor nodig was.
 *
 * $mega_menu_bg_color/$mega_menu_title_color/$mega_menu_text_color ("Kleuren
 * uitklapmenu", editor.js) zijn alleen zichtbaar/relevant zolang $use_mega_menu
 * aan staat — de dropdown heeft geen equivalent, die volgt gewoon de header
 * z'n eigen native achtergrond-/tekstkleur al. Zelfde patroon als
 * $transparent_text_color hierboven: als custom properties op <header> gezet
 * (niet inline op de own-header-megamenu <ul> zelf, ook al zou dat via
 * submenu_atts_0's 'style' net zo goed kunnen — alle header-brede kleurvars
 * bij elkaar op één element houden is consistenter), met een fallback in
 * header.css's var()-aanroep zelf (niet hier in PHP) op wat er zonder deze
 * instelling al stond: de header z'n eigen --own-header-bg voor de
 * achtergrond, de vaste primary-preset voor de kolomtitel, `inherit` voor de
 * linktekst. Leeg gelaten (default) verandert er dus zichtbaar niets t.o.v.
 * vóór deze instelling bestond.
 *
 * $show_scroll_shadow drijft mee op dezelfde x-data als het mobielmenu/
 * zoekmodal (Alpine, al op dit element). $scroll_expr's vergelijking staat
 * gewoon inline (geen apart .js-bestand nodig) — MAAR altijd via esc_attr()
 * uitgevoerd, nooit direct in de HTML geplakt: een kale ">" in een
 * HTML-attribuutwaarde is voor wptexturize() onzichtbaar als "binnen een
 * quote" (het splitst tag-vs-tekst met een naïeve <.*?>-regex), en mangelt
 * daardoor elke straight quote na die ">" tot een curly quote — precies wat
 * hier eerder de hele header-markup corrumpeerde. esc_attr() zet die ">" om
 * naar de entity `&gt;`, wat wptexturize() niet als "een tag sluiten"
 * herkent, en wat de browser bij het uitlezen van het attribuut weer
 * gewoon terugvertaalt naar een letterlijke ">" voor Alpine.
 *
 * $scroll_expr's drempel is altijd de topbalk z'n eigen, live hoogte
 * (document.querySelector('.own-header-topbar')?.offsetHeight), niet een
 * vast pixelgetal — óók zonder $is_transparent_until_scroll: een gewone
 * sticky header begint pas écht "te zweven" zodra er voorbij zijn eigen
 * rustpositie (net onder de topbalk) is gescrold, en met een vast getal zou
 * de schaduw (bij $show_scroll_shadow) al verschijnen terwijl de header nog
 * gewoon op zijn normale, niet-vastgeplakte plek staat. Zonder topbalk
 * (querySelector geeft null) valt dat vanzelf terug op 20.
 *
 * $header_bg_value wordt expliciet als --own-header-bg custom property op
 * <header> gezet, niet als "background-color:inherit" op de dropdowns
 * verderop: inherit kopieert alleen van de DIRECTE DOM-ouder, en geen van de
 * <ul>/<li>/<div>'s tussen een dropdown en <header> zet zelf een
 * background-color — dus inherit gaf daar gewoon transparent, en de
 * dropdown werd zichtbaar transparant (header erachter waar hij over de
 * header heen valt, paginawebsite-achtergrond waar hij eroverheen valt op
 * de rest van de content). Een custom property cascadeert wél door elke
 * tussenliggende laag heen, ongeacht of die zelf iets zet. Tekstkleur heeft
 * dit niet nodig: color erft van nature al door de hele boom, in
 * tegenstelling tot background-color.
 *
 * $cta_button_html/$logo_html (blocks/header/parts/mobile-menu.php's
 * $args['cta_button']/$args['logo']) geven het off-canvas mobielmenu
 * dezelfde, echte core/button en core/site-logo die de desktop-header al
 * toont — zie $wp_block hieronder voor hoe. Zo blijft het paneel z'n eigen
 * logo altijd exact even groot als het logo in de balk erboven, ook nadat
 * een editor de breedte-slider aanpast, zonder een los pixelgetal hier of
 * daar dat uit sync kan raken.
 *
 * "align" (plain attribute + eigen SelectControl, editor.js — GEEN native
 * toolbar-control, block.json's "supports.align" is false, zie editor.js's
 * eigen docblock voor waarom die hier nooit "wide" aanbiedt) kiest alleen de
 * breedte van $container_class, niet van de header zelf: deze <header> is
 * een template part buiten elke is-layout-constrained-voorouder om, dus die
 * is al vanzelf edge-to-edge — een achtergrondkleur via het native
 * "Kleuren"-paneel loopt daardoor al door tot de randen, zonder acf/hero/
 * acf/cta's eigen full-bleed-truc nodig te hebben. $container_class kiest
 * tussen src/css/app.css's ".content-width" (default)/".wide-width" (align
 * "wide", nu de default)/".full-width" (align "full", het derde,
 * breedste niveau — zie CLAUDE.md's "Alignment, width and full-bleed
 * backgrounds") — alle drie zonder eigen padding, dat komt via ".gutter-x"
 * op $header_classes hieronder (de BUITENSTE <header> zelf) in plaats van op
 * deze binnenste box: zie ".gutter-x"'s eigen commentaar in app.css voor
 * waarom dat niet hetzelfde is als padding-inline op de binnenste box zelf
 * (zoals het oudere ".container" nog doet voor acf/hero/acf/cta's eigen
 * legacy align:"full" geval) — op brede viewports zou dat de inhoud hier
 * verder naar binnen duwen dan bij acf/hero/acf/cta.
 */
$menu_alignment = $block['menuAlignment'] ?? 'center';
$use_mega_menu = ($block['submenuStyle'] ?? 'dropdown') === 'megamenu';
$is_sticky = ($block['isSticky'] ?? true) !== false;
$is_transparent_until_scroll = !empty($block['transparentUntilScroll']);
$show_scroll_shadow = !empty($block['showScrollShadow']);
$align = $block['align'] ?? 'wide';
$container_class = match ($align) {
    'full' => 'full-width',
    'wide' => 'wide-width',
    default => 'content-width',
};
$needs_scroll_state = $is_transparent_until_scroll || $show_scroll_shadow;

if (!empty($block['style']['color']['background'])) {
	$header_bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
	$header_bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
	$header_bg_value = 'transparent';
}

// Same pattern as $header_bg_value above, for the off-canvas mobile menu's
// own --own-header-text read (header.css): that panel always renders on the
// header's REAL, solid background regardless of $is_transparent_until_scroll,
// so it needs the header's actual native text colour independently of
// $transparent_text_color (which only applies while still unscrolled) —
// capturing it here, rather than reading WP's own colour support back off
// <header> in CSS, since there's no selector for "this block's own native
// text colour" once $transparent_text_color's !important override (below)
// is also targeting the same element.
if (!empty($block['style']['color']['text'])) {
	$header_text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
	$header_text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
	$header_text_value = '';
}

$transparent_text_color = trim($block['transparentTextColor'] ?? '');
$mega_menu_bg_color = trim($block['megaMenuBackgroundColor'] ?? '');
$mega_menu_title_color = trim($block['megaMenuTitleColor'] ?? '');
$mega_menu_text_color = trim($block['megaMenuTextColor'] ?? '');

// $wp_block is the 5th arg secure-custom-fields' acf_block_render_template()
// passes to render.php (it include()s this file from inside its own
// function, so render.php inherits every one of its local vars — see
// includes/blocks.php in the plugin). Its ->inner_blocks is the REAL parsed
// block tree (WP_Block instances, not the flattened HTML string $content
// also holds), so the mobile menu's own CTA and logo can render the SAME
// editor-configured core/button/core/site-logo the desktop header already
// shows — same text/link/native colour for the button, same width-slider
// size for the logo — without duplicating any of that as separate
// attributes on this block, or (for the logo) hardcoding a pixel size here
// that could drift out of sync with whatever the editor picked. Deliberately
// the single core/button itself, not its core/buttons wrapper: that
// wrapper's own className ("hidden lg:flex", InnerBlocks template below)
// exists purely to hide the CTA from the DESKTOP row below lg — reusing the
// wrapper verbatim would carry that same "hidden" class into the mobile
// panel, where it would just never show at all.
$cta_button_html = '';
$logo_html = '';
if ($wp_block instanceof WP_Block) {
	foreach ($wp_block->inner_blocks as $inner_block) {
		if ($inner_block->name === 'core/site-logo') {
			$logo_html = $inner_block->render();
		} elseif ($inner_block->name === 'core/buttons') {
			foreach ($inner_block->inner_blocks as $button_block) {
				if ($button_block->name === 'core/button') {
					$cta_button_html = $button_block->render();
					break;
				}
			}
		}
	}
}

$nav_portion_class = 'own-header-nav-portion flex-1 gap-4';
switch ($menu_alignment) {
	case 'center':
		$nav_portion_class .= ' own-header-nav-portion--center';
		break;
	case 'end':
		$nav_portion_class .= ' flex items-center justify-end';
		break;
	default:
		$nav_portion_class .= ' flex items-center justify-end lg:justify-between';
}

$header_classes = 'own-header not-prose gutter-x z-40';
if ($is_transparent_until_scroll) {
	$header_classes .= ' relative own-header--transparent';
	if ($is_sticky) {
		$header_classes .= ' own-header--sticky-on-scroll';
	}
} elseif ($is_sticky) {
	$header_classes .= ' sticky top-0';
} else {
	// Neither sticky nor transparent above needs this (both already set
	// their own position keyword), but $use_mega_menu's full-width panel
	// (own-header-megamenu, header.css) positions itself via inset-inline:0
	// against THIS element regardless of those settings, so it always needs
	// a real containing block here, not just in the sticky/transparent
	// branches above.
	$header_classes .= ' relative';
}
if ($needs_scroll_state) {
	$header_classes .= ' transition-[background-color,color,box-shadow] duration-200';
}

$header_style = sprintf('--own-header-bg:%s;', esc_attr($header_bg_value));
if ($is_transparent_until_scroll && $transparent_text_color !== '') {
	$header_style .= sprintf('--own-header-transparent-text:%s;', esc_attr($transparent_text_color));
}
if ($header_text_value !== '') {
	$header_style .= sprintf('--own-header-text:%s;', esc_attr($header_text_value));
}
if ($use_mega_menu) {
	if ($mega_menu_bg_color !== '') {
		$header_style .= sprintf('--own-header-megamenu-bg:%s;', esc_attr($mega_menu_bg_color));
	}
	if ($mega_menu_title_color !== '') {
		$header_style .= sprintf('--own-header-megamenu-title-color:%s;', esc_attr($mega_menu_title_color));
	}
	if ($mega_menu_text_color !== '') {
		$header_style .= sprintf('--own-header-megamenu-text-color:%s;', esc_attr($mega_menu_text_color));
	}
}

$wrapper_attributes = get_block_wrapper_attributes([
	'class' => $header_classes,
	'style' => $header_style,
]);

// searchModalOpen used to live here too, alongside mobileMenuOpen — moved to
// a global $store.searchDialog (src/js/scripts/live-search.js) once the
// search modal needed to open from a ⌘K/Ctrl+K press anywhere on the page
// (search-modal.php's own window keydown listeners), not just from a click
// inside THIS <header>'s own Alpine scope.
$x_data_parts = ['mobileMenuOpen: false'];
if ($needs_scroll_state) {
	$x_data_parts[] = 'scrolled: false';
}
$x_data = '{ ' . implode(', ', $x_data_parts) . ' }';

$scroll_class_parts = [];
if ($is_transparent_until_scroll) {
	$scroll_class_parts[] = "'own-header--scrolled': scrolled";
}
if ($show_scroll_shadow) {
	$scroll_class_parts[] = "'shadow-md': scrolled";
}
$scroll_class_attr = '{ ' . implode(', ', $scroll_class_parts) . ' }';

// Topbar-aware, not just a flat 20px, for the plain sticky+shadow case too:
// a sticky header only actually starts floating over content once scrolled
// past its own resting position (right after the topbar) — a flat pixel
// threshold would show the shadow while it's still sitting in its normal,
// non-stuck spot if the topbar happens to be taller than that. Falls back
// to 20 by itself when there's no topbar (querySelector returns null).
$scroll_threshold_expr = "document.querySelector('.own-header-topbar')?.offsetHeight || 20";
$scroll_expr = "scrolled = window.scrollY > ($scroll_threshold_expr)";
?>

<header <?= $wrapper_attributes; ?> x-data="<?= esc_attr($x_data); ?>" <?php if ($needs_scroll_state) : ?> @scroll.window="<?= esc_attr($scroll_expr); ?>" :class="<?= esc_attr($scroll_class_attr); ?>" <?php endif; ?>>

	<div class="<?= esc_attr($container_class); ?> relative flex items-center gap-4 py-2.5 lg:py-3.5">
		<InnerBlocks
			className="own-header-inline-blocks"
			template="[[&quot;core/site-logo&quot;],[&quot;core/buttons&quot;,{&quot;className&quot;:&quot;hidden lg:flex&quot;},[[&quot;core/button&quot;,{&quot;text&quot;:&quot;Neem contact op&quot;}]]]]"
			allowedBlocks="[&quot;core/site-logo&quot;,&quot;core/buttons&quot;]"
			templateLock="insert" />

		<div class="<?= esc_attr($nav_portion_class); ?>">
			<div class="hidden lg:block">
				<?php
				$primary_menu_args = [
					'theme_location' => 'primary-menu',
					'container' => false,
					'fallback_cb' => false,
					'depth' => 3,
					'own_show_chevrons' => true,
					'menu_class' => 'own-header-menu flex items-center gap-x-6',
					'li_class_0' => 'own-header-menu__item relative',
					'a_class_0' => 'inline-flex items-center gap-x-1.5 py-2 text-sm font-medium hover:text-(--wp--preset--color--primary) transition-colors',
				];

				if ($use_mega_menu) {
					// Depth-1 items become column headings and depth-2 items
					// the links under them — reusing the SAME 3-level
					// wp_nav_menu() depth this theme already renders for the
					// dropdown, just with different classes/attrs per level
					// (nav-menu-classes.php resolves li_class_N/a_class_N/
					// submenu_atts_N generically, so no walker change is
					// needed for either style). own-header-megamenu (header.
					// css) is the full-width panel that replaces
					// own-header-submenu--dropdown; own-header-megamenu__
					// column-links is a plain, always-visible list (once the
					// panel itself is open) that replaces the sideways
					// own-header-submenu--flyout — a column's links were
					// never meant to hide/reveal independently of their
					// column. Depth-2 reuses the dropdown's own item/link
					// classes as-is: a plain link list looks identical
					// either way. own_chevron_max_depth suppresses the
					// depth>0 chevron-right (nav-chevrons.php) that dropdown
					// mode shows to hint "opens sideways" — column titles
					// don't open anything themselves.
					$primary_menu_args['own_chevron_max_depth'] = 0;
					$primary_menu_args['submenu_atts_0'] = ['class' => 'own-header-megamenu'];
					$primary_menu_args['li_class_1'] = 'own-header-megamenu__column';
					$primary_menu_args['a_class_1'] = 'own-header-megamenu__column-title';
					$primary_menu_args['submenu_atts_1'] = ['class' => 'own-header-megamenu__column-links'];
					$primary_menu_args['li_class_2'] = 'own-header-submenu__item';
					$primary_menu_args['a_class_2'] = 'own-header-submenu__link';

					// header.css's own color rules for these two classes lose
					// to WP's per-block "Links" colour support: that feature
					// renders a `.wp-elements-<hash> a:where(...) { color: ... }`
					// rule that targets EVERY <a> in the header directly (not
					// via inheritance), at higher specificity than a plain
					// single class — the exact same conflict the transparent-
					// header text colour setting hit earlier, just for a
					// different pair of links. An inline style here sidesteps
					// it without needing !important: a real style="" attribute
					// always outranks any stylesheet rule regardless of that
					// rule's own specificity. Only set when the editor actually
					// picked a colour, so leaving it blank still falls through
					// to that native Links colour exactly as before.
					if ($mega_menu_title_color !== '') {
						$primary_menu_args['a_atts_1'] = ['style' => "color:{$mega_menu_title_color};"];
					}
					if ($mega_menu_text_color !== '') {
						$primary_menu_args['a_atts_2'] = ['style' => "color:{$mega_menu_text_color};"];
					}
				} else {
					$primary_menu_args['submenu_atts_0'] = ['class' => 'own-header-submenu own-header-submenu--dropdown'];
					// relative: makes THIS row (not the whole dropdown panel)
					// the positioning context for its own 3rd-level flyout —
					// see own-header-submenu--flyout's own docblock (header.css).
					$primary_menu_args['li_class_1'] = 'own-header-submenu__item relative';
					$primary_menu_args['a_class_1'] = 'own-header-submenu__link';
					$primary_menu_args['submenu_atts_1'] = ['class' => 'own-header-submenu own-header-submenu--flyout'];
					$primary_menu_args['li_class_2'] = 'own-header-submenu__item';
					$primary_menu_args['a_class_2'] = 'own-header-submenu__link';
				}

				wp_nav_menu($primary_menu_args);
				?>
			</div>

			<?php // -me-2 cancels the last button's p-2, so the icon itself lines
			// up with the content edge while the tap area keeps its size —
			// same as the close button in parts/mobile-menu.php. Not from lg:
			// there the CTA button sits at the edge, not these icons.
			?>
			<div class="flex items-center gap-x-2 -me-2 lg:me-0">
				<button
					@click="$store.searchDialog.open()"
					type="button"
					class="own-header-icon-btn inline-flex items-center justify-center rounded-lg p-2"
					aria-label="<?= esc_attr__('Zoeken', 'own-framework'); ?>">
					<?= ownframework_get_icon('search', 'w-5 h-5'); ?>
				</button>

				<button
					@click="mobileMenuOpen = !mobileMenuOpen"
					type="button"
					class="own-header-burger lg:hidden inline-flex items-center justify-center p-2"
					:aria-expanded="mobileMenuOpen.toString()"
					aria-label="<?= esc_attr__('Menu openen', 'own-framework'); ?>">
					<span x-show="!mobileMenuOpen" x-transition.opacity><?= ownframework_get_icon('menu', 'w-5 h-5'); ?></span>
					<span x-show="mobileMenuOpen" x-transition.opacity x-cloak><?= ownframework_get_icon('close', 'w-5 h-5'); ?></span>
				</button>
			</div>
		</div>
	</div>

	<?php get_template_part('blocks/header/parts/mobile-menu', null, ['cta_button' => $cta_button_html, 'logo' => $logo_html]); ?>
</header>

<?php get_template_part('blocks/header/parts/search-modal'); ?>