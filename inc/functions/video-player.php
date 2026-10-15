<?php
defined("ABSPATH") || exit();

/**
 * Wraps core/video's own <video> in a Video.js player (src/js/scripts/
 * video.js registers the elements) — server-side, so the layout is final on
 * first paint instead of re-flowing once the script runs.
 *
 * Only videos WITH "controls": an editor who turns off "Toon afspeelknoppen"
 * wants a bare autoplay/loop video, no player UI on top. blocks/hero's own
 * background video is its own block, never core/video, so it's never touched.
 * Front end only — render_block never runs for the editor's client-side
 * core/video preview, which keeps native controls there.
 *
 * Moved on the way in, per the Video.js docs:
 * - "controls" is dropped: the skin draws its own, and Video.js doesn't hide
 *   native controls by itself (known gap, videojs/v10#1160).
 * - "poster" moves to <video-player>: a poster on the <video> only sets the
 *   browser's own poster, the skin's poster image reads the player's.
 * - The aspect ratio comes from the attachment's own width/height, so a
 *   vertical or square upload isn't forced into 16:9. External URLs (no
 *   attachment ID) fall back to the video's intrinsic size via CSS.
 */
add_filter(
    "render_block_core/video",
    function (string $content, array $block): string {
        $processor = new WP_HTML_Tag_Processor($content);
        if (!$processor->next_tag("video") || $processor->get_attribute("controls") === null) {
            return $content;
        }

        $poster = (string) $processor->get_attribute("poster");
        $processor->remove_attribute("controls");
        $processor->remove_attribute("poster");
        $content = $processor->get_updated_html();

        $skin_style = "";
        $meta = !empty($block["attrs"]["id"]) ? wp_get_attachment_metadata((int) $block["attrs"]["id"]) : [];
        if (!empty($meta["width"]) && !empty($meta["height"])) {
            $skin_style = sprintf(' style="aspect-ratio:%d / %d"', $meta["width"], $meta["height"]);
        }

        // <media-i18n> without "lang" follows <html lang> (nl-NL → the shipped
        // "nl" pack).
        $open = sprintf(
            '<media-i18n><video-player%s><video-neutral-skin class="own-video-player"%s>',
            $poster !== "" ? ' poster="' . esc_url($poster) . '"' : "",
            $skin_style,
        );
        $close = "</video-neutral-skin></video-player></media-i18n>";

        return preg_replace('#<video\b.*?</video>#s', $open . '$0' . $close, $content, 1) ?? $content;
    },
    10,
    2,
);
