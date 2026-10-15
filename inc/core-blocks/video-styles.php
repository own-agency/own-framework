<?php
defined("ABSPATH") || exit();

/**
 * core/video's "Geen afronding" style: the same per-block opt-out of the
 * site-wide "Hoekafronding" scale as core/image's (image-styles.php), so a
 * video can sit square next to a square image. The CSS side is an
 * `:not(.is-style-square)` exclusion in src/css/components/video.css.
 */
add_action("init", function (): void {
    register_block_style("core/video", [
        "name" => "square",
        "label" => __("Geen afronding", "own-framework"),
    ]);
});
