<?php
defined('ABSPATH') || exit();

/**
 * Query/filter-logica voor acf/verhalen, gedeeld door drie aanroepers:
 * de eerste front-end render (render.php), het REST-endpoint dat live
 * filteren aandrijft (Alpine, src/js/scripts/verhalen-filter.js) en de
 * pre_get_posts-filter hieronder (zodat een gebookmarkte/gedeelde
 * gefilterde URL ook zonder JavaScript werkt) — zelfde driedelige opzet als
 * blocks/news/hooks/query.php's eigen bestand, hier voor de `verhaal` CPT.
 * Net als agenda's eigen taxonomie wordt "verhalen-categorie" dynamisch
 * ontdekt (get_object_taxonomies(), niet hardcoded) via
 * ownframework_verhalen_get_taxonomies() hieronder — een tweede taxonomie
 * die later via dezelfde SCF-UI wordt toegevoegd pikt de filterbalk/
 * facet-tellingen dus automatisch op.
 *
 * "Taxonomies" is daarom een keyed array (momenteel altijd precies één
 * entry), zelfde vorm als blocks/news/hooks/query.php's eigen
 * ownframework_news_get_taxonomies() (die daar bewust een array van ÉÉN
 * hardcoded taxonomie is, "category" — hier is het er dynamisch één, maar de
 * rest van dit bestand (parse_params/build_tax_query/facet_counts) is een
 * bijna-identieke kopie van dat bestand, zelfde "dupliceer per CPT-familie
 * i.p.v. delen"-conventie als de andere drie archiefblocks onderling
 * hanteren.
 *
 * Standaard-sortering is "nieuwste eerst" (net als acf/news/acf/vacatures)
 * — een verhalenoverzicht is geen kalender van aankomende momenten zoals
 * acf/agenda, dus geen chronologisch-oplopende default.
 *
 * Filter/sorteer-state leeft in de globale $store.verhalenFilters (src/js/
 * scripts/verhalen-filter.js) — gedeeld tussen acf/verhaal-hero's eigen
 * filterbalk (parts/filter-bar.php) en dit block's eigen sorteerdropdown/
 * resultatengrid, ondanks dat het twee losse blocks zijn.
 * "data-own-verhalen-filters" (de JSON-config die die store bij het laden
 * inleest) staat daarom op acf/verhaal-hero's eigen wrapper, moet precies
 * één keer op de pagina bestaan.
 */

/**
 * Dynamisch ontdekte taxonomieën van de `verhaal` CPT (momenteel alleen
 * "verhalen-categorie") — gebruikt door zowel de filterlogica hieronder als
 * blocks/verhaal-single-hero/render.php's eigen badges. De kaart zelf
 * (blocks/verhalen/parts/card.php) toont bewust geen taxonomie-badges
 * (alleen titel/quote/datum/link, zie dat bestand's eigen docblock).
 *
 * @return array<string, WP_Taxonomy>
 */
function ownframework_verhalen_get_taxonomies(): array
{
    $taxonomies = get_object_taxonomies('verhaal', 'objects');

    return array_filter($taxonomies, fn (WP_Taxonomy $tax): bool => (bool) $tax->show_ui);
}

/**
 * @return array<string, array{name: string, label: string, options: list<array{value: string, label: string, count: int}>}>
 */
function ownframework_verhalen_get_filter_data(): array
{
    $filter_data = [];

    foreach (ownframework_verhalen_get_taxonomies() as $slug => $tax) {
        $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => true]);
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        $options = array_map(fn ($term): array => [
            'value' => $term->slug,
            'label' => $term->name,
            'count' => (int) $term->count,
        ], $terms);

        $filter_data[$slug] = [
            'name' => $slug,
            'label' => $tax->label,
            'options' => $options,
        ];
    }

    return $filter_data;
}

/**
 * @param array<string, mixed> $params e.g. $_GET or a REST request's params.
 * @return array{orderby: string, order: string, tax_terms: array<string, list<string>>}
 */
