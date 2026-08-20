<?php
defined('ABSPATH') || exit();

/**
 * Voordeel — één kaart binnen acf/voordelen: icoon naast de kop, tekst
 * eronder ingesprongen tot onder de kop (niet onder het icoon) — zie
 * ".own-voordeel-content" (src/css/components/voordelen.css) voor hoe.
 * "ancestor": ["acf/voordelen"] (block.json) houdt dit block overal buiten
 * de inserter behalve binnen zijn ouder — declaratief, geen aparte
 * "allowed_block_types_all"-hook nodig zoals de blocks/verhaal-*-hooks/
 * restrict-to-*.php-bestanden dat nog wel moesten doen (die restricten op
 * een specifiek TEMPLATE, niet op een ouder-block — "ancestor" bestaat
 * precies voor dit eenvoudigere geval, zelfde mechanisme als acf/proces-stap
 * al gebruikt).
 *
 * Het icoon is een ECHT genest "core/icon"-block (WordPress 7.1's eigen
 * Icoon-blok, met de nieuwe iconen-picker die per-collectie groepeert) —
 * geen eigen ACF-veld/attribute/React-picker: dat block heeft zelf al een
 * native achtergrond-/tekst-/rand-/breedte-/paddingpaneel, dus er valt hier
 * niets meer zelf te bouwen. De initiële template hieronder zet alvast een
 * verzorgde default (achtergrond "Primair", icoonkleur "Basis", afgeronde
 * hoeken, wat padding, EN een gekozen icoon + breedte) zodat een net
 * ingevoegd voordeel er meteen zichtbaar en als een "badge" uitziet — een
 * editor vervangt het icoon zelf gewoon via de native picker; dit is alleen
 * een startpunt, geen verplichte keuze.
 *
 * "icon": "core/star-filled" — geverifieerd tegen de daadwerkelijk
 * geïnstalleerde WordPress-core op deze site (wp-includes/assets/
 * icon-library-manifest.php): iconnamen zijn "collectie/icoon", "core" is
 * WordPress' EIGEN ingebouwde standaardcollectie — een andere, losstaande
 * iconenset dan dit theme's eigen media/icons/*.svg +
 * ownframework_get_icon() (die twee kennen elkaar niet). Zonder een geldig
 * "icon"-attribuut rendert core/icon HELEMAAL NIETS
 * (render_block_core_icon() in WordPress' eigen broncode: "if (empty(
 * $attributes['icon'])) return;") — dat was de oorzaak van het onzichtbare
 * icoon: een lege "icon" is niet "geen icoon getoond", het is "dit block
 * rendert een lege string".
 *
 * "style.dimensions.width" (niet een los top-level "width"-attribuut, ook
 * geverifieerd tegen WordPress' eigen render-code) is wat het icoon
 * daadwerkelijk een zichtbare grootte geeft — zonder dit attribuut blijft
 * de SVG op zijn intrinsieke (vaak zeer kleine) eigen grootte hangen, wat
 * hier als "niet zichtbaar" oogde. "32px", passend bij de "0.75rem"
 * padding rondom (samen ±56px totale badge-grootte).
 *
 * Geen "core/image"/"core/buttons" in allowedBlocks: een voordeel is een
 * icoon + korte uitleg, geen kaart met eigen media/CTA (zelfde afbakening
 * als acf/proces-stap's eigen docblock voor exact dezelfde reden).
 *
 * De inhoud zelf (icoon, kop, tekst) is InnerBlocks, geen los veld — zie
 * acf/hero's eigen docblock voor de volle uitleg van SCF's
 * <InnerBlocks />-vervang-truc.
 */

$content_inner_blocks = '<InnerBlocks className=\'own-voordeel-content\' template=\'[["core/icon",{"icon":"core/star-filled","backgroundColor":"primary","textColor":"base","style":{"border":{"radius":"0.75rem"},"spacing":{"padding":{"top":"0.75rem","right":"0.75rem","bottom":"0.75rem","left":"0.75rem"}},"dimensions":{"width":"32px"}}}],["core/heading",{"level":3,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Beschrijving…"}]]\' allowedBlocks=\'["core/icon","core/heading","core/paragraph"]\' templateLock=\'false\' />';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-voordeel',
]);
?>
<div <?= $wrapper_attributes; ?>>
    <?= $content_inner_blocks; ?>
</div>
