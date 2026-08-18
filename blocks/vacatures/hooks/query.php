<?php
defined('ABSPATH') || exit();

/**
 * Query/filter logic for acf/vacatures, shared by three callers: the initial
 * front-end render (render.php), the REST endpoint that powers live filtering
 * (Alpine, src/js/scripts/vacatures-filter.js) and the pre_get_posts filter
 * below (so a bookmarked/shared filtered URL still works with JavaScript off,
 * via WordPress' own archive pagination). One place to build a tax_query/
 * search clause from a flat params array, rather than three near-identical
 * copies drifting apart.
 *
 * Taxonomies are discovered dynamically (get_object_taxonomies('vacancy')),
 * never hardcoded to "dienstverband" — an editor can register a second
 * taxonomy on this post type later (same SCF UI mechanism the post type and
 * "Dienstverband" itself were registered with) and the filter sidebar picks
 * it up automatically, no code change or extra "which taxonomies" block
 * setting needed.
 */

const OWNFRAMEWORK_VACATURES_POST_TYPE = 'vacancy';

/**
 * @return array<string, WP_Taxonomy> Keyed by taxonomy slug.
 */
function ownframework_vacatures_get_taxonomies(): array
{
    $taxonomies = get_object_taxonomies(OWNFRAMEWORK_VACATURES_POST_TYPE, 'objects');

    return array_filter($taxonomies, fn (WP_Taxonomy $tax): bool => (bool) $tax->show_ui);
}

/**
 * Only taxonomies that currently have at least one term get a filter group —
 * an empty group would just be a heading with nothing under it.
 *
 * @return array<string, array{name: string, label: string, options: list<array{value: string, label: string, count: int}>}>
 */
function ownframework_vacatures_get_filter_data(): array
{
    $filter_data = [];

    foreach (ownframework_vacatures_get_taxonomies() as $slug => $tax) {
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
            'icon' => ownframework_vacature_taxonomy_icon($slug),
            'options' => $options,
        ];
    }

    return $filter_data;
}

/**
 * @param array<string, mixed> $params e.g. $_GET or a REST request's params.
 * @return array{search: string, orderby: string, order: string, tax_terms: array<string, list<string>>}
 */
