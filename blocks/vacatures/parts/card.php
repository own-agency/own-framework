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
 * template change needed. Each badge's 'icon' (ownframework_vacature_taxonomy_
 * icon(), inc/functions/vacature-taxonomy-settings.php) is echoed unescaped
 * further down on purpose — raw SVG sanitized with wp_kses on save, not at
 * output time.
 *
 * Placed below the excerpt/logo row rather than next to the title: that top
 * row is width-constrained (title competes with the date on mobile, logo on
 * desktop), while the strip above the "Vacature bekijken" link runs the full
 * card width — the natural place for a row of badges that can grow past a
 * handful once more taxonomies are enabled.
 *
 * Title/text/background colour (and the link's own colour, further down)
 * read acf/vacatures' own "Vacature-kaart"-attributes (render.php, editor.js
 * "Kleuren" panel) as CSS custom properties set on that block's wrapper —
 * badges keep using the SAME block's separate "Accentkleur" attribute
 * (--own-vacatures-accent) instead, so the two can be recoloured
 * independently from the same block's own inspector.
 *
 * The root <a> is "flex flex-col" and the "Vacature bekijken" row uses
 * "mt-auto" rather than a plain top margin, so the card keeps that link
 * anchored to the bottom regardless of how much title/excerpt/badge content
 * sits above it, WHENEVER something actually stretches the card to a taller
 * box than its own content needs — CSS grid's own default
 * "align-items: stretch" already does this for free in the plain "grid
 * grid-cols-*" layout (acf/vacatures-uitgelicht's "Raster" weergave), no
 * extra CSS needed there.
 *
 * The slider layout needs one more thing, deliberately NOT a percentage
 * height on the card itself: src/css/components/swiper.css's own
 * ".own-vacatures-uitgelicht .swiper-slide { display: flex }" (same recipe
 * as ".own-content-slider"/".own-facts-slider" there) makes the SLIDE a flex
 * container with the card as its one flex child, which stretches the card via
 * that same flexbox default rather than "height:100%" — a percentage height
 * needs a DEFINITE ancestor height to resolve against, which a
 * flex-stretched ".swiper-slide" doesn't reliably provide (that file's own
 * docblock has the full reasoning); flex-child stretch works regardless.
 *
 * "no-underline" on the root <a>: CSS text-decoration set on an ancestor
 * paints through every descendant that doesn't override it, so without this
 * the browser's default link underline showed under the title/excerpt/date
 * text too, not just the "Vacature bekijken" link — that one keeps its own
 * "group-hover:underline!" (the "!" wins over the ancestor's "none" on hover).
 */

$posted_timestamp = (int) get_the_date('U');
$time_ago = human_time_diff($posted_timestamp, current_time('timestamp'));

$badges = [];
foreach (ownframework_vacatures_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms(get_the_ID(), $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    $icon = ownframework_vacature_taxonomy_icon($slug);
    foreach ($terms as $term) {
        $badges[] = ['label' => $term->name, 'icon' => $icon];
    }
}

/*
 * Explicit post ID, not get_field('logo'): this template is called both from
 * a live SCF block render (initial archive load, via render.php's own
 * block-render callback) and from a plain PHP loop with no active block
 * context (the REST-backed filter endpoint, hooks/query.php's
 * ownframework_vacatures_rest_filter()). ACF's own block rendering
 * (secure-custom-fields/includes/local-meta.php's ACF_Local_Meta) makes
 * get_field() calls WITHOUT a post_id resolve against the BLOCK's own pseudo
 * post ID rather than get_the_ID() whenever a block is actively rendering —
 * so the un-scoped call silently returned the block's own (nonexistent)
 * "logo" value on first paint, and only worked when re-rendered outside a
 * block context via the filter bar.
 */
$logo = get_field('logo', get_the_ID());
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-vacatures-card own-vacatures-surface group flex flex-col no-underline bg-(--own-vacatures-card-bg-color) rounded-2xl overflow-hidden">
    <article class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
        <div>
            <h2 class="text-lg lg:text-xl font-semibold text-(--own-vacatures-card-title-color) m-0! own-vacatures-card__title">
                <?= esc_html(get_the_title()); ?>
            </h2>
            <div class="mt-1.5 flex items-center text-sm text-(--own-vacatures-card-text-color)">
                <span title="<?= esc_attr(get_the_date()); ?>"><?= esc_html($time_ago); ?> <?php esc_html_e('geleden geplaatst', 'own-framework'); ?></span>
            </div>
        </div>

        <div class="mt-4 flex flex-col lg:flex-row items-start justify-between gap-3 lg:gap-4">
            <div class="prose prose-sm text-(--own-vacatures-card-text-color) line-clamp-2 order-2 lg:order-1">
                <?= wp_kses_post(get_the_excerpt()); ?>
            </div>
            <?php if (!empty($logo['url'])) : ?>
                <?php // Explicit width/height (not just CSS max-h-8/max-w-28 + w-auto/
                // h-auto): a logo upload is very often an SVG, and without these two
                // HTML attributes some browsers fall back to a default intrinsic size
                // (historically 300x150) for a replaced element before the stylesheet
                // has resolved, instead of the file's own real ratio — shrink-0 stops
                // the flex row above from ever squeezing it narrower than its own
                // max-w-28 while the (potentially long) excerpt text competes for
                // space. 
                ?>
                <img
                    class="max-h-8 lg:max-h-10 max-w-28 w-auto h-auto shrink-0 order-1 lg:order-2 m-0!"
                    src="<?= esc_url($logo['url']); ?>"
                    width="<?= (int) ($logo['width'] ?? 112); ?>"
                    height="<?= (int) ($logo['height'] ?? 40); ?>"
                    alt="<?= esc_attr($logo['alt'] ?? ''); ?>">
            <?php endif; ?>
        </div>

        <div class="mt-auto">
            <?php if (!empty($badges)) : ?>
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-wrap gap-2">
                    <?php foreach ($badges as $badge) : ?>
                        <span class="own-vacatures-badge inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium">
                            <?php if (!empty($badge['icon'])) : ?>
                                <span class="own-vacatures-tax-icon w-3.5 h-3.5 shrink-0" aria-hidden="true"><?= $badge['icon']; ?></span>
                            <?php endif; ?>
                            <?= esc_html($badge['label']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between pt-5">
                <span class="own-vacatures-card__link inline-flex items-center gap-1 text-sm font-medium group-hover:underline!">
                    <?php esc_html_e('Vacature bekijken', 'own-framework'); ?>
                    <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
                </span>
            </div>
        </div>
    </article>
</a>