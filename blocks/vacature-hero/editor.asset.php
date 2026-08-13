<?php
/**
 * Hand-written (no wp-scripts build step for this theme) — declares editor.js's
 * script dependencies so WordPress loads it after these are available. See
 * blocks/hero/editor.asset.php for the same reasoning.
 */
return array(
    'dependencies' => array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-hooks'),
    'version' => '1.0.0',
);
