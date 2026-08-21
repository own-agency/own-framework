<?php
if (!defined("ABSPATH")) {
    exit();
}

// Autoloaded PHP. glob() has no recursive "**", so every directory needs its
// own pattern here. See STRUCTURE.md for what belongs in each.
//
// blocks/*/hooks/ is narrower than blocks/*/ on purpose: render.php and parts/
// are templates, output at render time, and must never be require_once'd.
$ownframework_autoload = [
    "/inc/functions/*.php",
    "/inc/core-blocks/*.php",
    "/inc/scf/*.php",
    "/blocks/*/hooks/*.php",
    "/live-search/load.php",
    "/notifications/load.php",
];
foreach ($ownframework_autoload as $ownframework_pattern) {
    foreach (glob(get_template_directory() . $ownframework_pattern) as $file) {
        require_once $file;
    }
}
unset($ownframework_autoload, $ownframework_pattern);

function ownframework_register_my_menus()
{
    register_nav_menus([
        "topbar-menu" => __("Topbalk Menu", "own-framework"),
        "primary-menu" => __("Primary Menu", "own-framework"),
        "menu-1" => __("Footer Menu 1", "own-framework"),
        "menu-2" => __("Footer Menu 2", "own-framework"),
        "menu-3" => __("Footer Menu 3", "own-framework"),
        "copyright-menu" => __("Copyright Menu", "own-framework"),
    ]);
}
add_action("init", "ownframework_register_my_menus");

// Theme setup
function ownframework_theme_setup()
{
    load_theme_textdomain("own-framework", get_template_directory() . "/languages");

    add_theme_support("title-tag");
    add_theme_support("post-thumbnails");
    add_theme_support("custom-logo");

    add_post_type_support("page", "excerpt");

    add_theme_support("html5", [
        "search-form",
        "comment-form",
        "comment-list",
        "gallery",
        "caption",
        "style",
        "script",
    ]);

    // Block editor
    add_theme_support("editor-styles");
    add_theme_support("align-wide");
    add_theme_support("wp-block-styles");
    add_theme_support("responsive-embeds");

    // Redundant now that templates/index.html makes this a full block theme
    // (wp_is_block_theme() already implies template-part support) — kept
    // declared explicitly anyway; harmless, and correct if templates/ were
    // ever removed again.
    add_theme_support("block-template-parts");

    // add_editor_style() auto-scopes plain selectors under
    // .editor-styles-wrapper, so it reaches the iframed post editor.
    // enqueue_block_editor_assets does not, unless the stylesheet already
    // contains .editor-styles-wrapper/.wp-block selectors — ours doesn't.
    $manifest_path = get_template_directory() . "/assets/.vite/manifest.json";
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);
        if (isset($manifest["src/css/app.css"]["file"])) {
            $editor_style_url = get_template_directory_uri() . "/assets/" . $manifest["src/css/app.css"]["file"];
            add_editor_style($editor_style_url);

            // add_editor_style() also feeds classic TinyMCE via `mce_css`, which
            // leaked Tailwind's margin:0 reset into ACF's WYSIWYG fields —
            // flattening their paragraphs. Those fields have no `.prose` root to
            // restore spacing, so keep our stylesheet out of TinyMCE entirely.
            add_filter("mce_css", function (string $mce_css) use ($editor_style_url): string {
                $urls = array_filter(explode(",", $mce_css), fn($url) => trim($url) !== $editor_style_url);
                return implode(",", $urls);
            });
        }

        // Editor-only overrides, deliberately kept out of app.css: rules like
        // disabling pointer-events on ACF blocks' real <a>/<button> markup must
        // never reach the front end.
        if (isset($manifest["src/css/editor-only.css"]["file"])) {
            add_editor_style(get_template_directory_uri() . "/assets/" . $manifest["src/css/editor-only.css"]["file"]);
        }
    }
}
add_action("after_setup_theme", "ownframework_theme_setup");

