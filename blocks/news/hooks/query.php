<?php
defined('ABSPATH') || exit();

/**
 * Query/filter logic for acf/news, shared by three callers: the initial
 * front-end render (render.php), the REST endpoint that powers live filtering
 * (Alpine, src/js/scripts/news-filter.js) and the pre_get_posts filter below
 * (so a bookmarked/shared filtered URL still works with JavaScript off) —
 * same three-caller split as blocks/vacatures/hooks/query.php, structurally
 * the closest match of the other two CPTs (no periode/month-style second
 * facet like agenda has).
 *
 * Deliberately built on the NATIVE `post` post type and its NATIVE
 * "category" taxonomy — the confirmed decision for this feature was to
 * reuse what WordPress already ships rather than register a new custom
 * taxonomy the way agenda/vacancy each have their own. "Taxonomies" below is
 * still a keyed array of ONE entry (shaped exactly like vacatures'/agenda's
 * own dynamically-discovered set) rather than a single hardcoded constant,
 * so this file's own parse_params/build_tax_query/facet_counts bodies can
 * stay near-identical copies of the other two CPTs' — the same "duplicate
 * per family rather than share" convention those two already use with each
 * other (see e.g. blocks/agenda/hooks/query.php's own pagination/rendering
 * functions).
 */

const OWNFRAMEWORK_NEWS_TAXONOMY = 'category';

/**
 * @return array<string, WP_Taxonomy> Keyed by taxonomy slug — always just
 *                                    "category" for this CPT.
 */
function ownframework_news_get_taxonomies(): array
{
    $category = get_taxonomy(OWNFRAMEWORK_NEWS_TAXONOMY);

    return $category ? [OWNFRAMEWORK_NEWS_TAXONOMY => $category] : [];
}

/**
 * @return array<string, array{name: string, label: string, options: list<array{value: string, label: string, count: int}>}>
 */
