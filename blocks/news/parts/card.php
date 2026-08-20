<?php
defined('ABSPATH') || exit();

/**
 * One news post in the results grid — called from a live WP_Query loop
 * (ownframework_news_render_cards(), hooks/query.php), the_post() already ran
 * for the current item. Same overall shape as blocks/vacatures/parts/card.php
 * (title/date/excerpt/badges/link), but leads with the post's own featured
 * image (native `has_post_thumbnail()`/`get_the_post_thumbnail()`) instead of
 * a side logo — a genuinely new element for this card family, since neither
 * vacancy's logo (a manually-managed ACF field) nor agenda's date badge are
 * a featured image, and a leading image is the conventional shape for a news/
 * blog card. Optional: a post without one just gets a slightly shorter card,
 * not a broken/empty image box.
 *
 * Category badges use plain get_the_category() (native, not a taxonomy loop
 * like vacatures/agenda's own dynamically-discovered taxonomies) — this CPT
 * deliberately only ever filters/tags on WordPress' own "Categorie", per the
 * theme-wide decision to reuse the native taxonomy rather than invent a new
 * one (see blocks/news/hooks/query.php's own docblock). A post's own tags
 * (get_the_tags(), also native) are shown too, when set — display-only, own
 * ".own-news-tag" style deliberately more muted than the accent-coloured
 * category badges (tags aren't a filterable facet here, just extra context),
 * so the two never read as the same kind of label.
 *
 * Title/text/background/link colours read acf/news' own "Berichten-kaart"
 * attributes (render.php, editor.js "Kleuren" panel) as CSS custom
 * properties on that block's wrapper — same reasoning as acf/vacatures' own
 * card (CLAUDE.md Traps #7).
 *
 * "no-underline" on the root <a>: same reason as blocks/vacatures/parts/
 * card.php's own docblock — an ancestor's text-decoration paints through
 * every descendant unless overridden, so without this the title/excerpt/date
 * would show an underline too, not just the "Lees meer" link.
 */

$post_id = get_the_ID();

$categories = get_the_category($post_id);
$tags = get_the_tags($post_id);
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-news-card own-news-surface group flex flex-col no-underline bg-(--own-news-card-bg-color) rounded-2xl overflow-hidden">
    <?php if (has_post_thumbnail($post_id)) : ?>
        <div class="aspect-video overflow-hidden">
            <?= get_the_post_thumbnail($post_id, 'large', ['class' => 'w-full h-full object-cover m-0!']); ?>
        </div>
    <?php endif; ?>

    <article class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
        <?php if (!empty($categories)) : ?>
            <div class="flex flex-wrap gap-2 mb-3">
                <?php foreach ($categories as $category) : ?>
                    <span class="own-news-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                        <?= esc_html($category->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 class="text-lg lg:text-xl font-semibold text-(--own-news-card-title-color) m-0! own-news-card__title">
            <?= esc_html(get_the_title()); ?>
        </h2>
        <p class="mt-1.5 text-sm text-(--own-news-card-text-color)">
            <?= esc_html(get_the_date()); ?>
        </p>

        <div class="mt-3 prose prose-sm text-(--own-news-card-text-color) line-clamp-3">
            <?= wp_kses_post(get_the_excerpt()); ?>
        </div>

        <?php if (!empty($tags)) : ?>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($tags as $tag) : ?>
                    <span class="own-news-tag inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium">
                        #<?= esc_html($tag->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-auto pt-5">
            <span class="own-news-card__link inline-flex items-center gap-1 text-sm font-medium group-hover:underline!">
                <?php esc_html_e('Lees meer', 'own-framework'); ?>
                <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
            </span>
        </div>
    </article>
</a>
