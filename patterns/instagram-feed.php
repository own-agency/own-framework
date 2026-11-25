<?php
/**
 * Title: Instagram-feed met titel
 * Slug: own-framework/instagram-feed
 * Categories: own-framework
 * Description: Titel links, een knop naar het Instagram-account rechts en de nieuwste posts in een raster eronder. Zonder koppeling toont de feed een lege melding.
 * Keywords: instagram, social, feed, posts, volgen
 */

defined('ABSPATH') || exit();

// The button points at the connected account, resolved when the pattern is
// inserted. Without a connection it falls back to instagram.com; the editor
// can always change the link afterwards. The feed block itself reads the
// connection at render time, so only this link is a snapshot.
$own_ig_username = ownframework_instagram_is_connected()
    ? (string) (ownframework_instagram_connection()['username'] ?? '')
    : '';
$own_ig_url = esc_url('https://www.instagram.com/' . ($own_ig_username !== '' ? rawurlencode($own_ig_username) . '/' : ''));
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|xl","bottom":"var:preset|spacing|xl"},"blockGap":"var:preset|spacing|m"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--xl);margin-bottom:var(--wp--preset--spacing--xl)">
    <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
    <div class="wp-block-group">
        <!-- wp:heading -->
        <h2 class="wp-block-heading">Volg ons op Instagram</h2>
        <!-- /wp:heading -->

        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo $own_ig_url; ?>" target="_blank" rel="noreferrer noopener">Bekijk alle posts</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->

    <!-- wp:acf/instagram-feed {"name":"acf/instagram-feed","mode":"preview","layout":"cards","count":4,"rounded":true,"align":"wide"} /-->
</div>
<!-- /wp:group -->
