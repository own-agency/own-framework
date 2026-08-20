<?php
defined('ABSPATH') || exit();

/**
 * Toont "— Vacatures archief" (etc.) achter de titel van een pagina in
 * Pagina's → Alle pagina's, zodra die pagina het bijbehorende
 * "archive-{post_type}"-sjabloon heeft ("Sjabloon" in de zijbalk van de
 * pagina-editor) — exact dezelfde plek/opmaak als WordPress' eigen native
 * "— Voorpagina"/"— Berichtenpagina"-labels (Instellingen → Lezen se
 * voorpagina/berichtenpagina), via hetzelfde mechanisme: het
 * "display_post_states"-filter (wp-admin/includes/template.php se
 * _post_states()), niet iets zelfgebouwds in de lijsttabel.
 *
 * get_page_template_slug($post) (native, sinds WP 4.7 ook voor CPT's) i.p.v.
 * zelf de "_wp_page_template"-postmeta uitlezen — die functie normaliseert
 * de opgeslagen waarde al naar de kale bestandsnaam-slug (bv.
 * "archive-vacancy"), ongeacht of WordPress intern een `theme//slug`-vorm of
 * iets anders opslaat.
 *
 * De label-TEKST komt uit het al bestaande, geregistreerde block-template
 * zelf (get_block_template(), "wp_template" post type) i.p.v. een tweede,
 * los onderhouden lijstje met dezelfde drie titels — inc/functions/
 * register-templates.php's eigen register_block_template()-aanroepen zijn
 * daarmee de ENIGE plek waar "Vacatures archief"/"Agenda archief"/
 * "Verhalen archief" als tekst staat; hernoem je die daar, dan volgt dit
 * label vanzelf mee, geen twee plekken om in sync te houden. Zodra een
 * editor de template zelf al eens via de Site Editor heeft aangepast/
 * hernoemd (CLAUDE.md Traps #28: dat zet 'm blijvend in de database), pakt
 * get_block_template() sowieso al die eigen, actuele titel — precies het
 * gedrag dat je hier wil.
 *
 * Bewust alleen `page` (niet ook `vacancy`/`agenda`/`verhaal` zelf): een
 * losse vacature/moment/verhaal krijgt zijn archief nooit via een handmatig
 * gekozen sjabloon, dus dat overzicht heeft dit label nooit nodig — alleen
 * een gewone pagina die willens en wetens als "vitrine" voor zo'n archief is
 * ingericht.
 *
 * Alleen deze drie slugs (de enige "archive-*"-templates die dit theme
 * heeft) — geen dynamische scan van alle geregistreerde CPT's/templates:
 * zelfde "een kleine, bekende lijst hardcoden i.p.v. een generiek systeem
 * bouwen voor drie items"-conventie als de rest van dit theme. Voeg een
 * vierde regel toe zodra er een nieuw "archive-{post_type}"-sjabloon bijkomt.
 */
add_filter('display_post_states', function (array $post_states, WP_Post $post): array {
    if ($post->post_type !== 'page') {
        return $post_states;
    }

    $archive_template_slugs = ['archive-vacancy', 'archive-agenda', 'archive-verhaal'];

    $template_slug = get_page_template_slug($post);
    if (!in_array($template_slug, $archive_template_slugs, true)) {
        return $post_states;
    }

    $template = get_block_template(get_stylesheet() . '//' . $template_slug, 'wp_template');
    if ($template && $template->title !== '') {
        $post_states['own_archive_template'] = $template->title;
    }

    return $post_states;
}, 10, 2);
