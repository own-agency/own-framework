<?php
defined("ABSPATH") || exit();

/**
 * Bouwt het pad als [{label, url}, ...] op basis van de huidige query — url
 * is null voor het laatste (huidige) item, dat nooit een link is. Losse
 * functie van ownframework_get_breadcrumbs() zodat de trail-logica los van
 * de markup te overzien is.
 *
 * $is_preview: de block-editor preview rendert buiten de front-end main
 * query, dus geven is_front_page()/is_singular() daar altijd false terug —
 * die leunen op $wp_query-vlaggen die alleen tijdens een echte pagina-load
 * gezet worden, niet tijdens SCF's eigen preview-render. In de editor bewerk
 * je hoe dan ook altijd een singular post/page (nooit een archief/
 * zoekresultaat/404), dus wordt dat pad hier met $is_preview geforceerd i.p.v.
 * via de conditional tags gedetecteerd.
 */
function ownframework_get_breadcrumb_items(bool $is_preview = false): array
{
    $post_id = get_the_ID();
    $is_front = $is_preview
        ? ($post_id && get_option("show_on_front") === "page" && (int) get_option("page_on_front") === $post_id)
        : is_front_page();

    if ($is_front) {
        return [];
    }

    $items = [["label" => __("Home", "own-framework"), "url" => home_url("/")]];

    if ($is_preview || is_singular()) {
        $post_type = get_post_type();
        $post_type_object = get_post_type_object($post_type);

        if ($post_type === "post") {
            $categories = get_the_category();
            if (!empty($categories)) {
                $items[] = [
                    "label" => $categories[0]->name,
                    "url" => get_category_link($categories[0]),
                ];
            }
        } elseif ($post_type_object && $post_type_object->has_archive) {
            $items[] = [
                "label" => $post_type_object->labels->name,
                "url" => get_post_type_archive_link($post_type),
            ];
        }

        // Alleen relevant voor hierarchische post types (standaard "page"),
        // voor "post" en de meeste CPT's is dit altijd een leeg array.
        foreach (array_reverse(get_post_ancestors(get_the_ID())) as $ancestor_id) {
            $items[] = ["label" => get_the_title($ancestor_id), "url" => get_permalink($ancestor_id)];
        }

        $items[] = ["label" => get_the_title(), "url" => null];
    } elseif (is_category() || is_tag() || is_tax()) {
        $items[] = ["label" => single_term_title("", false), "url" => null];
    } elseif (is_post_type_archive()) {
        $items[] = ["label" => post_type_archive_title("", false), "url" => null];
    } elseif (is_author()) {
        $items[] = ["label" => get_the_author(), "url" => null];
    } elseif (is_date()) {
        if (is_day()) {
            $label = get_the_date();
        } elseif (is_month()) {
            $label = get_the_date("F Y");
        } else {
            $label = get_the_date("Y");
        }
        $items[] = ["label" => $label, "url" => null];
    } elseif (is_search()) {
        $items[] = [
            "label" => sprintf(__('Zoekresultaten voor "%s"', "own-framework"), get_search_query()),
            "url" => null,
        ];
    } elseif (is_404()) {
        $items[] = ["label" => __("Pagina niet gevonden", "own-framework"), "url" => null];
    } elseif (is_home()) {
        // De losse "Berichten"-pagina (Instellingen > Lezen) — is_front_page()
        // hierboven ving de "nieuwste berichten als voorpagina"-variant al af.
        $blog_page_id = (int) get_option("page_for_posts");
        $items[] = [
            "label" => $blog_page_id ? get_the_title($blog_page_id) : __("Blog", "own-framework"),
            "url" => null,
        ];
    }

    return $items;
}

/**
 * Geeft het broodkruimelpad terug als kant-en-klare markup, of "" op de
 * voorpagina en overal waar het pad alleen "Home" zou bevatten — dan is er
 * niks om naar terug te navigeren. Schema.org BreadcrumbList-microdata erbij
 * voor de rich snippet in zoekresultaten, geen aparte JSON-LD-variant nodig.
 */
function ownframework_get_breadcrumbs(string $class = "", bool $is_preview = false): string
{
    $items = ownframework_get_breadcrumb_items($is_preview);
    $count = count($items);

    if ($count < 2) {
        return "";
    }

    $html =
        '<nav aria-label="' .
        esc_attr__("Broodkruimelpad", "own-framework") .
        '" class="' .
        esc_attr($class) .
        '"><ol class="flex flex-wrap items-center gap-1.5" itemscope itemtype="https://schema.org/BreadcrumbList">';

    foreach ($items as $index => $item) {
        $position = $index + 1;
        $is_last = $position === $count;
        // Het eerste item is altijd "Home" (zie ownframework_get_breadcrumb_items()
        // hierboven) — een huisje communiceert dat sneller dan het woord zelf, en
        // scheelt breedte in een pad dat toch al kan wrappen. Tekst blijft wel
        // bestaan (sr-only) voor screenreaders en de schema.org "name"-itemprop.
        $is_home = $position === 1;

        $html .= '<li class="flex items-center gap-1.5" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

        $label = $is_home
            ? ownframework_get_icon("home", "w-4 h-4") . '<span class="sr-only" itemprop="name">' . esc_html($item["label"]) . "</span>"
            : '<span itemprop="name">' . esc_html($item["label"]) . "</span>";

        if ($item["url"] && !$is_last) {
            $html .= '<a href="' . esc_url($item["url"]) . '" class="hover:underline" itemprop="item">' . $label . "</a>";
        } else {
            $html .= '<span class="opacity-70"' . ($is_last ? ' aria-current="page"' : "") . ">" . $label . "</span>";
        }

        $html .= '<meta itemprop="position" content="' . esc_attr((string) $position) . '">';

        if (!$is_last) {
            $html .= ownframework_get_icon("chevron-right", "w-3 h-3 opacity-50 shrink-0");
        }

        $html .= "</li>";
    }

    $html .= "</ol></nav>";

    return $html;
}
