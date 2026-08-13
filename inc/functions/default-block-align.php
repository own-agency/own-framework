<?php
defined('ABSPATH') || exit();

/**
 * Zet "wide" (het nieuwe midden-formaat van de drie breedte-niveaus, zie
 * CLAUDE.md's "Alignment, width and full-bleed backgrounds") als DEFAULT
 * align-waarde voor elk block dat "wide" ondersteunt — core blocks incluis,
 * niet alleen deze theme's eigen blocks. Eén centraal filter i.p.v. elk
 * block.json's eigen "attributes.align.default" los aanpassen, omdat een
 * core block (afbeelding, groep, kolommen, …) zijn "align"-attribute meestal
 * niet zelf declareert — WordPress voegt die pas later zelf toe (zie
 * hieronder) zonder ooit een default te zetten, dus zonder dit filter blijft
 * elk zo'n block gewoon op "" (geen uitlijning) staan.
 *
 * "register_block_type_args" i.p.v. een latere hook: dit filter draait
 * SYNCHROON tijdens elke afzonderlijke register_block_type()-aanroep
 * (WP_Block_Type::set_props(), wp-includes/class-wp-block-type.php) — dus
 * ook voor deze theme's eigen blocks, die hun "align"-attribute (met een
 * eigen "default":"") al WEL zelf declareren in block.json. Daarom hieronder
 * altijd de default OVERSCHRIJVEN als het attribute al bestaat, niet alleen
 * toevoegen als het ontbreekt — anders blijft precies de meest voorspelbare
 * groep (deze theme's eigen blocks) buiten schot.
 *
 * Voor blocks die "align" nog NIET zelf declareren (de meeste core blocks):
 * WordPress' eigen wp_register_alignment_support() (wp-includes/block-
 * supports/align.php) voegt die pas toe op de "init"-hook, priority 22
 * (wp-includes/default-filters.php) — dus RUIM NA dit filter, dat al
 * synchroon binnen elke losse register_block_type()-aanroep draait. Die
 * functie voegt het attribute alleen toe als het nog niet bestaat
 * ("!array_key_exists('align', $block_type->attributes)"), en overschrijft
 * nooit een al bestaand attribute — dus door het attribute hier zelf al aan
 * te maken (met dezelfde vorm: type string + enum, zie hieronder) mét een
 * default, blijft die default ongemoeid staan zodra die latere hook draait.
 */
add_filter('register_block_type_args', function (array $args): array {
    $align_support = $args['supports']['align'] ?? false;
    $allowed_values = true === $align_support
        ? ['left', 'center', 'right', 'wide', 'full', '']
        : (is_array($align_support) ? $align_support : []);

    if (!in_array('wide', $allowed_values, true)) {
        return $args;
    }

    if (!isset($args['attributes']) || !is_array($args['attributes'])) {
        $args['attributes'] = [];
    }

    if (!isset($args['attributes']['align'])) {
        // Zelfde vorm als WordPress' eigen wp_register_alignment_support()
        // zou toevoegen — dit filter maakt dat mechanisme dus niet overbodig,
        // het loopt 'm alleen voor.
        $args['attributes']['align'] = [
            'type' => 'string',
            'enum' => ['left', 'center', 'right', 'wide', 'full', ''],
        ];
    }
    $args['attributes']['align']['default'] = 'wide';

    return $args;
});
