<?php
defined('ABSPATH') || exit();

/**
 * Kaartje — vaste titel/subtekst/afbeelding/tekst/knoppen-volgorde
 * (InnerBlocks) in een kaart met eigen achtergrondkleur, bedoeld om BINNEN
 * kolommen/containers te gebruiken (core/columns, core/group) i.p.v. als
 * eigen pagina-brede sectie zoals acf/cta — zelfde "root IS de kaart"-opzet
 * als acf/content-blok.
 *
 * Belangrijkste verschil met acf/content-blok: de afbeelding is hier een
 * ECHT genest core/image-InnerBlock, geen los "image"-attribute + eigen
 * MediaUpload-picker. Reden: SCF's <InnerBlocks />-vervang-truc kent maar
 * ÉÉN kind-lijst per block — acf_replace_inner_blocks_in_block_content()
 * (secure-custom-fields/includes/blocks.php) vervangt ELKE <InnerBlocks />-
 * match met dezelfde, volledige $content-blob, dus een tweede placeholder
 * voor "titel+subtekst boven de afbeelding" en een derde voor "tekst+knoppen
 * eronder" zou geen aparte kind-groepen opleveren, alleen dezelfde volledige
 * lijst drie keer herhalen. De gevraagde volgorde (Titel → Subtekst →
 * Afbeelding → Tekst → Knoppen) kan daarom alleen als ÉÉN doorlopende
 * InnerBlocks-lijst, met de afbeelding gewoon als een van de kinderen
 * ertussenin — en dat is meteen ook de reden om voor een echt core/image te
 * kiezen i.p.v. een eigen picker: een editor krijgt zo native crop/
 * bijschrift/alt-tekst/rand-opties gratis, dezelfde "hergebruik een al
 * gepolijste native UI"-afweging als acf/voordeel's eigen core/icon-keuze.
 *
 * "imageFullWidth" laat de afbeelding tot de zijkanten van de kaart lopen.
 * Omdat de afbeelding hier ALTIJD tussen Subtekst en Tekst in de lijst zit
 * (nooit het eerste of laatste kind), raakt een "volle breedte"-afbeelding
 * nooit de kaart's eigen ronde hoeken — alleen de rechte zijkanten — dus is
 * er geen overflow-hidden/clip-truc nodig zoals bij acf/content-blok's eigen
 * imageFullWidth (die daar wél de bovenste hoeken kan raken, omdat de
 * afbeelding daar altijd het eerste element is).
 *
 * De content-wrapper hieronder ("own-kaartje-content") is een gewone
 * gepadde kaart-binnenkant, exact zoals acf/content-blok's eigen
 * content-div (p-4 sm:p-6 lg:p-8) — geen per-kind padding-trucage. Het
 * loslaten van díe padding voor alleen de afbeelding gebeurt in
 * src/css/components/kaartje.css met een negative-margin/width-calc-truc
 * (zoals WordPress' eigen alignfull), maar dan gebaseerd op container-query-
 * eenheden ("cqw") i.p.v. een gewone "%": een gewone percentage-truc gaat
 * ervanuit dat de afbeelding een DIRECT kind is van de gepadde wrapper, en
 * dat klopt alleen op de front-end. In de block-editor wrapt Gutenberg elk
 * InnerBlocks-kind eerst in zijn eigen extra "wp-block"-div (standaard
 * block-editor-gedrag, niet SCF-specifiek) — dezelfde soort front-end/
 * editor-DOM-mismatch als dit thema's eigen CLAUDE.md Traps #17, hier één
 * niveau dieper. "cqw" meet altijd tegen de dichtstbijzijnde voorouder met
 * "container-type" (hier "own-kaartje-content" zelf), ongeacht hoeveel
 * ongestylde tussenlagen daartussen zitten — dus dezelfde regel klopt in
 * beide contexten, zonder dat render.php hoeft te weten welke van de twee
 * van toepassing is. Dit is niet dezelfde valkuil als de foute-afstand-bug
 * elders in dit thema (CLAUDE.md Traps #22): die ging over TWEE
 * onafhankelijke centrerings-stappen die stapelden; hier zit de afbeelding
 * maar in ÉÉN gepadde box, welke dat ook is, dus er is niets om dubbel te
 * tellen.
 *
 * "backgroundColor" is bewust een EIGEN attribute + een eigen "Kleur"-paneel
 * (editor.js), niet het native "Kleuren"-paneel — zelfde reden en zelfde
 * patroon als acf/content-blok's eigen backgroundColor (zie dat block's
 * eigen docblock voor de volle uitleg).
 */

