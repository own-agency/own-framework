<?php
defined("ABSPATH") || exit();

/**
 * Adds "Subtitel" (<p class="subtitle">, styled in
 * src/css/components/typography.css) to the WYSIWYG format dropdown,
 * alongside Paragraph and the headings — using WP's stock "formatselect"
 * button as-is, not a replacement control, so the editor UI stays default.
 *
 * `formats` registers "subtitle" as a named format (block p + a class)
 * in TinyMCE's formatter, exactly like registering it via JS would. Once
 * registered, `block_formats` can reference it by that name instead of a
 * raw tag — letting the standard formatselect list a class-based option
 * and, since matching now goes through the formatter (which does check
 * classes), correctly highlight it as active too. The rest of the list
 * repeats TinyMCE's own default entries (see wp-includes/js/tinymce
 * /themes/modern/theme.js's `defaultBlocks`) so nothing else changes.
 */
add_filter('tiny_mce_before_init', function (array $init): array {
    $init['formats'] = wp_json_encode([
        'subtitle' => ['block' => 'p', 'classes' => 'subtitle'],
    ]);
    $init['block_formats'] = 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Subtitel=subtitle;Preformatted=pre';
    return $init;
});
