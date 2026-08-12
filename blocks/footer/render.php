<?php
defined('ABSPATH') || exit();

/**
 * Footer: logo, korte tekst, CTA-kaart, footermenu en contact/social-links —
 * één ECHTE core/columns/core/column-structuur (3 kolommen), samen met acf/
 * footer-bottom (de copyright-balk) het volledige parts/footer.html.
 *
 * Dit block heeft zelf GEEN eigen attributen meer: alles wat vroeger een
 * block-attribuut was (CTA-tekst, menukeuze, contactgegevens) is nu een
 * eigen, genest block (acf/footer-cta, acf/footer-menu, acf/footer-contact).
 * Eén SCF-block ondersteunt maar één InnerBlocks-gebied (zie ook acf/
 * header's eigen render.php-comment) — dat is de enige manier om zowel
 * STATISCHE, klikbaar-bewerkbare content (logo/tekst/titels, core/paragraph
 * + core/heading) als DYNAMISCHE, PHP-berekende content (CTA/menu/contact)
 * in DEZELFDE 3-koloms core/columns-structuur te krijgen: PHP-output kan
 * niet los tussen InnerBlocks-kinderen gesplitst worden, het moet zelf een
 * echt geregistreerd block zijn om als kind van core/column te bestaan. Een
 * eerdere versie van dit bestand rendere de dynamische content daarom apart,
 * in een 2e, losse rij ónder de echte kolommen — visueel bijna identiek,
 * maar 2 losse grids die handmatig gesynchroniseerd moesten blijven (zelfde
 * omslagbreedte etc.). Die synchronisatie is hier niet meer nodig: alles
 * zit in ÉÉN core/columns, dat zijn eigen stapelgedrag zelf al jarenlang
 * beproefd regelt (flexbox, stapelt zelf al onder 782px — wp-includes/
 * blocks/columns/style.css) — precies de oorzaak van de "massive gaps"-bug
 * die deze structuur oplost was CSS Grid's auto-plaatsingscursor, die alleen
 * VOORUIT beweegt: kolom 1's eigen rijen duwden 'm voorbij waar kolom 2/3
 * 'm nodig hadden.
 *
 * Lock-niveaus zitten nu op 3 plekken, elk met een eigen reden:
 *
 * 1. Dit InnerBlocks-gebied zelf: templateLock={false} — geen contentOnly
 *    meer, want naast de ÉNE core/columns mag een editor er nu OOK een 2e,
 *    losse sectie ONDER de kolommen bij zetten (own-footer-below-columns,
 *    footer.css) — bv. een los, groot logo gecentreerd onder een 3/4-koloms
 *    layout, zoals sommige klanten 'm willen (geen vaste logo-kolom nodig
 *    daarvoor). Leeg/optioneel: er staat niets in totdat een editor zelf
 *    core/site-logo of core/group hier invoegt. allowedBlocks hieronder
 *    laat daarom naast "core/columns" ook "core/site-logo"/"core/group" toe
 *    — een editor "sleept het logo er gewoon in" i.p.v. eerst een kolom of
 *    group te moeten aanmaken.
 * 2. core/columns krijgt nu zelf "lock":{"move":false,"remove":false} —
 *    nodig zodra punt 1 niet meer contentOnly is: zonder die lock zou de
 *    HELE kolommenrij nu ook verwijderd/verplaatst kunnen worden, wat nooit
 *    de bedoeling was van het loskoppelen hierboven. Los daarvan, zijn eigen
 *    "templateLock":false (core/columns/core/column ondersteunen dit
 *    attribuut zelf, wp-includes/blocks/columns/block.json) — een block's
 *    eigen templateLock overschrijft de geërfde lock van de ouder specifiek
 *    voor ZIJN kolommen, dus dit ontgrendelt het toevoegen/verwijderen/
 *    herordenen van kolommen zelf, los van punt 1's "mag er een sectie
 *    bij"-vraag. Zonder dit zou een editor nooit een 2e "Menu"-kolom kunnen
 *    toevoegen voor een layout met meer linkkolommen (Mollie-achtig).
 * 3. Elke core/column krijgt zijn eigen, losse "templateLock":"contentOnly"
 *    terug (punt 2's ontgrendeling geldt alleen VOOR core/columns, niet
 *    automatisch ook binnen elke kolom) — voorkomt dat een editor een
 *    willekeurig ander block ín een kolom zet, ook al zijn de kolommen zelf
 *    nu vrij. De logo/CTA- en contactkolom krijgen bovendien hun eigen
 *    "lock":{"move":false,"remove":false}: die twee blijven vast op hun
 *    plek, alleen de middelste "Menu"-kolom (géén eigen "lock") is vrij te
 *    verplaatsen/verwijderen/dupliceren — zo ontstaan meerdere menukolommen
 *    door de bestaande te dupliceren (elk met zijn eigen menukeuze via acf/
 *    footer-menu's menuId), niet door de hele structuur open te zetten.
 * acf/footer-cta krijgt daarbovenop nog steeds expliciet "lock":
 * {"remove":false}: overschrijft de contentOnly-lock van zijn eigen kolom
 * (punt 3) specifiek voor dít block, dus alléén de CTA-kaart kan ook los
 * verwijderd (en later opnieuw toegevoegd) worden — geen apart "toon
 * CTA-kaart"-schakeltje nodig, het block zelf toevoegen/verwijderen IS de
 * schakelaar.
 *
 * Bestaande, al opgeslagen footers van vóór deze wijziging (losse CTA-/
 * menu-/contact-attributen op DIT block) worden hier niet meer gelezen — die
 * instellingen moeten 1x opnieuw ingevuld worden in de nieuwe, genestte
 * blocks.
 *
 * "align" (plain attribute + eigen SelectControl, editor.js — GEEN native
 * toolbar-control, block.json's "supports.align" is false, zie editor.js's
 * eigen docblock voor waarom die hier nooit "wide" aanbiedt) kiest alleen de
 * breedte van $container_class, niet van deze <footer> zelf: die is een
 * template part buiten elke is-layout-constrained-voorouder om, dus al
 * vanzelf edge-to-edge — --own-footer-bg hieronder loopt daardoor al door
 * tot de randen, zonder acf/hero/acf/cta's eigen full-bleed-truc nodig te
 * hebben. $container_class kiest tussen src/css/app.css's ".content-width"
 * (default) en ".wide-width" (align "wide") — beide zonder eigen padding,
 * dat komt via ".gutter-x" op de <footer>'s eigen class hieronder in plaats
 * van op deze binnenste box: zie ".gutter-x"'s eigen commentaar in app.css
 * voor waarom dat niet hetzelfde is als padding-inline op de binnenste box
 * zelf (zoals het oudere ".container" nog doet voor acf/hero/acf/cta's eigen
 * legacy align:"full" geval) — op brede viewports zou dat de inhoud hier
 * verder naar binnen duwen dan bij acf/hero/acf/cta.
 */

