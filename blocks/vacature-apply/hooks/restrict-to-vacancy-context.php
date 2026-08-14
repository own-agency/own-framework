<?php
defined('ABSPATH') || exit();

/**
 * acf/vacature-apply leest per-post velden van "de huidige vacature"
 * (application_url, via get_the_ID()) — buiten een vacature-context is dat
 * geen zinnige plek. Twee plekken zijn wél geldig:
 *
 * - templates/single-vacancy.html zelf (post_type "wp_template", post_name
 *   "single-vacancy") — dat is waar dit block daadwerkelijk staat sinds de
 *   2/3+1/3 kolommen-layout naar de TEMPLATE verhuisde (inc/functions/
 *   vacancy-post-content-template.php's oude per-post "template"-argument is
 *   bewust verwijderd: dat werkte alleen voor vacatures die via de block-
 *   editor's "Nieuwe vacature toevoegen" aangemaakt waren, niet voor
 *   vacatures die WP All Import rechtstreeks in de database zet — de
 *   TEMPLATE geldt altijd, ongeacht hoe een vacature is aangemaakt).
 * - een losse vacature-POST zelf (post_type "vacancy") — voor het geval een
 *   editor het block toch nog eigenhandig ergens in een individuele
 *   vacaturetekst wil plaatsen.
 */
add_filter('allowed_block_types_all', function ($allowed_block_types, $editor_context) {
    $restricted_blocks = ['acf/vacature-apply'];

    $post = $editor_context->post ?? null;
    $is_vacancy_context = $post && (
        $post->post_type === 'vacancy'
        || ($post->post_type === 'wp_template' && $post->post_name === 'single-vacancy')
    );

    if ($is_vacancy_context) {
        return $allowed_block_types;
    }

    if ($allowed_block_types === true) {
        $allowed_block_types = array_keys(WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    return array_values(array_diff($allowed_block_types, $restricted_blocks));
}, 10, 2);
