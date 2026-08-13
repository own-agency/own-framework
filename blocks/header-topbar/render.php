<?php
defined('ABSPATH') || exit();

/**
 * Topbalk boven de navbar: telefoon/e-mail links, menu ("Topbalk Menu",
 * Weergave → Menu's) rechts. Rendert niets als geen van beide aanwezig is.
 * Kleuren komen uit dit block's eigen native Kleuren-paneel (block.json's
 * "supports.color"). Hoogte van de balk is bewust vast (py-2.5/lg:py-3.5),
 * geen instelbare padding.
 *
 * $phone/$email zijn gewone block-attributen (block.json), geen SCF-velden —
 * bediening via blocks/header-topbar/editor.js (native TextControls in het
 * Instellingen-paneel), net als menuAlignment/isSticky bij acf/header.
 *
 * Bewust NIET de bestaande `.topbar` utility (src/css/app.css) — die bestond
 * al voor iets anders (een balk vlak boven de footer, zie de commentaar bij
 * die class) en heeft een negative margin die specifiek `main`'s
 * padding-bottom compenseert. Hier, vóór <main>, deed diezelfde class de
 * hele balk uit zijn eigen box omhoog/omlaag schuiven — precies de
 * "topbalk past niet op het scherm"-layoutbug.
 *
 * $show_menu's eigen own-header-topbar-inner is altijd hidden lg:block: dat
 * menu staat op mobiel al in het off-canvas mobielmenu (blocks/header/parts/
 * mobile-menu.php's "Snel naar:", Ownframework_Mobile_Drilldown_Walker heeft
 * er niets mee te maken, dit is los de topbar-menu locatie) — nog eens
 * dezelfde links hier tonen zou puur duplicatie zijn. Blijft $show_contact
 * er ook niet, dan is er op mobiel na die ene wijziging niets meer over om
 * te tonen: dan verbergt $topbar_classes de HELE balk daar ook maar, in
 * plaats van een lege, nutteloze strook padding.
 *
 * --own-header-topbar-text (hieronder, alleen als er echt een eigen
 * tekstkleur gekozen is) bestaat zodat "Snel naar:" (own-mobile-menu__
 * quicklink, header.css) diezelfde kleur kan overnemen — het zijn precies
 * dezelfde topbar-menu-items, alleen op mobiel getoond via het off-canvas
 * menu in plaats van deze balk. Gewone CSS-overerving volstaat daar niet:
 * acf/header-topbar en acf/header (dat off-canvas menu's eigen consument)
 * zijn LOS van elkaar staande blocks in parts/header.html, geen ouder/kind
 * — er is geen gedeelde voorouder waar deze custom property anders vanaf
 * zou kunnen overerven. :root, niet dit block's eigen wrapper, is de enige
 * plek die beide bereikt. Zonder eigen keuze blijft --own-header-topbar-
 * text ongezet, dus header.css's eigen var(..., fallback) op de oude,
 * primary-preset-kleur terugvalt — geen gedragsverandering voor een site
 * die deze balk nooit een eigen kleur heeft gegeven.
 *
 * "align" (plain attribute + eigen SelectControl, editor.js — GEEN native
 * toolbar-control, block.json's "supports.align" is false, zie editor.js's
 * eigen docblock voor waarom die hier nooit "wide" aanbiedt) kiest alleen de
 * breedte van de INHOUD, niet van deze balk zelf: de balk (dit block's
 * wrapper) is een template part, buiten elke is-layout-constrained-voorouder
 * om, dus hij beslaat al vanzelf de volle schermbreedte — een
 * achtergrondkleur via het native "Kleuren"-paneel loopt daardoor al door
 * tot de randen, zonder acf/hero/acf/cta's eigen full-bleed-truc nodig te
 * hebben. $container_class hieronder kiest tussen src/css/app.css's
 * ".content-width" (default)/".wide-width" (align "wide", nu de default)/
 * ".full-width" (align "full", het derde, breedste niveau — zie CLAUDE.md's
 * "Alignment, width and full-bleed backgrounds") — alle drie zonder eigen
 * padding, dat komt via ".gutter-x" op $topbar_classes hieronder (de
 * BUITENSTE, al edge-to-edge wrapper) in plaats van op deze binnenste box:
 * zie ".gutter-x"'s eigen commentaar in app.css voor waarom dat niet
 * hetzelfde is als padding-inline op de binnenste box zelf (zoals het oudere
 * ".container" nog doet voor acf/hero/acf/cta's eigen legacy align:"full"
 * geval) — op brede viewports zou dat de inhoud hier verder naar binnen
 * duwen dan bij acf/hero/acf/cta.
 */
$phone = trim($block['phone'] ?? '');
$email = trim($block['email'] ?? '');
$show_contact = $phone !== '' || $email !== '';
$show_menu = has_nav_menu('topbar-menu');

if (!$show_contact && !$show_menu) {
	return '';
}

if (!empty($block['style']['color']['text'])) {
	$topbar_text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
	$topbar_text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
	$topbar_text_value = '';
}

$align = $block['align'] ?? 'wide';
$container_class = match ($align) {
    'full' => 'full-width',
    'wide' => 'wide-width',
    default => 'content-width',
} . ' flex items-center';
if ($show_contact && $show_menu) {
	$container_class .= ' justify-between gap-4';
} elseif ($show_menu) {
	$container_class .= ' justify-end';
}

$topbar_classes = 'own-header-topbar not-prose gutter-x';
if ($show_menu && !$show_contact) {
	$topbar_classes .= ' hidden lg:block';
}
?>
<?php if ($topbar_text_value !== '') : ?>
<style><?= sprintf(':root{--own-header-topbar-text:%s;}', esc_html($topbar_text_value)); ?></style>
<?php endif; ?>
<div <?= get_block_wrapper_attributes(['class' => $topbar_classes]); ?>>
	<div class="<?= esc_attr($container_class); ?>">
		<?php if ($show_contact) : ?>
			<div class="own-header-topbar-contact flex flex-wrap items-center gap-x-4 gap-y-1 text-sm py-2! lg:py-3">
				<?php if ($phone !== '') : ?>
					<a href="tel:<?= esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>" class="inline-flex items-center gap-1.5 hover:underline!">
						<?= ownframework_get_icon('phone', 'w-4 h-4'); ?>
						<?= esc_html($phone); ?>
					</a>
				<?php endif; ?>

				<?php if ($email !== '') : ?>
					<a href="mailto:<?= esc_attr($email); ?>" class="inline-flex items-center gap-1.5 hover:underline!">
						<?= ownframework_get_icon('mail', 'w-4 h-4'); ?>
						<?= esc_html($email); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ($show_menu) : ?>
			<div class="own-header-topbar-inner hidden lg:block py-2.5 lg:py-3.5">
				<?php
				wp_nav_menu([
					'theme_location' => 'topbar-menu',
					'container' => false,
					'fallback_cb' => false,
					'menu_class' => 'own-topbar-menu flex flex-wrap items-center gap-y-3 lg:gap-y-4',
					'li_class' => 'own-topbar-menu__item text-sm font-medium px-3 lg:px-4 hover:underline!',
				]);
				?>
			</div>
		<?php endif; ?>
	</div>
</div>