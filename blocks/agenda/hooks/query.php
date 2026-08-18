<?php
defined('ABSPATH') || exit();

/**
 * Query/filter logic for acf/agenda, shared by three callers: the initial
 * front-end render (render.php), the REST endpoint that powers live
 * filtering (Alpine, src/js/scripts/agenda-filter.js) and the pre_get_posts
 * filter below (so a bookmarked/shared filtered URL still works with
 * JavaScript off) — same three-caller split as blocks/vacatures/hooks/
 * query.php, ported for this CPT's own two facets: the "agenda-categorie"
 * taxonomy (discovered dynamically, get_object_taxonomies()) and "periode"
 * (aankomend/afgelopen/alle), which isn't a taxonomy at all — it's a
 * meta_query against the denormalized "agenda_sort_end" postmeta
 * (inc/functions/agenda-helpers.php's ownframework_agenda_sync_sort_meta()),
 * so it's handled as its own single-select facet rather than forced into
 * the taxonomy loop.
 *
 * Sorting also reads that denormalized pair ("agenda_sort_start"/
 * "agenda_sort_end") rather than the post's own post_date: a moment's
 * meaningful date is when it HAPPENS, not when the post was published, and
 * "orderby date" would otherwise need to pick between two different meta
 * keys (event_date vs. start_date) depending on event_type — a single
 * WP_Query can't orderby "whichever of two meta keys applies per row".
 */

/**
 * @return array<string, WP_Taxonomy> Keyed by taxonomy slug.
 */
function ownframework_agenda_get_taxonomies(): array
{
    $taxonomies = get_object_taxonomies(OWNFRAMEWORK_AGENDA_POST_TYPE, 'objects');

    return array_filter($taxonomies, fn (WP_Taxonomy $tax): bool => (bool) $tax->show_ui);
}

/**
 * Only taxonomies that currently have at least one term get a filter group —
 * an empty group would just be a heading with nothing under it.
 *
 * @return array<string, array{name: string, label: string, options: list<array{value: string, label: string, count: int}>}>
 */