function ownframework_vacatures_parse_params(array $params): array
{
    $search = isset($params['search']) && !is_array($params['search'])
        ? sanitize_text_field((string) $params['search'])
        : '';

    $allowed_orderby = ['date' => 'date', 'title' => 'title'];
    $orderby = $allowed_orderby[$params['orderby'] ?? ''] ?? 'date';
    $order = strtoupper((string) ($params['order'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';

    $tax_terms = [];
    foreach (ownframework_vacatures_get_taxonomies() as $slug => $tax) {
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
        'tax_terms' => $tax_terms,
    ];
}

/**
 * @param array<string, list<string>> $tax_terms
 * @param string $skip_taxonomy Leave out this taxonomy's own filter — used to compute
 *                               faceted counts ("results if you also picked this option").
 */
function ownframework_vacatures_build_tax_query(array $tax_terms, string $skip_taxonomy = ''): array
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
 * Standalone query — used by the editor preview and the REST endpoint, i.e.
 * whenever the block isn't riding WordPress' own main query.
 */
function ownframework_vacatures_new_query(array $params, int $paged, int $per_page): WP_Query
{
    $parsed = ownframework_vacatures_parse_params($params);

    $args = [
        'post_type' => OWNFRAMEWORK_VACATURES_POST_TYPE,
        'post_status' => 'publish',
        'posts_per_page' => max(1, $per_page),
        'paged' => max(1, $paged),
        'orderby' => $parsed['orderby'],
        'order' => $parsed['order'],
    ];

    if ($parsed['search'] !== '') {
        $args['s'] = $parsed['search'];
    }

    $tax_query = ownframework_vacatures_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    return new WP_Query($args);
}

/**
 * The archive's MAIN query (the initial, no-JS page load) is built by
 * WordPress itself long before acf/vacatures ever renders — its own
 * "posts_per_page" comes from Instellingen → Lezen, not from the block's own
 * "Vacatures per pagina" attribute, which pre_get_posts below has no way to
 * read directly (the block hasn't been parsed yet at this point in the
 * request). This re-derives it by parsing the SAME template content that's
 * about to render: get_block_template() already resolves the database-saved
 * override when the template's been customised in the Site Editor, falling
 * back to the theme file otherwise — exactly the source that ends up on
 * screen, so this can never disagree with what render.php itself reads.
 *
 * Only the very first, main-query page load needs this: every REST-backed
 * filter/sort/pagination action afterwards already carries the block's own
 * perPage straight from render.php's $filters_config (src/js/scripts/
 * vacatures-filter.js's own "per_page" param, see ownframework_vacatures_
 * new_query()'s $per_page argument below) — no template parsing needed there.
 */
function ownframework_vacatures_get_configured_per_page(): int
{
    $default = 10;

    $template = get_block_template(get_stylesheet() . '//archive-vacancy', 'wp_template');
    if (!$template || empty($template->content)) {
        return $default;
    }

    $per_page = ownframework_vacatures_find_block_attr(parse_blocks($template->content), 'acf/vacatures', 'perPage');

    return $per_page !== null ? max(1, (int) $per_page) : $default;
}

/**
 * Depth-first search through a parsed block tree for the first block
 * matching $block_name, returning one of its own $attrs, or null if that
 * block (or the attribute on it) isn't present anywhere in the tree.
 *
 * @param array<int, array{blockName: ?string, attrs: array, innerBlocks: array}> $blocks parse_blocks() output.
 */
function ownframework_vacatures_find_block_attr(array $blocks, string $block_name, string $attr_name)
{
    foreach ($blocks as $block) {
        if (($block['blockName'] ?? '') === $block_name) {
            return $block['attrs'][$attr_name] ?? null;
        }
        if (!empty($block['innerBlocks'])) {
            $found = ownframework_vacatures_find_block_attr($block['innerBlocks'], $block_name, $attr_name);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

/**
 * Lets a shared/bookmarked filtered archive URL (?dienstverband[]=parttime,
 * ?search=...) work through WordPress' own archive query and pagination
 * (/vacatures/page/2/) without any JavaScript — the REST-backed live
 * filtering in src/js/scripts/vacatures-filter.js takes over from there once
 * it loads, same relationship as apply_vacancy_url_filters() had on the
 * theme this was ported from.
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query() || !is_post_type_archive(OWNFRAMEWORK_VACATURES_POST_TYPE)) {
        return;
    }

    $parsed = ownframework_vacatures_parse_params($_GET);

    if ($parsed['search'] !== '') {
        $query->set('s', $parsed['search']);
    }

    $tax_query = ownframework_vacatures_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }

    $query->set('orderby', $parsed['orderby']);
    $query->set('order', $parsed['order']);
    $query->set('posts_per_page', ownframework_vacatures_get_configured_per_page());
});

/**
 * Always anchors first and last page; shows a ±1 window around the current
 * page with an ellipsis for any gap.
 *
 * @return list<int|string>
 */
function ownframework_vacatures_pagination_pages(int $current_page, int $max_pages): array
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

function ownframework_vacatures_render_cards(WP_Query $query): string
{
    ob_start();

    if ($query->have_posts()) {
        echo '<div class="own-vacatures-list space-y-4">';
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('blocks/vacatures/parts/card');
        }
        echo '</div>';
    } else {
        echo '<div class="own-vacatures-empty text-center py-12">';
        echo '<p class="text-gray-600">' . esc_html__('Er zijn geen vacatures gevonden die aan je zoekopdracht voldoen.', 'own-framework') . '</p>';
        echo '</div>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function ownframework_vacatures_render_pagination(int $current_page, int $max_pages): string
{
    $pages = ownframework_vacatures_pagination_pages($current_page, $max_pages);
    if (empty($pages)) {
        return '';
    }

    ob_start();
    ?>
    <nav class="own-vacatures-pagination mt-10 flex justify-center" aria-label="<?php esc_attr_e('Paginering', 'own-framework'); ?>">
        <div class="flex gap-2 items-center">
            <?php if ($current_page > 1) : ?>
                <button type="button" data-own-vacatures-page="<?= (int) ($current_page - 1); ?>" class="own-vacatures-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-vacatures-surface text-gray-900 hover:bg-gray-50">
                    <span class="sr-only"><?php esc_html_e('Vorige', 'own-framework'); ?></span>
                    <?= ownframework_get_icon('chevron-right', 'w-4 h-4 rotate-180'); ?>
                </button>
            <?php endif; ?>

            <?php foreach ($pages as $page) : ?>
                <?php if ($page === '...') : ?>
                    <span class="relative inline-flex h-9 items-center px-3.5 text-sm text-gray-500 select-none">&hellip;</span>
                <?php elseif ($page === $current_page) : ?>
                    <span aria-current="page" class="own-vacatures-page-current relative z-10 inline-flex h-9 items-center rounded-xl px-3.5 text-sm font-semibold text-white">
                        <?= (int) $page; ?>
                    </span>
                <?php else : ?>
                    <button type="button" data-own-vacatures-page="<?= (int) $page; ?>" class="own-vacatures-page-btn relative inline-flex h-9 items-center rounded-xl own-vacatures-surface px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                        <?= (int) $page; ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $max_pages) : ?>
                <button type="button" data-own-vacatures-page="<?= (int) ($current_page + 1); ?>" class="own-vacatures-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-vacatures-surface text-gray-900 hover:bg-gray-50">
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
 * Faceted counts: for taxonomy A, count terms using every active filter
 * EXCEPT taxonomy A's own selection, so the number next to an unchecked
 * option reflects "results if you also pick this one" rather than the
 * (unfiltered-by-A) current result count.
 *
 * @param array<string, list<string>> $tax_terms
 * @return array<string, array<string, int>>
 */
function ownframework_vacatures_facet_counts(string $search, array $tax_terms): array
{
    global $wpdb;

    $taxonomy_counts = [];

    foreach (array_keys(ownframework_vacatures_get_filter_data()) as $taxonomy) {
        $count_args = [
            'post_type' => OWNFRAMEWORK_VACATURES_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        if ($search !== '') {
            $count_args['s'] = $search;
        }

        $tax_query = ownframework_vacatures_build_tax_query($tax_terms, $taxonomy);
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
    register_rest_route('own-vacatures/v1', '/filter', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ownframework_vacatures_rest_filter',
        'permission_callback' => '__return_true',
        'args' => [
            'search' => ['type' => 'string'],
            'orderby' => ['type' => 'string'],
            'order' => ['type' => 'string'],
            'paged' => ['type' => 'integer'],
            'per_page' => ['type' => 'integer'],
        ],
    ]);
});

function ownframework_vacatures_rest_filter(WP_REST_Request $request): WP_REST_Response
{
    $params = $request->get_params();
    $paged = max(1, (int) $request->get_param('paged'));
    $per_page = (int) $request->get_param('per_page') ?: 10;

    $query = ownframework_vacatures_new_query($params, $paged, $per_page);
    $parsed = ownframework_vacatures_parse_params($params);

    $response = [
        'success' => true,
        'count' => $query->found_posts,
        'max_pages' => $query->max_num_pages,
        'html' => ownframework_vacatures_render_cards($query),
        'pagination_html' => ownframework_vacatures_render_pagination($paged, $query->max_num_pages),
        'taxonomy_counts' => ownframework_vacatures_facet_counts($parsed['search'], $parsed['tax_terms']),
    ];

    return new WP_REST_Response($response);
}

/**
 * Localized onto "theme-scripts" (inc/functions/enqueue-scripts-styles.php's
 * already-enqueued Vite bundle), same pattern as live-search/includes/
 * assets.php — this theme ships one JS bundle, and src/js/scripts/
 * vacatures-filter.js (bundled into it) reads window.OwnVacatures at
 * runtime. Always localized (not conditional on has_block()): live-search's
 * own endpoint is localized the same unconditional way, and the alternative
 * (checking has_block() here, in the main query context, before the block
 * has even rendered) is more fragile than the tiny, harmless cost of an
 * unused global on pages without this block.
 */
add_action('wp_enqueue_scripts', function () {
    wp_localize_script('theme-scripts', 'OwnVacatures', [
        'endpoint' => rest_url('own-vacatures/v1/filter'),
        'archiveUrl' => get_post_type_archive_link(OWNFRAMEWORK_VACATURES_POST_TYPE) ?: home_url('/'),
    ]);
});
