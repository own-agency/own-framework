<?php
defined("ABSPATH") || exit();

/**
 * SVG uploads for administrators only, sanitized on upload.
 */

function ownframework_allow_svg_upload($mimes)
{
    if (current_user_can("manage_options")) {
        $mimes["svg"] = "image/svg+xml";
        $mimes["svgz"] = "image/svg+xml";
    }
    return $mimes;
}
add_filter("upload_mimes", "ownframework_allow_svg_upload");

function ownframework_sanitize_svg($file)
{
    if ($file["type"] === "image/svg+xml") {
        if (!current_user_can("manage_options")) {
            $file["error"] =
                "Sorry, SVG upload is restricted to administrators only.";
            return $file;
        }

        // Check the real MIME type, not the browser-supplied one.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if ($mime_type !== "image/svg+xml") {
            $file["error"] = "File is not a valid SVG.";
            return $file;
        }

        $svg_content = file_get_contents($file["tmp_name"]);
        if ($svg_content === false) {
            $file["error"] = "Could not read SVG file.";
            return $file;
        }

        $sanitized_svg = ownframework_sanitize_svg_content($svg_content);

        if ($sanitized_svg === null) {
            $file["error"] = "Invalid or unsafe SVG file.";
            return $file;
        }

        file_put_contents($file["tmp_name"], $sanitized_svg);
    }
    return $file;
}
add_filter("wp_handle_upload_prefilter", "ownframework_sanitize_svg");

/**
 * Parses the SVG as XML and strips dangerous nodes/attributes. Deliberately not
 * a regex denylist — those are bypassed by obfuscated markup, unlisted event
 * handlers or nested contexts.
 */
function ownframework_sanitize_svg_content(string $svg_content): ?string
{
    // A DOCTYPE has no legitimate use in an SVG asset and is the XXE /
    // entity-expansion vector, so reject rather than strip.
    if (preg_match("/<!DOCTYPE/i", $svg_content)) {
        return null;
    }

    // "use" stays: it's how internal icon-sprite references (href="#id") work.
    // The attribute pass below strips any external/javascript: href it carries.
    $dangerous_tags = ["script", "foreignobject", "iframe", "embed", "object"];
    $url_attributes = ["href", "xlink:href", "src"];

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadXML($svg_content, LIBXML_NONET);
    libxml_clear_errors();

    if (!$loaded || !$dom->documentElement || strtolower($dom->documentElement->nodeName) !== "svg") {
        return null;
    }

    $xpath = new DOMXPath($dom);

    foreach ($dangerous_tags as $tag) {
        foreach (iterator_to_array($xpath->query("//*[translate(local-name(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='{$tag}']")) as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    foreach (iterator_to_array($xpath->query("//*")) as $element) {
        if (!$element instanceof DOMElement) {
            continue;
        }

        $attributes_to_remove = [];

        foreach ($element->attributes as $attr) {
            $name  = strtolower($attr->nodeName);
            $value = trim($attr->nodeValue);

            $is_event_handler = str_starts_with($name, "on");
            $is_unsafe_url     = in_array($name, $url_attributes, true)
                && preg_match('/^\s*(javascript|data|vbscript):/i', $value);
            $is_unsafe_style   = $name === "style"
                && preg_match('/javascript:|expression\s*\(/i', $value);

            if ($is_event_handler || $is_unsafe_url || $is_unsafe_style) {
                $attributes_to_remove[] = $attr->nodeName;
            }
        }

        foreach ($attributes_to_remove as $attr_name) {
            $element->removeAttribute($attr_name);
        }
    }

    return $dom->saveXML();
}

function ownframework_add_svg_mime_type($data, $file, $filename, $mimes)
{
    $filetype = wp_check_filetype($filename, $mimes);
    if ($filetype["ext"] === "svg" && $filetype["type"] === "image/svg+xml") {
        $data["ext"] = "svg";
        $data["type"] = "image/svg+xml";
    }
    return $data;
}
add_filter("wp_check_filetype_and_ext", "ownframework_add_svg_mime_type", 10, 4);

/**
 * Without this, image_downsize() returns width/height 0 for any SVG: it
 * treats SVG as a real image (mime starts with "image/", wp_attachment_is()
 * in wp-includes/post.php), so it skips straight to looking for a generated
 * intermediate thumbnail size — which never exists for SVG, since GD/Imagick
 * can't rasterize one. That 0x0 is why core/site-logo's own width-slider
 * calculation (wp-includes/blocks/site-logo.php) silently does nothing for
 * an SVG custom logo: it explicitly bails when width/height come back empty.
 * Parsing the file's own viewBox/width/height and reporting that here gives
 * WordPress real dimensions, the same as it already has for any raster
 * image — restores the width slider, and header.css's max-height cap
 * (blocks/header, .wp-block-site-logo img) has a real ratio to work from
 * instead of nothing to size against.
 */
function ownframework_svg_image_downsize($downsize, $attachment_id, $size)
{
    if ($downsize) {
        return $downsize;
    }

    $attachment = get_post($attachment_id);
    if (!$attachment || $attachment->post_mime_type !== "image/svg+xml") {
        return $downsize;
    }

    $path = get_attached_file($attachment_id);
    if (!$path || !file_exists($path)) {
        return $downsize;
    }

    $dimensions = ownframework_get_svg_dimensions($path);
    if (!$dimensions) {
        return $downsize;
    }

    $url = wp_get_attachment_url($attachment_id);
    if (!$url) {
        return $downsize;
    }

    return [$url, $dimensions["width"], $dimensions["height"], false];
}
add_filter("image_downsize", "ownframework_svg_image_downsize", 10, 3);

/**
 * @return array{width: int, height: int}|null
 */
function ownframework_get_svg_dimensions(string $path): ?array
{
    $svg_content = file_get_contents($path);
    if ($svg_content === false) {
        return null;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadXML($svg_content, LIBXML_NONET);
    libxml_clear_errors();

    if (!$loaded || !$dom->documentElement) {
        return null;
    }

    $svg = $dom->documentElement;

    $width = $svg->getAttribute("width");
    $height = $svg->getAttribute("height");
    if (is_numeric($width) && is_numeric($height) && (float) $width > 0 && (float) $height > 0) {
        return ["width" => (int) round((float) $width), "height" => (int) round((float) $height)];
    }

    // Most exported SVGs carry a viewBox even when width/height are
    // percentages or missing entirely — "minX minY width height".
    $view_box = $svg->getAttribute("viewBox");
    if ($view_box) {
        $parts = preg_split("/[\s,]+/", trim($view_box));
        if (count($parts) === 4 && is_numeric($parts[2]) && is_numeric($parts[3]) && (float) $parts[2] > 0 && (float) $parts[3] > 0) {
            return ["width" => (int) round((float) $parts[2]), "height" => (int) round((float) $parts[3])];
        }
    }

    return null;
}

// SVGs have no intrinsic pixel size, so the media library sizes them wrong.
function ownframework_fix_svg_thumbnails()
{
    if (current_user_can("manage_options")) {
        echo '<style>
            .attachment-266x266, .thumbnail img {
                width: 100% !important;
                height: auto !important;
            }
        </style>';
    }
}
add_action("admin_head", "ownframework_fix_svg_thumbnails");
