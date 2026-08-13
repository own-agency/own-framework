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

    ownframework_relabel_align_controls();
}
add_action("enqueue_block_editor_assets", "ownframework_enqueue_editor_assets");

/**
 * Herlabelt Gutenberg's eigen breedte-uitlijningslabels ("Geen"/"Wijde
 * breedte"/"Volledige breedte") naar deze theme's eigen drie-niveau-
 * naamgeving ("Smal"/"Normaal"/"Breed", zie CLAUDE.md's "Alignment, width
 * and full-bleed backgrounds") — puur tekst, geen gedrag.
 *
 * Dit MOET een `wp_add_inline_script()` op het "wp-hooks"-handle zijn, geen
 * los, via Vite gebundeld bestand (zoals een eerdere versie hiervan wél
 * deed — zie git-historie): de titels in de uitlijnings-toolbar/-dropdown
 * komen uit een MODULE-LEVEL constante in wp-includes/js/dist/
 * block-editor.js (BLOCK_ALIGNMENTS_CONTROLS/getAlignmentsInfo), die maar
 * ÉÉN KEER wordt opgebouwd — op het moment dat dat script zelf voor het
 * eerst draait, niet bij elke render. Een `wp.hooks.addFilter()`-aanroep die
 * pas NA dat moment draait (zoals een los bestand via
 * `enqueue_block_editor_assets`, geladen als `type="module"` en dus altijd
 * uitgesteld tot na alle gewone scripts) komt domweg te laat: de strings
 * liggen dan al vast, en geen filter kan een reeds opgebouwde constante nog
 * met terugwerkende kracht veranderen.
 *
 * De oplossing gebruikt WordPress' EIGEN afhankelijkheids-garantie i.p.v. een
 * race tegen laadvolgorde te winnen: "wp-i18n" (en daarmee "wp-block-editor")
 * heeft ZELF "wp-hooks" als dependency, dus print/draait altijd NA
 * "wp-hooks". Door deze filters rechtstreeks als inline-script ná
 * "wp-hooks" zelf te hangen (i.p.v. in een los bestand dat alleen wp-hooks
 * als dependency HEEFT, wat geen enkele garantie geeft over de volgorde
 * t.o.v. wp-block-editor, twee onafhankelijke scripts), draaien deze
 * filters GEGARANDEERD voordat "i18n"/"block-editor" ooit draaien — geen
 * gok over scriptvolgorde meer, gewoon dezelfde ketting die WordPress al
 * afdwingt voor elk script dat op "wp-hooks" leunt.
 *
 * "wp-hooks" wordt op ELKE wp-admin-pagina geregistreerd (kernbibliotheek),
 * maar de inline code print alleen daadwerkelijk mee als "wp-hooks" ook
 * echt wordt afgedrukt op die pagina — dus alleen relevant, geen overhead
 * elders. Vier strings, alle vier bevestigd via het gecompileerde
 * block-editor.js (domain "default"):
 * - _x("None", "Alignment option") — de uitlijningsknop voor "geen
 *   uitlijning".
 * - __("Content width") — het cijferveld in Ontwerp → Layout voor
 *   "contentSize".
 * - __("Wide width") — zowel de uitlijningsknop ALS het cijferveld voor
 *   "wideSize" — bewust dezelfde string, dus samen herlabeld.
 * - __("Full width") — alleen de uitlijningsknop; geen cijferveld voor
 *   "full" (Gutenberg berekent daar structureel geen "Max ... breed"-
 *   toelichting voor, ongeacht wat er in theme.json staat) — vandaar dat de
 *   maximale breedte hieronder IN het label zelf staat ("Breed (max.
 *   1440px)") i.p.v. als los tweede regeltje zoals bij de andere twee: er is
 *   geen hook om zelf zo'n tweede regel toe te voegen aan een structuur die
 *   Gutenberg's eigen component nooit voor "full" opbouwt. $max_width komt
 *   uit ownframework_get_full_width_max() (inc/functions/
 *   full-width-scale.php, Thema opties → Stijlen → Breedte) — dezelfde
 *   waarde als de CSS zelf gebruikt, dus dit label blijft kloppen als die
 *   instelling ooit wijzigt.
 *
 * "domain" NOOIT strikt vergelijken met de letterlijke string "default":
 * @wordpress/i18n's __()/_x() geven "domain" ONGEWIJZIGD door aan de filter
 * — bij de normale aanroep zonder tweede argument (__("Wide width"), exact
 * hoe Gutenberg dit aanroept) komt hier "undefined" aan, nooit de string
 * "default", ook al resolvet de daadwerkelijke vertaal-lookup zelf wél naar
 * het "default"-domain. "domain === 'default'" zou dus altijd falen voor
 * precies het geval dat gefilterd moet worden.
 */
function ownframework_relabel_align_controls(): void
{
    $max_width = ownframework_get_full_width_max();
    $full_label = esc_js("Breed (max. {$max_width}px)");

    wp_add_inline_script(
        "wp-hooks",
        <<<JS
        (function () {
            function isDefaultDomain(domain) {
                return !domain || domain === "default";
            }

            wp.hooks.addFilter(
                "i18n.gettext_with_context",
                "own-framework/align-labels",
                function (translation, text, context, domain) {
                    if (isDefaultDomain(domain) && context === "Alignment option" && text === "None") {
                        return "Smal";
                    }
                    return translation;
                }
            );

            wp.hooks.addFilter(
                "i18n.gettext",
                "own-framework/align-labels",
                function (translation, text, domain) {
                    if (!isDefaultDomain(domain)) {
                        return translation;
                    }
                    switch (text) {
                        case "Content width":
                            return "Smal";
                        case "Wide width":
                            return "Normaal";
                        case "Full width":
                            return "{$full_label}";
                        default:
                            return translation;
                    }
                }
            );
        })();
        JS,
        "after",
    );
}

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
