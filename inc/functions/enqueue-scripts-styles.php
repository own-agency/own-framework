<?php
defined("ABSPATH") || exit();

/**
 * Enqueue scripts and styles from Vite build
 */
function ownframework_enqueue_assets()
{
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    $manifest_path = $theme_dir . "/assets/.vite/manifest.json";

    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);

        if (isset($manifest["src/css/app.css"])) {
            $css_file = $manifest["src/css/app.css"]["file"];
            wp_enqueue_style(
                "theme-styles",
                $theme_uri . "/assets/" . $css_file,
                [],
                null,
            );
        }

        if (isset($manifest["src/js/main.js"])) {
            $js_file = $manifest["src/js/main.js"]["file"];
            wp_enqueue_script(
                "theme-scripts",
                $theme_uri . "/assets/" . $js_file,
                [],
                null,
                true,
            );

            wp_script_add_data("theme-scripts", "defer", true);
        }
    }
}

add_action("wp_enqueue_scripts", "ownframework_enqueue_assets");

/**
 * `add_editor_style()` (ownframework_theme_setup(), functions.php) auto-scopes
 * app.css under `.editor-styles-wrapper` — that wrapper only exists in the
 * classic per-post content editor. The Site Editor's Templates/Template-onderdelen
 * canvas (e.g. editing the "Header" template part) has no such wrapper, so
 * none of that scoped CSS ever matches there: blocks fall back to raw
 * Gutenberg default styling, rendering noticeably bigger/different than the
 * front end (own-header, own-header-topbar, the site-logo height cap, the
 * dropdown/flyout rules — all of it silently missing).
 *
 * enqueue_block_editor_assets runs inside EVERY block-editor iframe — classic
 * post editing and the Site Editor's Templates/Template-onderdelen canvas
 * alike — and, unlike add_editor_style(), does not rewrite selectors. Loading
 * the exact same compiled app.css there (unscoped, matching the front end
 * enqueue 1:1) is what actually closes that gap. Deliberately additive, not a
 * replacement for add_editor_style(): that one's scoped rewrite is still what
 * makes plain front-end selectors reach the classic editor's own
 * .editor-styles-wrapper, and the TinyMCE exclusion elsewhere depends on it
 * staying registered under the "theme-styles"-adjacent editor-style handle.
 */
function ownframework_enqueue_editor_assets()
{
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    $manifest_path = $theme_dir . "/assets/.vite/manifest.json";

    if (!file_exists($manifest_path)) {
        return;
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);

    if (isset($manifest["src/css/app.css"]["file"])) {
        wp_enqueue_style(
            "theme-styles-editor",
            $theme_uri . "/assets/" . $manifest["src/css/app.css"]["file"],
            [],
            null,
        );
    }

    // editor-only.css (src/css/components/editor-layout.css) is normally only
    // reached via add_editor_style(), which — same gap as app.css above —
    // never applies to the Site Editor's Templates/Template-onderdelen canvas.
    // Its rules (ACF block links disabled in-editor, the site-logo height
    // fallback) are exactly the kind that matter most there, so it needs the
    // same unscoped enqueue_block_editor_assets treatment.
    if (isset($manifest["src/css/editor-only.css"]["file"])) {
        wp_enqueue_style(
            "theme-styles-editor-only",
            $theme_uri . "/assets/" . $manifest["src/css/editor-only.css"]["file"],
            [],
            null,
        );
    }

    // Los, MINIMAAL bestand (src/js/editor-scripts.js), niet main.js zelf:
    // zonder Swiper-initialisatie blijven de paginatie-bolletjes van acf/hero
    // en acf/afbeeldingen-slider onzichtbaar in de editor (die <span>'s
    // worden pas door Swiper zelf aangemaakt, render.php zet alleen de lege
    // container neer) — maar main.js's overige scripts (Alpine.start(),
    // header.js, header-nav-touch.js, live-search.js) gaan uit van de VOLLE
    // pagina (een echte <header>, de zoekmodal's eigen ancestor-context) die
    // er in een block-editor-iframe met alleen dít blok's eigen preview-DOM
    // niet is. Alpine die daar toch overheen scant kan vastlopen op een
    // ontbrekend element/store — de editor werd daardoor ooit even helemaal
    // niet meer klikbaar, een veel groter probleem dan onopgemaakte
    // paginatie-bolletjes. editor-scripts.js importeert daarom bewust alleen
    // hero.js + image-slider.js, niets anders uit main.js.
    if (isset($manifest["src/js/editor-scripts.js"]["file"])) {
        wp_enqueue_script(
            "theme-scripts-editor",
            $theme_uri . "/assets/" . $manifest["src/js/editor-scripts.js"]["file"],
            [],
            null,
            true,
        );
    }
}
add_action("enqueue_block_editor_assets", "ownframework_enqueue_editor_assets");

/**
 * "theme-scripts" en "theme-scripts-editor" delen nu een chunk (beide
 * importeren hero.js/image-slider.js, zie vite.config.ts's "chunkFileNames"),
 * dus staat er een kale `import "./chunk...js"` bovenaan hun eigen gecompileerde
 * bestand. Zonder `type="module"` op het <script>-tag zelf gooit de browser
 * daar een "Cannot use import statement outside a module"-syntaxerror op, en
 * draait geen regel van dat bestand — front-end interactiviteit én de editor's
 * eigen Swiper-fix zouden dan allebei stil breken, zonder build-foutmelding
 * (Vite compileert dit prima, het is puur een runtime-mismatch tussen module-
 * en classic-script-laden).
 */
add_filter("script_loader_tag", function (string $tag, string $handle): string {
    if (!in_array($handle, ["theme-scripts", "theme-scripts-editor"], true)) {
        return $tag;
    }

    if (str_contains($tag, 'type="module"')) {
        return $tag;
    }

    return str_replace("<script ", '<script type="module" ', $tag);
}, 10, 2);