function ownframework_verhalen_parse_params(array $params): array
{
    $allowed_orderby = ['date' => 'date', 'title' => 'title'];
    $orderby = $allowed_orderby[$params['orderby'] ?? ''] ?? 'date';
    $order = strtoupper((string) ($params['order'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';

    // Leest "verhalen-categorie[]=slug-a&verhalen-categorie[]=slug-b"
    // (bracketed), niet een kale herhaalde "verhalen-categorie=a&
    // verhalen-categorie=b" — zelfde reden als blocks/news/hooks/query.php's
    // eigen docblock: PHP houdt bij een herhaalde niet-bracketed key alleen
    // de LAATSTE waarde over zodra het $_GET/$request->get_params() vult.
    $tax_terms = [];
    foreach (ownframework_verhalen_get_taxonomies() as $slug => $tax) {
        if (empty($params[$slug])) {
            continue;
        }
        $raw = is_array($params[$slug]) ? $params[$slug] : [$params[$slug]];
        $terms = array_values(array_filter(array_map('sanitize_title', $raw)));
        if (!empty($terms)) {
            $tax_terms[$slug] = $terms;
        }
    }

    return [
        'orderby' => $orderby,
        'order' => $order,
        'tax_terms' => $tax_terms,
    ];
}

/**
 * @param array<string, list<string>> $tax_terms
 * @param string $skip_taxonomy Leave out this taxonomy's own filter — used to compute
 *                               faceted counts ("results if you also picked this option").
 */
function ownframework_verhalen_build_tax_query(array $tax_terms, string $skip_taxonomy = ''): array
{
    $tax_query = [];
    foreach ($tax_terms as $taxonomy => $terms) {
        if ($taxonomy === $skip_taxonomy || empty($terms)) {
            continue;
        }
        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $terms,
            'operator' => 'IN',
        ];
    }
    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    return $tax_query;
}

/**
 * Standalone query — gebruikt door de editor-preview en het REST-endpoint,
 * dus telkens wanneer het block niet meerijdt op WordPress' eigen
 * hoofd-query.
 */
function ownframework_verhalen_new_query(array $params, int $paged, int $per_page): WP_Query
{
    $parsed = ownframework_verhalen_parse_params($params);

    $args = [
        'post_type' => 'verhaal',
        'post_status' => 'publish',
        'posts_per_page' => max(1, $per_page),
        'paged' => max(1, $paged),
        'orderby' => $parsed['orderby'],
        'order' => $parsed['order'],
    ];

    $tax_query = ownframework_verhalen_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    return new WP_Query($args);
}

/**
 * De archiefpagina's MAIN query (de eerste, no-JS page load) is al gebouwd
 * vóórdat acf/verhalen ooit rendert — herleidt het block's eigen "Aantal per
 * pagina"-attribuut uit de (mogelijk in de Site Editor overschreven)
 * "archive-verhaal"-templateinhoud, zelfde mechanisme/reden als
 * blocks/vacatures/hooks/query.php's eigen
 * ownframework_vacatures_get_configured_per_page() (CLAUDE.md Traps #29).
 */
function ownframework_verhalen_get_configured_per_page(): int
{
    $default = 12;

    $template = get_block_template(get_stylesheet() . '//archive-verhaal', 'wp_template');
    if (!$template || empty($template->content)) {
        return $default;
    }

    $per_page = ownframework_verhalen_find_block_attr(parse_blocks($template->content), 'acf/verhalen', 'perPage');

    return $per_page !== null ? max(1, (int) $per_page) : $default;
}

/**
 * Zelfde "herlees de opgeslagen archive-verhaal-template"-truc als
 * ownframework_verhalen_get_configured_per_page() hierboven (CLAUDE.md
 * Traps #29), voor acf/verhalen's eigen "fallbackImage"-attribute (editor.js'
 * "Kaart zonder foto"-paneel) — gebruikt door blocks/verhalen/parts/card.php
 * voor elk verhaal zonder eigen uitgelichte afbeelding.
 *
 * MET een statische cache, in tegenstelling tot de per-pagina-load-functie
 * hierboven: deze wordt aangeroepen vanuit een KAART-template die per
 * request meerdere keren in een lus rendert (één keer per verhaal zonder
 * foto), dus zonder cache zou dezelfde template-fetch/parse-cyclus zich
 * onnodig per kaart herhalen — de waarde verandert toch niet binnen één
 * request.
 *
 * @return array{id?: int, url?: string, alt?: string}
 */
function ownframework_verhalen_get_configured_fallback_image(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $template = get_block_template(get_stylesheet() . '//archive-verhaal', 'wp_template');
    if (!$template || empty($template->content)) {
        return $cached = [];
    }

    $image = ownframework_verhalen_find_block_attr(parse_blocks($template->content), 'acf/verhalen', 'fallbackImage');

    return $cached = (is_array($image) ? $image : []);
}

/**
 * @param array<int, array{blockName: ?string, attrs: array, innerBlocks: array}> $blocks parse_blocks() output.
 */
function ownframework_verhalen_find_block_attr(array $blocks, string $block_name, string $attr_name)
{
    foreach ($blocks as $block) {
        if (($block['blockName'] ?? '') === $block_name) {
            return $block['attrs'][$attr_name] ?? null;
        }
        if (!empty($block['innerBlocks'])) {
            $found = ownframework_verhalen_find_block_attr($block['innerBlocks'], $block_name, $attr_name);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

/**
 * Laat een gebookmarkte/gedeelde gefilterde URL (?verhalen-categorie[]=...,
 * ?orderby=title) werken via WordPress' eigen archiefquery/paginering zonder
 * JavaScript — zelfde relatie als blocks/news/hooks/query.php's eigen
 * pre_get_posts-hook, hier op "is_post_type_archive('verhaal')" i.p.v.
 * "is_home()" (dit CPT heeft een eigen, echte archiefpagina, geen
 * "Berichtenpagina"-instelling zoals `post`).
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('verhaal')) {
        return;
    }

    $parsed = ownframework_verhalen_parse_params($_GET);

    $tax_query = ownframework_verhalen_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }

    $query->set('orderby', $parsed['orderby']);
    $query->set('order', $parsed['order']);
    $query->set('posts_per_page', ownframework_verhalen_get_configured_per_page());
});

/**
 * Zelfde ±1-venster-met-ellipsis-paginering als blocks/vacatures/hooks/
 * query.php's eigen ownframework_vacatures_pagination_pages().
 *
 * @return list<int|string>
 */
function ownframework_verhalen_pagination_pages(int $current_page, int $max_pages): array
{
    if ($max_pages <= 1) {
        return [];
    }

    $pages = [];
    $candidates = array_unique([1, $current_page - 1, $current_page, $current_page + 1, $max_pages]);
    sort($candidates);

    foreach ($candidates as $page) {
        if ($page < 1 || $page > $max_pages) {
            continue;
        }
        if (!empty($pages) && end($pages) !== '...' && $page - end($pages) > 1) {
            $pages[] = '...';
        }
        $pages[] = $page;
    }

    return $pages;
}

/**
 * Geen eigen layout-wrapper om de kaarten heen — render.php's eigen
 * "data-own-verhalen-results" div is wat de grid-layout beheert, en die div
 * wordt nooit in zijn geheel vervangen, alleen zijn innerHTML (deze
 * functie's return-waarde) — zelfde reden als blocks/news/hooks/query.php's
 * eigen ownframework_news_render_cards().
 */
function ownframework_verhalen_render_cards(WP_Query $query, ?array $parsed = null): string
{
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('blocks/verhalen/parts/card');
        }
    } else {
        // Two different situations: filters that match nothing (offer to
        // clear them) or nothing published yet (just say so). The REST
        // callback passes its own parsed params; the first page load reads
        // them from the URL. The empty state spans the whole results grid
        // itself (empty-state.css).
        $parsed ??= ownframework_verhalen_parse_params($_GET);
        if (!empty($parsed['tax_terms'])) {
            $empty = [
                'title' => __('Geen verhalen gevonden', 'own-framework'),
                'text' => __('Er zijn geen verhalen die bij deze filters passen.', 'own-framework'),
                'action' => [
                    'label' => __('Filters wissen', 'own-framework'),
                    'alpine' => '$store.verhalenFilters.clearTaxonomyFilters()',
                ],
            ];
        } else {
        $empty = [
            'title' => __('Nog geen verhalen', 'own-framework'),
            'text' => __('Nieuwe verhalen verschijnen hier zodra ze gepubliceerd zijn.', 'own-framework'),
        ];
        }
        get_template_part('template-parts/components/empty-state', null, $empty);
    }

    wp_reset_postdata();

    return ob_get_clean();
}

/**
 * Knoppen met "data-own-verhalen-page" i.p.v. gewone `<a href>`-links: de
 * globale $store.verhalenFilters (src/js/scripts/verhalen-filter.js) luistert
 * op die data-attribute om zonder page-reload een REST-call te triggeren —
 * zelfde mechanisme als blocks/news/agenda/vacatures/hooks/query.php's eigen
 * paginering.
 */
function ownframework_verhalen_render_pagination(int $current_page, int $max_pages): string
{
    $pages = ownframework_verhalen_pagination_pages($current_page, $max_pages);
    if (empty($pages)) {
        return '';
    }

    ob_start();
    ?>
    <nav class="own-verhalen-pagination mt-10 flex justify-center" aria-label="<?php esc_attr_e('Paginering', 'own-framework'); ?>">
        <div class="flex gap-2 items-center">
            <?php if ($current_page > 1) : ?>
                <button type="button" data-own-verhalen-page="<?= (int) ($current_page - 1); ?>" class="own-verhalen-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-verhalen-surface text-gray-900 hover:bg-gray-50">
                    <span class="sr-only"><?php esc_html_e('Vorige', 'own-framework'); ?></span>
                    <?= ownframework_get_icon('chevron-right', 'w-4 h-4 rotate-180'); ?>
                </button>
            <?php endif; ?>

            <?php foreach ($pages as $page) : ?>
                <?php if ($page === '...') : ?>
                    <span class="relative inline-flex h-9 items-center px-3.5 text-sm text-gray-500 select-none">&hellip;</span>
                <?php elseif ($page === $current_page) : ?>
                    <span aria-current="page" class="own-verhalen-page-current relative z-10 inline-flex h-9 items-center rounded-xl px-3.5 text-sm font-semibold text-white">
                        <?= (int) $page; ?>
                    </span>
                <?php else : ?>
                    <button type="button" data-own-verhalen-page="<?= (int) $page; ?>" class="own-verhalen-page-btn relative inline-flex h-9 items-center rounded-xl own-verhalen-surface px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                        <?= (int) $page; ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $max_pages) : ?>
                <button type="button" data-own-verhalen-page="<?= (int) ($current_page + 1); ?>" class="own-verhalen-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-verhalen-surface text-gray-900 hover:bg-gray-50">
                    <span class="sr-only"><?php esc_html_e('Volgende', 'own-framework'); ?></span>
                    <?= ownframework_get_icon('chevron-right', 'w-4 h-4'); ?>
                </button>
            <?php endif; ?>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}

/**
 * Facet-tellingen voor de taxonomie-groepen — identieke redenering/vorm als
 * blocks/news/hooks/query.php's eigen ownframework_news_facet_counts(): voor
 * taxonomie A, tel met elk actief filter BEHALVE taxonomie A's eigen
 * selectie, zodat een nog niet aangevinkte optie's getal "resultaten als je
 * deze OOK kiest" weergeeft i.p.v. het huidige (al door A gefilterde) getal.
 *
 * @param array<string, list<string>> $tax_terms
 * @return array<string, array<string, int>>
 */
function ownframework_verhalen_facet_counts(array $tax_terms): array
{
    global $wpdb;

    $taxonomy_counts = [];

    foreach (array_keys(ownframework_verhalen_get_filter_data()) as $taxonomy) {
        $count_args = [
            'post_type' => 'verhaal',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        $tax_query = ownframework_verhalen_build_tax_query($tax_terms, $taxonomy);
        if (!empty($tax_query)) {
            $count_args['tax_query'] = $tax_query;
        }

        $ids_query = new WP_Query($count_args);
        $post_ids = array_map('intval', $ids_query->posts);
        wp_reset_postdata();

        $taxonomy_counts[$taxonomy] = [];

        if (empty($post_ids)) {
            continue;
        }

        $ids_placeholder = implode(',', $post_ids);
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.slug, COUNT(DISTINCT tr.object_id) AS term_count
                 FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                 WHERE tt.taxonomy = %s
                 AND tr.object_id IN ({$ids_placeholder})
                 GROUP BY t.slug",
                $taxonomy
            )
        );

        foreach ($rows as $row) {
            $taxonomy_counts[$taxonomy][$row->slug] = (int) $row->term_count;
        }
    }

    return $taxonomy_counts;
}

add_action('rest_api_init', function () {
    register_rest_route('own-verhalen/v1', '/filter', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ownframework_verhalen_rest_filter',
        'permission_callback' => '__return_true',
        'args' => [
            'orderby' => ['type' => 'string'],
            'order' => ['type' => 'string'],
            'verhalen-categorie' => ['type' => 'array', 'items' => ['type' => 'string']],
            'paged' => ['type' => 'integer'],
            'per_page' => ['type' => 'integer'],
        ],
    ]);
});

function ownframework_verhalen_rest_filter(WP_REST_Request $request): WP_REST_Response
{
    $params = $request->get_params();
    $paged = max(1, (int) $request->get_param('paged'));
    $per_page = (int) $request->get_param('per_page') ?: 12;

    $query = ownframework_verhalen_new_query($params, $paged, $per_page);
    $parsed = ownframework_verhalen_parse_params($params);

    $response = [
        'success' => true,
        'count' => $query->found_posts,
        'max_pages' => $query->max_num_pages,
        'html' => ownframework_verhalen_render_cards($query, $parsed),
        'pagination_html' => ownframework_verhalen_render_pagination($paged, $query->max_num_pages),
        'taxonomy_counts' => ownframework_verhalen_facet_counts($parsed['tax_terms']),
    ];

    return new WP_REST_Response($response);
}

/**
 * Gelokaliseerd op "theme-scripts" (inc/functions/enqueue-scripts-styles.php's
 * al-ingeschreven Vite-bundel), zelfde patroon als blocks/news/hooks/
 * query.php's eigen window.OwnNews. `verhaal` heeft, anders dan `post`, een
 * echte CPT-archiefpagina (get_post_type_archive_link()) — geen
 * "Berichtenpagina"-instelling nodig zoals news' eigen fallback.
 */
add_action('wp_enqueue_scripts', function () {
    wp_localize_script('theme-scripts', 'OwnVerhalen', [
        'endpoint' => rest_url('own-verhalen/v1/filter'),
        'archiveUrl' => get_post_type_archive_link('verhaal') ?: home_url('/'),
    ]);
});
