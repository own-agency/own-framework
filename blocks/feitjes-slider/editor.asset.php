<?php
/**
 * Hand-written (no wp-scripts build step for this theme) — declares editor.js's
 * script dependencies so WordPress loads it after these are available.
 * register_block_script_handle() (wp-includes/blocks.php) reads this file
 * automatically for any "editorScript"/"script"/"viewScript" entry in
 * block.json; without it, the script registers with an empty dependency
 * array and wp.hooks/wp.element/wp.blockEditor/wp.components aren't
 * guaranteed to exist yet when it runs.
 */
return array(
    'dependencies' => array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-hooks', 'wp-data'),
    'version' => '1.0.0',
);
