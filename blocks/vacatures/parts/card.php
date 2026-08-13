<?php
defined('ABSPATH') || exit();

/**
 * One vacature in the results grid — called from a live WP_Query loop
 * (ownframework_vacatures_render_cards(), hooks/query.php), the_post() already
 * ran for the current item.
 *
 * Badges show every taxonomy term attached to THIS vacancy across every
 * taxonomy registered on the post type (ownframework_vacatures_get_taxonomies())
 * — not just "dienstverband" hardcoded — same reasoning as the filter sidebar
 * itself: a second taxonomy added later via SCF UI shows up here too, no
 * template change needed.
 */

$posted_timestamp = (int) get_the_date('U');
$time_ago = human_time_diff($posted_timestamp, current_time('timestamp'));

$badges = [];
foreach (ownframework_vacatures_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms(get_the_ID(), $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    foreach ($terms as $term) {
        $badges[] = $term->name;
    }
}

$logo = get_field('logo');
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-vacatures-card block group bg-white rounded-2xl ring-1 ring-gray-200 hover:ring overflow-hidden">
    <article class="p-4 sm:p-5 lg:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
            <div>
                <h2 class="text-lg lg:text-xl font-semibold text-gray-900 m-0! own-vacatures-card__title">
                    <?= esc_html(get_the_title()); ?>
                </h2>
                <div class="mt-1.5 flex items-center text-sm text-gray-500">
                    <span title="<?= esc_attr(get_the_date()); ?>"><?= esc_html($time_ago); ?> <?php esc_html_e('geleden geplaatst', 'own-framework'); ?></span>
                </div>
            </div>

            <?php if (!empty($badges)) : ?>
                <div class="flex flex-wrap gap-2 shrink-0">
                    <?php foreach ($badges as $badge) : ?>
                        <span class="own-vacatures-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            <?= esc_html($badge); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 flex flex-col lg:flex-row items-start justify-between gap-3 lg:gap-4">
            <div class="prose prose-sm text-gray-500 line-clamp-2 order-2 lg:order-1">
                <?= wp_kses_post(get_the_excerpt()); ?>
            </div>
            <?php if (!empty($logo['url'])) : ?>
                <img class="max-h-8 lg:max-h-10 max-w-28 w-auto h-auto order-1 lg:order-2 m-0!" src="<?= esc_url($logo['url']); ?>" alt="<?= esc_attr($logo['alt'] ?? ''); ?>">
            <?php endif; ?>
        </div>

        <div class="mt-5 flex items-center justify-between">
            <span class="own-vacatures-card__link inline-flex items-center gap-1 text-sm font-medium">
                <?php esc_html_e('Vacature bekijken', 'own-framework'); ?>
                <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
            </span>
        </div>
    </article>
</a>