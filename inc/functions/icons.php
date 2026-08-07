<?php
defined("ABSPATH") || exit();

function ownframework_get_icon(string $name, string $class = ""): string
{
    $path =
        get_template_directory() . "/media/icons/" . sanitize_file_name($name) . ".svg";

    if (!file_exists($path)) {
        return "";
    }

    $svg = file_get_contents($path);

    if ($class) {
        $svg = preg_replace(
            "/<svg/",
            '<svg class="' . esc_attr($class) . '"',
            $svg,
            1,
        );
    }

    return $svg;
}