function ownframework_news_get_filter_data(): array
{
    $filter_data = [];

    foreach (ownframework_news_get_taxonomies() as $slug => $tax) {
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
function ownframework_news_parse_params(array $params): array
{
    $allowed_orderby = ['date' => 'date', 'title' => 'title'];
    $orderby = $allowed_orderby[$params['orderby'] ?? ''] ?? 'date';
    $order = strtoupper((string) ($params['order'] ?? '')) === 'ASC' ? 'ASC' : 'DESC';

    // Reads "category[]=slug-a&category[]=slug-b" (bracketed), not a bare
    // repeated "category=a&category=b" — PHP only keeps the LAST value for a
    // repeated non-bracketed key once it populates $_GET/$request->get_params()
    // (verified directly: parse_str("category=a&category=b") gives "b", not
    // an array), so a genuine multi-select facet needs the "[]" to survive as
    // more than one value. src/js/scripts/news-filter.js's own updateUrl()/
    // apply() send it that way for exactly this reason.
    $tax_terms = [];
    foreach (ownframework_news_get_taxonomies() as $slug => $tax) {
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
function ownframework_news_build_tax_query(array $tax_terms, string $skip_taxonomy = ''): array
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
function ownframework_news_new_query(array $params, int $paged, int $per_page): WP_Query
{
    $parsed = ownframework_news_parse_params($params);

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => max(1, $per_page),
        'paged' => max(1, $paged),
        'orderby' => $parsed['orderby'],
        'order' => $parsed['order'],
    ];

    $tax_query = ownframework_news_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }

    return new WP_Query($args);
}

/**
 * The archive's MAIN query (the initial, no-JS page load) is built by
 * WordPress itself before acf/news ever renders — re-derives the block's own
 * "Berichten per pagina" attribute from the (possibly Site-Editor-
 * overridden) "home" template content, same mechanism/reasoning as
 * blocks/vacatures/hooks/query.php's own
 * ownframework_vacatures_get_configured_per_page() (CLAUDE.md Traps #29).
 */
function ownframework_news_get_configured_per_page(): int
{
    $default = 10;

    $template = get_block_template(get_stylesheet() . '//home', 'wp_template');
    if (!$template || empty($template->content)) {
        return $default;
    }

    $per_page = ownframework_news_find_block_attr(parse_blocks($template->content), 'acf/news', 'perPage');

    return $per_page !== null ? max(1, (int) $per_page) : $default;
}

/**
 * @param array<int, array{blockName: ?string, attrs: array, innerBlocks: array}> $blocks parse_blocks() output.
 */
function ownframework_news_find_block_attr(array $blocks, string $block_name, string $attr_name)
{
    foreach ($blocks as $block) {
        if (($block['blockName'] ?? '') === $block_name) {
            return $block['attrs'][$attr_name] ?? null;
        }
        if (!empty($block['innerBlocks'])) {
            $found = ownframework_news_find_block_attr($block['innerBlocks'], $block_name, $attr_name);
            if ($found !== null) {
                return $found;
            }
        }
    }

    return null;
}

/**
 * Lets a shared/bookmarked filtered URL (?category[]=..., ?orderby=title)
 * work through WordPress' own blog-index query and pagination without any
 * JavaScript — same relationship as blocks/vacatures/hooks/query.php's own
 * pre_get_posts hook. Restricted to is_home() (the site's own "posts page"),
 * NOT is_archive()/is_category(): a category/tag/date/search archive still
 * falls back to templates/index.html's plain native Query Loop, untouched by
 * this feature.
 */
add_action('pre_get_posts', function (WP_Query $query) {
    if (is_admin() || !$query->is_main_query() || !is_home()) {
        return;
    }

    $parsed = ownframework_news_parse_params($_GET);

    $tax_query = ownframework_news_build_tax_query($parsed['tax_terms']);
    if (!empty($tax_query)) {
        $query->set('tax_query', $tax_query);
    }

    $query->set('orderby', $parsed['orderby']);
    $query->set('order', $parsed['order']);
    $query->set('posts_per_page', ownframework_news_get_configured_per_page());
});

/**
 * Same ±1-window-with-ellipsis pagination as blocks/vacatures/hooks/
 * query.php's own ownframework_vacatures_pagination_pages().
 *
 * @return list<int|string>
 */
function ownframework_news_pagination_pages(int $current_page, int $max_pages): array
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
 * "data-own-news-results" div is what owns the list/grid layout classes (via
 * Alpine's ":class", toggled by the "Weergave" buttons), and that div is
 * never replaced wholesale, only its innerHTML (this function's return
 * value) — so the layout choice survives every filter/sort/page fetch.
 */
function ownframework_news_render_cards(WP_Query $query, ?array $parsed = null): string
{
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('blocks/news/parts/card');
        }
    } else {
        // Two different situations: filters that match nothing (offer to
        // clear them) or nothing published yet (just say so). The REST
        // callback passes its own parsed params; the first page load reads
        // them from the URL. The empty state spans the whole results grid
        // itself (empty-state.css).
        $parsed ??= ownframework_news_parse_params($_GET);
        if (!empty($parsed['tax_terms'])) {
            $empty = [
                'title' => __('Geen berichten gevonden', 'own-framework'),
                'text' => __('Er zijn geen berichten die bij deze filters passen.', 'own-framework'),
                'action' => [
                    'label' => __('Filters wissen', 'own-framework'),
                    'alpine' => '$store.newsFilters.clearTaxonomyFilters()',
                ],
            ];
        } else {
        $empty = [
            'title' => __('Nog geen nieuws', 'own-framework'),
            'text' => __('Nieuwe berichten verschijnen hier zodra ze gepubliceerd zijn.', 'own-framework'),
        ];
        }
        get_template_part('template-parts/components/empty-state', null, $empty);
    }

    wp_reset_postdata();

    return ob_get_clean();
}

function ownframework_news_render_pagination(int $current_page, int $max_pages): string
{
    $pages = ownframework_news_pagination_pages($current_page, $max_pages);
    if (empty($pages)) {
        return '';
    }

    ob_start();
    ?>
    <nav class="own-news-pagination mt-10 flex justify-center" aria-label="<?php esc_attr_e('Paginering', 'own-framework'); ?>">
        <div class="flex gap-2 items-center">
            <?php if ($current_page > 1) : ?>
                <button type="button" data-own-news-page="<?= (int) ($current_page - 1); ?>" class="own-news-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-news-surface text-gray-900 hover:bg-gray-50">
                    <span class="sr-only"><?php esc_html_e('Vorige', 'own-framework'); ?></span>
                    <?= ownframework_get_icon('chevron-right', 'w-4 h-4 rotate-180'); ?>
                </button>
            <?php endif; ?>

            <?php foreach ($pages as $page) : ?>
                <?php if ($page === '...') : ?>
                    <span class="relative inline-flex h-9 items-center px-3.5 text-sm text-gray-500 select-none">&hellip;</span>
                <?php elseif ($page === $current_page) : ?>
                    <span aria-current="page" class="own-news-page-current relative z-10 inline-flex h-9 items-center rounded-xl px-3.5 text-sm font-semibold text-white">
                        <?= (int) $page; ?>
                    </span>
                <?php else : ?>
                    <button type="button" data-own-news-page="<?= (int) $page; ?>" class="own-news-page-btn relative inline-flex h-9 items-center rounded-xl own-news-surface px-3.5 text-sm font-semibold text-gray-900 hover:bg-gray-50">
                        <?= (int) $page; ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $max_pages) : ?>
                <button type="button" data-own-news-page="<?= (int) ($current_page + 1); ?>" class="own-news-page-btn relative inline-flex h-9 w-9 items-center justify-center rounded-xl own-news-surface text-gray-900 hover:bg-gray-50">
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
 *
 * @param array<string, list<string>> $tax_terms
 * @return array<string, array<string, int>>
 */
function ownframework_news_facet_counts(array $tax_terms): array
{
    global $wpdb;

    $taxonomy_counts = [];

    foreach (array_keys(ownframework_news_get_filter_data()) as $taxonomy) {
        $count_args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        $tax_query = ownframework_news_build_tax_query($tax_terms, $taxonomy);
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
    register_rest_route('own-news/v1', '/filter', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ownframework_news_rest_filter',
        'permission_callback' => '__return_true',
        'args' => [
            'orderby' => ['type' => 'string'],
            'order' => ['type' => 'string'],
            'category' => ['type' => 'array', 'items' => ['type' => 'string']],
            'paged' => ['type' => 'integer'],
            'per_page' => ['type' => 'integer'],
        ],
    ]);
});

