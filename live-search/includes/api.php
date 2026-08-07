<?php
defined('ABSPATH') || exit();

add_action('rest_api_init', function () {
	register_rest_route('own-live-search/v1', '/query', [
		'methods' => WP_REST_Server::READABLE,
		'callback' => 'ownframework_live_search_handle_request',
		'permission_callback' => '__return_true',
		'args' => [
			'q' => [
				'required' => true,
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function (string $value): bool {
					return mb_strlen(trim($value)) >= (int) OWNFRAMEWORK_LIVE_SEARCH_CONFIG['min_chars'];
				},
			],
		],
	]);
});

function ownframework_live_search_handle_request(WP_REST_Request $request): WP_REST_Response
{
	$config = OWNFRAMEWORK_LIVE_SEARCH_CONFIG;
	$term = trim($request->get_param('q'));

	// Short-lived cache: the same term gets re-submitted a lot (keystrokes
	// settling on one debounced value, visitors searching the same thing) and
	// results don't need to be second-fresh.
	$cache_key = 'ownframework_live_search_' . md5($term);
	$cached = get_transient($cache_key);
	if ($cached !== false) {
		return new WP_REST_Response($cached);
	}

	// ── ACF meta_query ──────────────────────────────────────────────────────
	$has_acf = !empty($config['acf_fields']);
	$meta_query = ['relation' => 'OR'];

	if ($has_acf) {
		foreach ($config['acf_fields'] as $field_key) {
			$meta_query[] = [
				'key' => $field_key,
				'value' => $term,
				'compare' => 'LIKE',
			];
		}
	}

	// ── Main query (title + content + excerpt via 's') ──────────────────────
	// no_found_rows: only ->posts is used, so skip WP_Query's found-rows count.
	$query = new WP_Query([
		'post_type' => $config['post_types'],
		'post_status' => 'publish',
		'posts_per_page' => (int) $config['per_page'],
		's' => $term,
		'no_found_rows' => true,
	]);

	// ── Second pass for ACF-only matches not caught by 's' ───────────────────
	// WP_Query ANDs 's' with meta_query rather than ORing them, so one query
	// can't express "matched by title/content OR by ACF field".
	$acf_post_ids = [];
	if ($has_acf) {
		$acf_query = new WP_Query([
			'post_type' => $config['post_types'],
			'post_status' => 'publish',
			'posts_per_page' => (int) $config['per_page'],
			'meta_query' => $meta_query,
			'fields' => 'ids',
			'no_found_rows' => true,
		]);
		$acf_post_ids = $acf_query->posts;
	}

	// Merge + deduplicate
	$all_posts = $query->posts;
	$existing_ids = wp_list_pluck($all_posts, 'ID');
	$extra_ids = array_diff($acf_post_ids, $existing_ids);

	if (!empty($extra_ids)) {
		$extra_posts = get_posts([
			'post__in' => $extra_ids,
			'post_type' => $config['post_types'],
			'post_status' => 'publish',
			'posts_per_page' => count($extra_ids),
			'orderby' => 'post__in',
			'no_found_rows' => true,
		]);
		$all_posts = array_merge($all_posts, $extra_posts);
	}

	$all_posts = array_slice($all_posts, 0, (int) $config['per_page']);

	// ── ACF display fields ───────────────────────────────────────────────────
	$acf_display = !empty($config['acf_display']) ? $config['acf_display'] : $config['acf_fields'];

	// ── Build result objects ─────────────────────────────────────────────────
	$results = [];
	foreach ($all_posts as $post) {
		$acf_data = [];
		if ($has_acf && function_exists('get_field')) {
			foreach ($acf_display as $field_key) {
				$value = get_field($field_key, $post->ID);
				if (!empty($value) && is_string($value)) {
					$acf_data[$field_key] = wp_strip_all_tags($value);
				}
			}
		}

		$thumbnail_url = null;
		if ($config['thumbnail']) {
			$thumbnail_url = get_the_post_thumbnail_url($post->ID, $config['thumbnail_size']) ?: null;
		}

		$post_type_obj = get_post_type_object($post->post_type);

		$results[] = [
			'id' => $post->ID,
			'title' => wp_strip_all_tags(get_the_title($post)),
			'excerpt' => ownframework_live_search_get_excerpt($post, $term),
			'url' => get_permalink($post),
			'type' => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
			'type_slug' => $post->post_type,
			'thumbnail' => $thumbnail_url,
			'acf' => $acf_data,
		];
	}

	$response_data = [
		'results' => $results,
		'total' => count($results),
		'term' => $term,
	];

	set_transient($cache_key, $response_data, 60);

	return new WP_REST_Response($response_data);
}

/**
 * Returns a short excerpt, preferring content that contains the search term.
 */
function ownframework_live_search_get_excerpt(WP_Post $post, string $term): string
{
	$excerpt = $post->post_excerpt
		? wp_strip_all_tags($post->post_excerpt)
		: wp_trim_words(wp_strip_all_tags($post->post_content), 20);

	if (!empty($post->post_content) && stripos($excerpt, $term) === false) {
		$content = wp_strip_all_tags($post->post_content);
		$pos = mb_stripos($content, $term);
		if ($pos !== false) {
			$start = max(0, $pos - 60);
			$excerpt = ($start > 0 ? '…' : '') . mb_substr($content, $start, 140) . '…';
		}
	}

	return $excerpt;
}
