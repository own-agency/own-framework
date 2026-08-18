<?php
defined('ABSPATH') || exit();

/**
 * Geeft templates/archive-vacancy.html en templates/single-vacancy.html elk
 * een titel + omschrijving in de Site Editor's Templates-lijst. Nodig omdat
 * "archive-{post_type}"/"single-{post_type}" geen van beide letterlijk in
 * get_default_block_template_types() (wp-includes/block-template-utils.php)
 * voorkomen — dat kent alleen de generieke "archive"/"single", niet elke
 * mogelijke post-type-variant erbovenop. Zonder dit toont de lijst gewoon de
 * kale bestandsnaam ("archive-vacancy"/"single-vacancy") als titel, zonder
 * omschrijving.
 *
 * register_block_template() (sinds WP 6.7, wp-includes/block-template.php) is
 * hiervoor de officiële route: _build_block_template_result_from_file()
 * (wp-includes/block-template-utils.php) valt terug op een hier geregistreerde
 * titel/omschrijving zodra het .html-bestand zelf nog geen titel heeft ÉN de
 * slug niet in de ingebouwde lijst voorkomt — precies deze situatie voor
 * beide templates. Zodra iemand een template via de Site Editor hernoemt/
 * aanpast, wint die eigen (in de database opgeslagen) titel voortaan altijd,
 * ongeacht wat hier staat — dit is alleen de eerste-indruk-default voor een
 * nog ongewijzigde template.
 *
 * Dekt bewust NIET index/single/page/404/archive (de generieke ingebouwde
 * types zelf, zonder post-type-achtervoegsel): die slugs staan wél
 * letterlijk in get_default_block_template_types(), en dat ingebouwde blok
 * (verderop in dezelfde functie) overschrijft een via register_block_
 * template() gegeven titel/omschrijving daar juist weer. Voor die vaste
 * WordPress-sjabloontypen is hernoemen via de Site Editor zelf de enige
 * manier — dit theme kan hun titel niet zelf zetten. "single-vacancy" is wél
 * veilig: WordPress herkent de POST-TYPE-hiërarchie (welk bestand een
 * vacature-detailpagina rendert) volledig automatisch op bestandsnaam, maar
 * die automatische herkenning bevat geen eigen titel/omschrijving-fallback
 * voor de post-type-variant — vandaar dat dit hier alsnog nodig is, ondanks
 * dat er (anders dan bij "archive-vacancy") geen aparte registratie nodig was
 * om het bestand zelf gevonden te krijgen.
 */
add_action('init', function () {
    register_block_template('own-framework//archive-vacancy', [
        'title' => __('Vacatures archief', 'own-framework'),
        'description' => __('Archiefpagina van de vacatures: acf/vacature-hero (zoeken) en acf/vacatures (filters, sortering, resultaten).', 'own-framework'),
    ]);

    register_block_template('own-framework//single-vacancy', [
        'title' => __('Vacature detail', 'own-framework'),
        'description' => __('Detailpagina van één vacature: acf/vacature-single-hero, daaronder de vacaturetekst (2/3) naast de sollicitatiekaart acf/vacature-apply (1/3).', 'own-framework'),
    ]);

    // Zelfde reden als hierboven, nu voor de `agenda` CPT: "archive-agenda"/
    // "single-agenda" komen evenmin voor in get_default_block_template_types().
    register_block_template('own-framework//archive-agenda', [
        'title' => __('Agenda archief', 'own-framework'),
        'description' => __('Archiefpagina van de agenda: acf/agenda-hero (titel/tekst) en acf/agenda (filters op categorie + periode, sortering, resultaten).', 'own-framework'),
    ]);

    register_block_template('own-framework//single-agenda', [
        'title' => __('Agenda moment detail', 'own-framework'),
        'description' => __('Detailpagina van één agenda-moment: acf/agenda-single-hero, daaronder de inhoud (2/3) naast de aanmeldkaart acf/agenda-aanmelden (1/3, alleen zichtbaar als aanmelden aan staat).', 'own-framework'),
    ]);
});