$image_full_width = !empty($block['imageFullWidth']);

// Al resolved naar een "var(--wp--preset--color--<slug>)" (of een losse
// hex-waarde) door editor.js's eigen resolveToPresetVar() — zelfde patroon
// als acf/content-blok's eigen $bg_value.
$bg_value = $block['backgroundColor'] ?: '';
$has_bg = $bg_value !== '';

// "own-surface" (app.css) alleen zonder gekozen achtergrondkleur — zelfde
// "geen kleur ⇒ wel rand"-regel als acf/content-blok's eigen $card_class.
$card_class = trim('own-kaartje relative rounded-2xl ' . ($has_bg ? '' : 'own-surface') . ($image_full_width ? ' own-kaartje--bleed' : ''));

$card_style_parts = [];
if ($bg_value !== '') {
    $card_style_parts[] = 'background-color:' . esc_attr($bg_value);
}
// Altijd als laatste, nooit conditioneel — garandeert dat de style-attribute
// nooit op een cijfer eindigt (bv. een hexwaarde als "#062b20"), zelfde reden
// als acf/content-blok's eigen $card_style_parts.
$card_style_parts[] = 'box-sizing:border-box';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => $card_class,
    'style' => implode(';', $card_style_parts),
]);

/**
 * Letterlijke placeholdertekst, geen React-component — zie acf/hero's eigen
 * docblock voor de volle uitleg van SCF's <InnerBlocks />-vervang-truc.
 * "allowedBlocks" beperkt bewust tot precies deze vier bloktypes — dit is
 * geen generiek content-vak zoals acf/content-blok, maar een vaste
 * kaartvorm; "template" zet ze meteen in de gevraagde volgorde neer.
 *
 * "className" hier (i.p.v. SCF's default "acf-innerblocks-container" te laten
 * staan zoals de meeste andere blocks) is niet cosmetisch: src/css/components/
 * kaartje.css moet de Knoppenrij (altijd het laatste kind) individueel als
 * DIRECT kind kunnen aanspreken, om 'm "margin-top: auto" te geven — zo blijft
 * die onderaan de kaart staan zodra de kaart, via "Uitrekken om te vullen",
 * hoger is dan zijn eigen inhoud. Zonder eigen className zit de hele
 * vijf-koppige lijst achter SCF's eigen enkele "acf-innerblocks-container"-div
 * verstopt (CLAUDE.md Traps #13): ":last-child" zou dan die ENE wrapper-div
 * raken i.p.v. de knoppenrij erin — en "margin-top: auto" op die wrapper duwt
 * dan de HELE lijst (titel incluis) naar de onderkant van de kaart, precies
 * het omgekeerde van het bedoelde effect. "flex flex-col" (zonder een eigen
 * "gap-*") verhuist hier mee (i.p.v. op "own-kaartje-content" te blijven
 * staan): dat is nu de daadwerkelijke flex-kolom van de vijf kinderen,
 * "own-kaartje-content" zelf is alleen nog de gepadde buitenkant. Bewust GEEN
 * "gap-*" hier: de vijf kinderen zijn als flex-items niet meer aan elkaar's
 * margins onderhevig aan margin-collapsing zoals in gewone block-flow, dus een
 * expliciete "gap" zou BOVENOP hun eigen prose-marges komen i.p.v. die te
 * vervangen — merkbaar te veel witruimte tussen elk kopje/alinea. De prose-
 * marges alleen (typography.css) geven al genoeg ritme.
 */
$content_inner_blocks = '<InnerBlocks template=\'[["core/heading",{"level":2,"placeholder":"Titel…"}],["core/paragraph",{"placeholder":"Subtekst…"}],["core/image",{}],["core/paragraph",{"placeholder":"Tekst…"}],["core/buttons",{}]]\' allowedBlocks=\'["core/heading","core/paragraph","core/image","core/buttons"]\' templateLock=\'false\' className=\'own-kaartje-list flex flex-col\' />';
?>
<div <?= $wrapper_attributes; ?>>
    <div class="own-kaartje-content prose max-w-none flex flex-col p-4 sm:p-6 lg:p-8">
        <?= $content_inner_blocks; ?>
    </div>
</div>
