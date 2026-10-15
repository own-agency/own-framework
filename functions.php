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
    "/developer/load.php",
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

// Puts `.prose` on the block editor's content root so editor typography
// (heading margins, list bullets, …) matches the front end, where
// templates/page.html and single.html put it on core/post-content.
//
// Runs in the PARENT editor window and reaches into the editor-canvas iframe.
// An inline script enqueued on enqueue_block_assets is copied into the iframe
// too, but there it isn't reliably executed, and the iframe has no
// core/editor or core/edit-site store to tell a template part from a post —
// so .prose silently never landed and Tailwind's preflight
// (`ul { list-style: none }`) hid every bullet in the editor.
//
// Target: the post-content block when there is one (a post/page, also with
// "Toon sjabloon" on, where the outer root is the template with header and
// footer), otherwise the root container itself — but never while editing a
// template or template part on its own: those roots are bespoke Tailwind
// chrome, and `.prose` fights it (rounds every logo, overrides inherited
// text colour). The root itself, not `.editor-styles-wrapper`: the extra
// nesting breaks the typography plugin's `:first-child` margin reset.
function ownframework_editor_prose_class()
{
    wp_register_script("ownframework-editor-prose", false, ["wp-dom-ready", "wp-data"], null, true);
    wp_enqueue_script("ownframework-editor-prose");
    wp_add_inline_script("ownframework-editor-prose", "
        wp.domReady(function () {
            function editedPostType() {
                var editor = wp.data.select('core/editor');
                var type = editor && editor.getCurrentPostType && editor.getCurrentPostType();
                if (type === 'wp_template' || type === 'wp_template_part') {
                    return type;
                }
                var editSite = wp.data.select('core/edit-site');
                return (editSite && editSite.getEditedPostType && editSite.getEditedPostType()) || type;
            }
            function documents() {
                var docs = [document];
                document.querySelectorAll('iframe[name=\"editor-canvas\"]').forEach(function (frame) {
                    try {
                        if (frame.contentDocument) {
                            docs.push(frame.contentDocument);
                        }
                    } catch (e) {}
                });
                return docs;
            }
            function apply() {
                var type = editedPostType();
                var isChrome = type === 'wp_template' || type === 'wp_template_part';
                documents().forEach(function (doc) {
                    var postContent = doc.querySelector('.wp-block-post-content');
                    var root = doc.querySelector('.is-root-container');
                    var target = postContent || (isChrome ? null : root);
                    // Guarded: even a no-op classList change is a mutation,
                    // which would re-trigger the observer every frame.
                    if (root && root !== target && root.classList.contains('prose')) {
                        root.classList.remove('prose', 'max-w-none');
                    }
                    if (target && !target.classList.contains('prose')) {
                        target.classList.add('prose', 'max-w-none');
                    }
                    watch(doc);
                });
            }
            var queued = false;
            function schedule() {
                if (!queued) {
                    queued = true;
                    requestAnimationFrame(function () {
                        queued = false;
                        apply();
                    });
                }
            }
            // The iframe (and its document) is created and replaced by React
            // after this runs; class attributes too, so watch those as well.
            var watched = new WeakSet();
            function watch(doc) {
                if (watched.has(doc) || !doc.body) {
                    return;
                }
                watched.add(doc);
                new MutationObserver(schedule).observe(doc.body, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class'],
                });
            }
            // Switching between post and template within one editor session.
            wp.data.subscribe(schedule);
            apply();
        });
    ");
}
add_action("enqueue_block_editor_assets", "ownframework_editor_prose_class");

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
// top/bottom in one place. core/button and core/icon are excluded: their
// padding shapes the block itself (the box around the label/icon), not
// spacing between blocks, so both keep native all-around control — same
// reasoning for core/icon as acf/voordeel's own pre-filled icon badge
// (inc/core-blocks/button-arrow-icon.php's docblock has core/button's own
// "extend a native block" precedent).
add_filter('block_type_metadata', function (array $metadata): array {
    if (in_array($metadata['name'], ['core/button', 'core/icon'], true)) {
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