function ownframework_agenda_get_filter_data(): array
{
    $filter_data = [];

    foreach (ownframework_agenda_get_taxonomies() as $slug => $tax) {
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
 * The three "periode" options — a single-select facet (radio, not
 * checkboxes) unlike the taxonomy groups above, and not backed by a
 * taxonomy at all. Labels only; counts are filled in at render/REST time
 * (ownframework_agenda_periode_counts()).
 *
 * @return array<string, string>
 */
function ownframework_agenda_get_periode_options(): array
{
    return [
        'aankomend' => __('Aankomend', 'own-framework'),
        'afgelopen' => __('Afgelopen', 'own-framework'),
        'alle' => __('Alle', 'own-framework'),
    ];
}

/**
 * @param array<string, mixed> $params e.g. $_GET or a REST request's params.
 * @return array{search: string, orderby: string, order: string, periode: string, tax_terms: array<string, list<string>>}
 */
function ownframework_agenda_parse_params(array $params): array
{
    $search = isset($params['search']) && !is_array($params['search'])
        ? sanitize_text_field((string) $params['search'])
        : '';

    $allowed_orderby = ['date' => 'date', 'title' => 'title'];
    $orderby = $allowed_orderby[$params['orderby'] ?? ''] ?? 'date';
    // "date" here always means agenda_sort_start (see ownframework_agenda_new_query()) —
    // chronological, not "nieuwste eerst", since this is a calendar of upcoming
    // moments, not a news feed. Default ASC, opposite of vacatures' DESC default.
    $order = strtoupper((string) ($params['order'] ?? '')) === 'DESC' ? 'DESC' : 'ASC';

    $periode_options = ownframework_agenda_get_periode_options();
    $periode = isset($params['periode']) && array_key_exists($params['periode'], $periode_options)
        ? $params['periode']
        : 'aankomend';

    $tax_terms = [];
    foreach (ownframework_agenda_get_taxonomies() as $slug => $tax) {
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
        'search' => $search,
        'orderby' => $orderby,
        'order' => $order,
        'periode' => $periode,
        'tax_terms' => $tax_terms,
    ];
}

/**
 * @param array<string, list<string>> $tax_terms
 * @param string $skip_taxonomy Leave out this taxonomy's own filter — used to compute
 *                               faceted counts ("results if you also picked this option").
 */
function ownframework_agenda_build_tax_query(array $tax_terms, string $skip_taxonomy = ''): array
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
 * A "meerdaags" moment counts as "aankomend" until its OWN end date passes,
 * not its start date — so a running vacation stays visible for its whole
 * duration instead of dropping out of the upcoming list on day two. Both
 * sides are plain Ymd strings (agenda_sort_end, current_time('Ymd')), so a
 * simple string comparison sorts/compares correctly without a DATE-type
 * meta_query cast.
 */
function ownframework_agenda_build_periode_meta_query(string $periode): array
{
    $today = current_time('Ymd');

    if ($periode === 'afgelopen') {
        return [['key' => 'agenda_sort_end', 'value' => $today, 'compare' => '<']];
    }
    if ($periode === 'alle') {
        return [];
    }

    return [['key' => 'agenda_sort_end', 'value' => $today, 'compare' => '>=']];
}

/**
 * Standalone query — used by the editor preview and the REST endpoint, i.e.
 * whenever the block isn't riding WordPress' own main query.
 */
function ownframework_agenda_new_query(array $params, int $paged, int $per_page): WP_Query
{
    $parsed = ownframework_agenda_parse_params($params);

    $args = [
        'post_type' => OWNFRAMEWORK_AGENDA_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => max(1, $per_page),
        'paged' => max(1, $paged),
        'order' => $parsed['order'],
    ];

    if ($parsed['orderby'] === 'title') {
        $args['orderby'] = 'title';
    } else {
        $args['meta_key'] = 'agenda_sort_start';
        $args['orderby'] = 'meta_value';
    }

    if ($parsed['search'] !== '') {
        $args['s'] = $parsed['search'];
    }

    $tax_query = ownframework_agenda_build_tax_query($parsed['tax_terms']);
    $periode_meta_query = ownframework_agenda_build_periode_meta_query($parsed['periode']);

    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    if (!empty($periode_meta_query)) {
        $args['meta_query'] = $periode_meta_query;
    }

    return new WP_Query($args);
}

/**
 * The archive's MAIN query (the initial, no-JS page load) is built by
 * WordPress itself before acf/agenda ever renders — re-derives the block's
 * own "Momenten per pagina" attribute from the (possibly Site-Editor-
 * overridden) template content, same mechanism/reasoning as blocks/
 * vacatures/hooks/query.php's own ownframework_vacatures_get_configured_per_page().
 */
function ownframework_agenda_get_configured_per_page(): int
{
    $default = 10;

    $template = get_block_template(get_stylesheet() . '//archive-agenda', 'wp_template');
    if (!$template || empty($template->content)) {
        return $default;
    }

    $per_page = ownframework_agenda_find_block_attr(parse_blocks($template->content), 'acf/agenda', 'perPage');

    return $per_page !== null ? max(1, (int) $per_page) : $default;
}

/**
 * @param array<int, array{blockName: ?string, attrs: array, innerBlocks: array}> $blocks parse_blocks() output.
 */
function ownframework_agenda_find_block_attr(array $blocks, string $block_name, string $attr_name)
{
    foreach ($blocks as $block) {
        if (($block['blockName'] ?? '') === $block_name) {
            return $block['attrs'][$attr_name] ?? null;
        }
        if (!empty($block['innerBlocks'])) {
            $found = ownframework_agenda_find_block_attr($block['innerBlocks'], $block_name, $attr_name);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

/**
 * Lets a shared/bookmarked filtered archive URL (?agenda-categorie[]=...,
 * ?periode=afgelopen, ?search=...) work through WordPress' own archive query
 * and pagination without any JavaScript — same relationship as blocks/
 * vacatures/hooks/query.php's own pre_get_posts hook.
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query() || !is_post_type_archive(OWNFRAMEWORK_AGENDA_POST_TYPE)) {
        return;
    }

    $parsed = ownframework_agenda_parse_params($_GET);

    if ($parsed['search'] !== '') {
        $query->set('s', $parsed['search']);
    }

    $tax_query = ownframework_agenda_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }

    $periode_meta_query = ownframework_agenda_build_periode_meta_query($parsed['periode']);
    if (!empty($periode_meta_query)) {
        $query->set('meta_query', $periode_meta_query);
    }

    if ($parsed['orderby'] === 'title') {
        $query->set('orderby', 'title');
    } else {
        $query->set('meta_key', 'agenda_sort_start');
        $query->set('orderby', 'meta_value');
    }
    $query->set('order', $parsed['order']);
    $query->set('posts_per_page', ownframework_agenda_get_configured_per_page());
});

/**
 * Same ±1-window-with-ellipsis pagination as blocks/vacatures/hooks/
 * query.php's own ownframework_vacatures_pagination_pages().
 *
 * @return list<int|string>
 */
function ownframework_agenda_pagination_pages(int $current_page, int $max_pages): array
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
 * No layout wrapper of its own around the cards — render.php's own
 * "data-own-agenda-results" div is what owns the list/grid layout classes
 * (via Alpine's ":class", toggled by the "Weergave" buttons), and that div
 * is never replaced wholesale, only its innerHTML (this function's return
 * value) — so the layout choice survives every filter/sort/page fetch,
 * while the cards themselves stay layout-agnostic here.
 */
function ownframework_agenda_render_cards(WP_Query $query): string
{
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('blocks/agenda/parts/card');
        }
    } else {
        // "col-span-full": stays a single, page-wide message even when the
        // results container is currently in "grid" mode, instead of being
        // squeezed into one grid cell's width.
        echo '<div class="own-agenda-empty col-span-full text-center py-12">';
        echo '<p class="text-gray-600">' . esc_html__('Er zijn geen agenda-momenten gevonden die aan je zoekopdracht voldoen.', 'own-framework') . '</p>';
        echo '</div>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function ownframework_agenda_render_pagination(int $current_page, int $max_pages): string
{
    $pages = ownframework_agenda_pagination_pages($current_page, $max_pages);
    if (empty($pages)) {
        return '';
    }

    ob_start();
    ?>
    <nav class="own-agenda-pagination mt-10 flex justify-center" aria-label="<?php esc_attr_e('Paginering', 'own-framework'); ?>">
        <div class="flex gap-2 items-center">
            <?php if ($current_page > 1) : ?>
                <button type="button" data-own-agenda-page="<?= (int) ($current_page - 1); ?>" class="own-agenda-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-agenda-surface text-gray-900 hover:bg-gray-50">
                    <span class="sr-only"><?php esc_html_e('Vorige', 'own-framework'); ?></span>
                    <?= ownframework_get_icon('chevron-right', 'w-4 h-4 rotate-180'); ?>
                </button>
            <?php endif; ?>

            <?php foreach ($pages as $page) : ?>
                <?php if ($page === '...') : ?>
                    <span class="relative inline-flex h-9 items-center px-3.5 text-sm text-gray-500 select-none">&hellip;</span>
                <?php elseif ($page === $current_page) : ?>
                    <span aria-current="page" class="own-agenda-page-current relative z-10 inline-flex h-9 items-center rounded-xl px-3.5 text-sm font-semibold text-white">
                        <?= (int) $page; ?>
                    </span>
                <?php else : ?>
                    <button type="button" data-own-agenda-page="<?= (int) $page; ?>" class="own-agenda-page-btn relative inline-flex h-9 items-center rounded-xl own-agenda-surface px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                        <?= (int) $page; ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $max_pages) : ?>
                <button type="button" data-own-agenda-page="<?= (int) ($current_page + 1); ?>" class="own-agenda-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-agenda-surface text-gray-900 hover:bg-gray-50">
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
 * Faceted counts for the taxonomy groups — identical reasoning/shape to
 * blocks/vacatures/hooks/query.php's own ownframework_vacatures_facet_counts():
 * for taxonomy A, count using every active filter EXCEPT taxonomy A's own
 * selection, so an unchecked option's number reflects "results if you also
 * pick this one" rather than the current (already-filtered-by-A) count.
 * The current "periode" selection is always applied here (never skipped) —
 * a taxonomy count should reflect the period you're currently looking at.
 *
 * @param array<string, list<string>> $tax_terms
 * @return array<string, array<string, int>>
 */
function ownframework_agenda_facet_counts(string $search, array $tax_terms, string $periode): array
{
    global $wpdb;

    $taxonomy_counts = [];
    $periode_meta_query = ownframework_agenda_build_periode_meta_query($periode);

    foreach (array_keys(ownframework_agenda_get_filter_data()) as $taxonomy) {
        $count_args = [
            'post_type' => OWNFRAMEWORK_AGENDA_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        if ($search !== '') {
            $count_args['s'] = $search;
        }
        if (!empty($periode_meta_query)) {
            $count_args['meta_query'] = $periode_meta_query;
        }

        $tax_query = ownframework_agenda_build_tax_query($tax_terms, $taxonomy);
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

/**
 * Counts for the three "periode" options themselves — each ignores its OWN
 * selection (same "skip yourself" reasoning as the taxonomy facets above),
 * so "Afgelopen (12)" stays accurate regardless of which periode is
 * currently active.
 *
 * @param array<string, list<string>> $tax_terms
 * @return array<string, int>
 */
function ownframework_agenda_periode_counts(string $search, array $tax_terms): array
{
    $tax_query = ownframework_agenda_build_tax_query($tax_terms);
    $counts = [];

    foreach (array_keys(ownframework_agenda_get_periode_options()) as $periode) {
        $args = [
            'post_type' => OWNFRAMEWORK_AGENDA_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ];

        if ($search !== '') {
            $args['s'] = $search;
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $periode_meta_query = ownframework_agenda_build_periode_meta_query($periode);
        if (!empty($periode_meta_query)) {
            $args['meta_query'] = $periode_meta_query;
        }

        $query = new WP_Query($args);
        $counts[$periode] = (int) $query->found_posts;
        wp_reset_postdata();
    }

    return $counts;
}

add_action('rest_api_init', function () {
    register_rest_route('own-agenda/v1', '/filter', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ownframework_agenda_rest_filter',
        'permission_callback' => '__return_true',
        'args' => [
            'search' => ['type' => 'string'],
            'orderby' => ['type' => 'string'],
            'order' => ['type' => 'string'],
            'periode' => ['type' => 'string'],
            'paged' => ['type' => 'integer'],
            'per_page' => ['type' => 'integer'],
        ],
    ]);
});

function ownframework_agenda_rest_filter(WP_REST_Request $request): WP_REST_Response
{
    $params = $request->get_params();
    $paged = max(1, (int) $request->get_param('paged'));
    $per_page = (int) $request->get_param('per_page') ?: 10;

    $query = ownframework_agenda_new_query($params, $paged, $per_page);
    $parsed = ownframework_agenda_parse_params($params);

    $response = [
        'success' => true,
        'count' => $query->found_posts,
        'max_pages' => $query->max_num_pages,
        'html' => ownframework_agenda_render_cards($query),
        'pagination_html' => ownframework_agenda_render_pagination($paged, $query->max_num_pages),
        'taxonomy_counts' => ownframework_agenda_facet_counts($parsed['search'], $parsed['tax_terms'], $parsed['periode']),
        'periode_counts' => ownframework_agenda_periode_counts($parsed['search'], $parsed['tax_terms']),
    ];

    return new WP_REST_Response($response);
}

/**
 * Localized onto "theme-scripts" (inc/functions/enqueue-scripts-styles.php's
 * already-enqueued Vite bundle) — same pattern/reasoning as blocks/vacatures/
 * hooks/query.php's own window.OwnVacatures.
 */
add_action('wp_enqueue_scripts', function () {
    wp_localize_script('theme-scripts', 'OwnAgenda', [
        'endpoint' => rest_url('own-agenda/v1/filter'),
        'archiveUrl' => get_post_type_archive_link(OWNFRAMEWORK_AGENDA_POST_TYPE) ?: home_url('/'),
    ]);
});
