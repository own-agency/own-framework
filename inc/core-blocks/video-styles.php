<?php
defined("ABSPATH") || exit();

/**
 * "Geen afronding" for core/video and core/embed: the same per-block opt-out
 * of the site-wide "Hoekafronding" scale as core/image's (image-styles.php),
 * so a video can sit square next to a square image. The CSS side is the
 * `.is-style-square` rule in src/css/components/video.css.
 *
 * core/embed has no per-variation block styles, so the style also shows for
 * non-video embeds (a post on X, a Spotify player); it does nothing there,
 * since only "is-type-video" embeds get a radius in the first place.
 */
add_action("init", function (): void {
    foreach (["core/video", "core/embed"] as $block) {
        register_block_style($block, [
            "name" => "square",
            "label" => __("Geen afronding", "own-framework"),
        ]);
    }
});
