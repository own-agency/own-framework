<?php
defined('ABSPATH') || exit();

/**
 * Zet acf/hero als verplicht eerste block van elke nieuwe pagina — een
 * post-type "template" (WordPress' eigen starter-content-mechanisme voor de
 * editor, niet te verwarren met templates/page.html: dat is de statische
 * paginastructuur rond de content, core/post-content daar is puur een
 * dynamische plek voor wát er ook in de post_content van DIE pagina staat,
 * en kan zelf geen starter-blocks "meegeven" aan nieuwe pagina's).
 *
 * "lock":{"move":true,"remove":true} op het template-item zelf (niet op de
 * hele pagina — er is bewust geen "template_lock" hier): "true" betekent die
 * actie IS vergrendeld — geverifieerd in wp-includes/js/dist/block-editor.js'
 * eigen isMoveLockedBlock()/isRemoveLockedBlock() ("return !!attributes?.
 * lock?.move"), niet aangenomen. blocks/footer/render.php's eigen kolom-
 * locks gebruiken "false" met een comment die het omgekeerde beweert — dat
 * is dus zelf ook fout en verplaatsbaar/verwijderbaar ondanks de comment,
 * los van deze aanpassing (nog niet gecorrigeerd, geen onderdeel van deze
 * wijziging). Hier dus wél "true": dit block kan noch verplaatst noch
 * verwijderd worden, maar de rest van de pagina blijft volledig vrij te
 * bewerken (blocks toevoegen/verwijderen/herordenen onder de hero). Werkt
 * samen met block.json's "multiple": false: samen garanderen ze precies één
 * hero, altijd als eerste block, op elke pagina.
 */
add_filter('register_post_type_args', function (array $args, string $post_type): array {
    if ($post_type !== 'page') {
        return $args;
    }

    $args['template'] = [
        ['acf/hero', ['lock' => ['move' => true, 'remove' => true]]],
    ];

    return $args;
}, 10, 2);