function ownframework_news_rest_filter(WP_REST_Request $request): WP_REST_Response
{
    $params = $request->get_params();
    $paged = max(1, (int) $request->get_param('paged'));
    $per_page = (int) $request->get_param('per_page') ?: 10;

    $query = ownframework_news_new_query($params, $paged, $per_page);
    $parsed = ownframework_news_parse_params($params);

    $response = [
        'success' => true,
        'count' => $query->found_posts,
        'max_pages' => $query->max_num_pages,
        'html' => ownframework_news_render_cards($query, $parsed),
        'pagination_html' => ownframework_news_render_pagination($paged, $query->max_num_pages),
        'taxonomy_counts' => ownframework_news_facet_counts($parsed['tax_terms']),
    ];

    return new WP_REST_Response($response);
}

/**
 * Localized onto "theme-scripts" (inc/functions/enqueue-scripts-styles.php's
 * already-enqueued Vite bundle), same pattern as blocks/vacatures/hooks/
 * query.php's own window.OwnVacatures. Resolves the site's actual "posts
 * page" URL (Instellingen → Lezen → "Berichtenpagina") when one is set,
 * falling back to the homepage — get_post_type_archive_link() (what the
 * other two CPTs use) doesn't apply here, `post` has no CPT-style archive
 * link of its own.
 */
add_action('wp_enqueue_scripts', function () {
    $posts_page_id = (int) get_option('page_for_posts');

    wp_localize_script('theme-scripts', 'OwnNews', [
        'endpoint' => rest_url('own-news/v1/filter'),
        'archiveUrl' => $posts_page_id ? get_permalink($posts_page_id) : home_url('/'),
    ]);
});
