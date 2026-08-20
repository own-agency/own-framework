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
 *
 * UITZONDERING: binnen de post-editor van "post" (blog), "agenda" en
 * "verhaal" (Verhalen) is de default "" (Smal) i.p.v. "wide" — de inhoud van
 * een los bericht/moment/verhaal is doorlopende tekst, waar redacteuren
 * telkens zelf terugschakelden naar Smal. Elders (pagina's, de site-editor-
 * templates zelf) blijft "wide" gewoon de default. Dit kan alleen server-side
 * bepaald worden: block-registratie (en dus dit filter) draait EENMALIG per
 * request op "init", dus de admin-request voor "post.php"/"post-new.php" van
 * zo'n post type registreert de blocks meteen met de juiste default — geen
 * losse REST-aanroep achteraf nodig, de block-editor haalt de geregistreerde
 * block-types uit dezelfde request. $pagenow/$_GET zijn hier veilig te lezen:
 * puur een UI-default bepalen, geen state-wijziging, dus geen nonce nodig.
 */

/**
 * @return string|null Slug van het post type dat momenteel bewerkt wordt in
 *                      "post.php"/"post-new.php", of null buiten die twee
 *                      admin-schermen (incl. de site-editor, die andere
 *                      "pagenow"-waarden gebruikt).
 */
function ownframework_get_admin_edited_post_type(): ?string
{
    if (!is_admin()) {
        return null;
    }

    // get_current_screen() is the most authoritative source when it's already
    // available — but it's normally only set up AFTER "init" fires during the
    // wp-admin bootstrap (wp-admin/includes/admin.php's set_current_screen()
    // runs later than wp-settings.php's own "init"), so this is a bonus check
    // for whenever that ordering doesn't hold, not the primary signal.
    if (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && $screen->base === 'post' && !empty($screen->post_type)) {
            return $screen->post_type;
        }
    }

    global $pagenow;

    if ($pagenow === 'post-new.php') {
        return isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : 'post';
    }

    if ($pagenow === 'post.php' && isset($_GET['post'])) {
        return get_post_type((int) $_GET['post']) ?: null;
    }

    return null;
}

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

    $small_align_post_types = ['post', 'agenda', 'verhaal'];
    $editing_post_type = ownframework_get_admin_edited_post_type();
    $args['attributes']['align']['default'] = in_array($editing_post_type, $small_align_post_types, true) ? '' : 'wide';

    return $args;
});