$align = $block['align'] ?? '';
$container_class = $align === 'wide' ? 'wide-width' : 'content-width';

if (!empty($block['style']['color']['background'])) {
	$footer_bg_value = $block['style']['color']['background'];
} elseif (!empty($block['backgroundColor'])) {
	$footer_bg_value = 'var(--wp--preset--color--' . $block['backgroundColor'] . ')';
} else {
	// Geen "transparent"-default zoals acf/header: een footer zonder eigen
	// achtergrond zou gewoon de pagina-achtergrond doorlaten, wat voor dit
	// altijd-onderaan-de-pagina block zelden bedoeld is. "base" (wit), niet
	// "contrast" (donker) zoals voorheen: die donkere default combineerde met
	// de tekstkleur hieronder (die zonder eigen keuze op "inherit" bleef
	// staan, doorgaans zelf ook donker) tot onleesbare donkere tekst op een
	// donkere achtergrond — een footer werkte dus alleen na het handmatig
	// instellen van beide kleuren.
	$footer_bg_value = 'var(--wp--preset--color--base)';
}

if (!empty($block['style']['color']['text'])) {
	$footer_text_value = $block['style']['color']['text'];
} elseif (!empty($block['textColor'])) {
	$footer_text_value = 'var(--wp--preset--color--' . $block['textColor'] . ')';
} else {
	// "contrast" (donker), niet "inherit": moet de leesbare tegenhanger van de
	// "base"-achtergrond hierboven blijven, ook op een pagina met een eigen
	// (b.v. donkere) globale tekstkleur waar "inherit" net de verkeerde kant
	// op zou vallen. own-footer-icons-color (footer.css) heeft geen eigen
	// default nodig — die valt al terug op "inherit"/"currentColor", en erft
	// deze tekstkleur dus vanzelf mee.
	$footer_text_value = 'var(--wp--preset--color--contrast)';
}

$footer_style = sprintf('--own-footer-bg:%s;--own-footer-text:%s;', esc_attr($footer_bg_value), esc_attr($footer_text_value));

$wrapper_attributes = get_block_wrapper_attributes([
	'class' => 'own-footer not-prose gutter-x',
	'style' => $footer_style,
]);
?>
<footer <?= $wrapper_attributes; ?>>
	<div class="<?= esc_attr($container_class); ?> py-12 md:py-20">
		<InnerBlocks
			className="own-footer-columns"
			template="[
				[&quot;core/columns&quot;,{&quot;lock&quot;:{&quot;move&quot;:false,&quot;remove&quot;:false},&quot;templateLock&quot;:false},[
					[&quot;core/column&quot;,{&quot;lock&quot;:{&quot;move&quot;:false,&quot;remove&quot;:false},&quot;templateLock&quot;:&quot;contentOnly&quot;},[
						[&quot;core/site-logo&quot;,{}],
						[&quot;core/paragraph&quot;,{&quot;className&quot;:&quot;own-footer-logo-text text-base max-w-xs&quot;,&quot;placeholder&quot;:&quot;Korte tekst onder het logo...&quot;}],
						[&quot;acf/footer-cta&quot;,{&quot;lock&quot;:{&quot;remove&quot;:false}}]
					]],
					[&quot;core/column&quot;,{&quot;templateLock&quot;:&quot;contentOnly&quot;},[
						[&quot;core/heading&quot;,{&quot;level&quot;:3,&quot;placeholder&quot;:&quot;Menu&quot;}],
						[&quot;acf/footer-menu&quot;,{}]
					]],
					[&quot;core/column&quot;,{&quot;lock&quot;:{&quot;move&quot;:false,&quot;remove&quot;:false},&quot;templateLock&quot;:&quot;contentOnly&quot;},[
						[&quot;core/heading&quot;,{&quot;level&quot;:3,&quot;placeholder&quot;:&quot;Contact&quot;}],
						[&quot;acf/footer-contact&quot;,{}]
					]]
				]]
			]"
			allowedBlocks="[&quot;core/columns&quot;,&quot;core/site-logo&quot;,&quot;core/group&quot;]" />
	</div>
</footer>