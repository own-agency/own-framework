<?php
defined('ABSPATH') || exit();

/**
 * Geeft templates/archive-vacancy.html een titel + omschrijving in de Site
 * Editor's Templates-lijst. Nodig omdat "archive-{post_type}" geen ingebouwd
 * WordPress-sjabloontype is — get_default_block_template_types() (wp-includes/
 * block-template-utils.php) kent alleen de generieke "archive", niet elke
 * mogelijke post-type-variant erbovenop. Zonder dit toont de lijst gewoon de
 * kale bestandsnaam "archive-vacancy" als titel, zonder omschrijving.
 *
 * register_block_template() (sinds WP 6.7, wp-includes/block-template.php) is
 * hiervoor de officiële route: _build_block_template_result_from_file()
 * (wp-includes/block-template-utils.php) valt terug op een hier geregistreerde
 * titel/omschrijving zodra het .html-bestand zelf nog geen titel heeft ÉN de
 * slug niet in de ingebouwde lijst voorkomt — precies deze situatie. Zodra
 * iemand de template via de Site Editor hernoemt/aanpast, wint die eigen (in
 * de database opgeslagen) titel voortaan altijd, ongeacht wat hier staat —
 * dit is alleen de eerste-indruk-default voor een nog ongewijzigde template.
 *
 * Dekt bewust NIET index/single/page/404/archive (de generieke ingebouwde
 * types): die slugs staan wél in get_default_block_template_types(), en dat
 * ingebouwde blok (verderop in dezelfde functie) overschrijft een via
 * register_block_template() gegeven titel/omschrijving daar juist weer. Voor
 * die vaste WordPress-sjabloontypen is hernoemen via de Site Editor zelf de
 * enige manier — dit theme kan hun titel niet zelf zetten.
 */
add_action('init', function () {
    register_block_template('own-framework//archive-vacancy', [
        'title' => __('Vacatures archief', 'own-framework'),
        'description' => __('Archiefpagina van de vacatures: acf/vacature-hero (zoeken) en acf/vacatures (filters, sortering, resultaten).', 'own-framework'),
    ]);
});