// Puts `.prose` on the block editor's content root so editor typography matches
// the front end.
//
// enqueue_block_assets (not enqueue_block_editor_assets) runs inside the editor
// iframe itself, so a plain selector lookup works.
//
// Target `.is-root-container`, not `.editor-styles-wrapper`: the wrapper sits a
// level higher, and the extra nesting breaks the typography plugin's
// `:first-child` margin reset, leaving the first heading with a top margin.
//
// Template parts are skipped — their root is bespoke Tailwind chrome, not
// article content, and `.prose` fights it (rounds every logo, overrides
// inherited text colour).
function ownframework_editor_prose_class()
{
    if (!is_admin()) {
        return;
    }

    wp_register_script("ownframework-editor-prose", false, ["wp-dom-ready", "wp-data"], null, true);
    wp_enqueue_script("ownframework-editor-prose");
    wp_add_inline_script("ownframework-editor-prose", "
        wp.domReady(function () {
            function isTemplateChrome() {
                if (!wp.data || !wp.data.select) {
                    return false;
                }
                // core/editor is the classic post/page editor's store — editing a
                // template PART directly there (e.g. the Header template part
                // opened on its own, not through the Site Editor's own Templates
                // list) does register it, reporting postType there correctly.
                var editor = wp.data.select('core/editor');
                var postType = editor && editor.getCurrentPostType && editor.getCurrentPostType();
                if (postType === 'wp_template_part' || postType === 'wp_template') {
                    return true;
                }
                // core/edit-site is the Site Editor's own store (Templates/
                // Template-onderdelen screens) — core/editor's getCurrentPostType()
                // legitimately returns undefined/'post' there instead of erroring,
                // which used to read as not-template-chrome and let `.prose`
                // leak onto the header/footer template parts' own root (rounding
                // logos, adding article-style image margins, etc).
                var editSite = wp.data.select('core/edit-site');
                var editedPostType = editSite && editSite.getEditedPostType && editSite.getEditedPostType();
                return editedPostType === 'wp_template_part' || editedPostType === 'wp_template';
            }
            function applyProseClass() {
                var root = document.querySelector('.is-root-container');
                if (!root) {
                    return;
                }
                if (isTemplateChrome()) {
                    root.classList.remove('prose', 'max-w-none');
                    return;
                }
                if (!root.classList.contains('prose')) {
                    root.classList.add('prose', 'max-w-none');
                }
            }
            // The root container is rendered by React after this script runs,
            // and can remount (template switch, editor reload), so keep
            // watching rather than checking once.
            applyProseClass();
            new MutationObserver(applyProseClass).observe(document.body, {
                childList: true,
                subtree: true,
            });
        });
    ");
}
add_action("enqueue_block_assets", "ownframework_editor_prose_class");

// Register custom block categories. "own-framework-header-footer" is its own
// category (not just "own-framework") so header/footer's own blocks group
// together in the inserter, separate from any future non-header/footer
// custom block — inc/functions/header-footer-blocks.php is what actually
// keeps those blocks OUT of the inserter everywhere except the header/footer
// template parts; this category is just where they show up once there.
add_filter('block_categories_all', function (array $categories): array {
    array_unshift(
        $categories,
        [
            'slug'  => 'own-framework-header-footer',
            'title' => 'Header & footer',
            'icon'  => null,
        ],
        [
            'slug'  => 'own-framework-vacatures',
            'title' => 'Vacatures',
            'icon'  => null,
        ],
        [
            'slug'  => 'own-framework-agenda',
            'title' => 'Agenda',
            'icon'  => null,
        ],
        [
            'slug'  => 'own-framework-news',
            'title' => 'Nieuws',
            'icon'  => null,
        ],
        [
            'slug'  => 'own-framework-verhalen',
            'title' => 'Verhalen',
            'icon'  => null,
        ],
        [
            'slug'  => 'own-framework',
            'title' => 'OWN Framework',
            'icon'  => null,
        ]
    );
    return $categories;
});

// Register custom ACF blocks from their block.json files
add_action('init', function (): void {
    foreach (glob(get_template_directory() . '/blocks/*/block.json') as $block_json) {
        register_block_type(dirname($block_json));
    }
});

// theme.json's settings.spacing.margin/padding is on/off only — it can't
// restrict which sides show. Editors need vertical rhythm, not horizontal
// margin/padding, so narrow every block's existing margin/padding support to
// top/bottom in one place. core/button is excluded: its padding shapes the
// button itself (the box around the label), not spacing between blocks, so
// it keeps its native horizontal + vertical control.
add_filter('block_type_metadata', function (array $metadata): array {
    if ($metadata['name'] === 'core/button') {
        return $metadata;
    }
    if (!empty($metadata['supports']['spacing']['margin'])) {
        $metadata['supports']['spacing']['margin'] = ['top', 'bottom'];
    }
    if (!empty($metadata['supports']['spacing']['padding'])) {
        $metadata['supports']['spacing']['padding'] = ['top', 'bottom'];
    }
    return $metadata;
});
