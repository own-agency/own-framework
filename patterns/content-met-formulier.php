<?php

/**
 * Title: Content met formulier
 * Slug: own-framework/content-met-formulier
 * Categories: own-framework
 * Description: Titel en tekst naast een Forminator-formulier. Op mobiel staat het formulier automatisch onder de tekst, op desktop ernaast.
 * Keywords: content, formulier, forminator, contact, sectie
 */
?>
<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--xl);margin-bottom:var(--wp--preset--spacing--xl)">
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- wp:heading -->
        <h2 class="wp-block-heading">Titel van deze sectie</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph -->
        <p>Korte, heldere tekst die uitlegt waarom de bezoeker dit formulier zou invullen.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->

    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:acf/forminator-form /--></div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->