<?php
/**
 * Title: Content met afbeelding
 * Slug: own-framework/content-met-afbeelding
 * Categories: own-framework
 * Description: Titel, tekst en een knop naast een afbeelding. Wissel de kant via de "Media & tekst"-knop in de blok-toolbar.
 * Keywords: content, afbeelding, sectie, media, tekst
 */

$own_placeholder_url = esc_url(get_template_directory_uri() . '/media/images/placeholder.png');
?>
<!-- wp:media-text {"mediaType":"image","align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"}}}} -->
<div class="wp-block-media-text alignwide is-stacked-on-mobile" style="margin-top:var(--wp--preset--spacing--xl);margin-bottom:var(--wp--preset--spacing--xl)"><figure class="wp-block-media-text__media"><img src="<?php echo $own_placeholder_url; ?>" alt=""/></figure><div class="wp-block-media-text__content">
<!-- wp:heading -->
<h2 class="wp-block-heading">Titel van deze sectie</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Korte, heldere tekst die uitlegt wat je hier laat zien en waarom dit relevant is voor de bezoeker.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Meer informatie</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:media-text -->
