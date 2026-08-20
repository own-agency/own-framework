<?php
defined('ABSPATH') || exit();

/**
 * One agenda-moment in the results grid — called from a live WP_Query loop
 * (ownframework_agenda_render_cards(), hooks/query.php), the_post() already
 * ran for the current item. Same overall shape as blocks/vacatures/parts/
 * card.php, minus the logo column (this CPT has no per-post image field).
 *
 * WHEN a moment happens is this CPT's single most important fact — more so
 * than for a vacancy — so it gets its own fixed-position visual "date badge"
 * (day number, month underneath, inc/functions/agenda-helpers.php's own
 * ownframework_agenda_get_date_badge()) instead of a plain text line: a
 * quick-scan shape a visitor's eye catches before reading any title, the
 * same idea as a paper agenda/calendar's own date block. A small caption
 * under the title (ownframework_agenda_get_card_caption()) fills in ONLY
 * what the badge itself can't show (a time, or a range crossing a month) —
 * deliberately not the full formatted sentence, which would just repeat the
 * same date the badge already shows right next to it.
 *
 * Badges show every "agenda-categorie" term on this moment; a small
 * "Meerdaags"/"Aanmelden mogelijk" pill is appended when relevant so both
 * are scannable straight from the results list, without opening the card.
 *
 * Title/text/background/link colours read acf/agenda's own "Agenda-kaart"
 * attributes (render.php, editor.js "Kleuren" panel) as CSS custom
 * properties on that block's wrapper — same reasoning as acf/vacatures'
 * card (CLAUDE.md Traps #7). The date badge itself always uses the block's
 * "Accentkleur" (--own-agenda-accent, same variable the category badges and
 * checkboxes already read), not one of the card-specific colours — it's a
 * calendar-style accent element, not "card text".
 *
 * Jaartal staat BINNEN hetzelfde vak (maand erboven, dag ertussenin groot,
 * jaartal eronder, alle drie met de geërfde accentkleur via "currentColor"/
 * opacity) i.p.v. als los, ongekaderd tekstregeltje ONDER het vierkant —
 * zelfde reden/opzet als blocks/agenda-single-hero/render.php's eigen
 * datumvierkant, hier op de kaart's eigen (kleinere) formaat toegepast.
 */

$post_id = get_the_ID();

$date_badge = ownframework_agenda_get_date_badge($post_id);
$date_caption = ownframework_agenda_get_card_caption($post_id, $date_badge);

$badges = [];
foreach (ownframework_agenda_get_taxonomies() as $slug => $tax) {
    $terms = get_the_terms($post_id, $slug);
    if (empty($terms) || is_wp_error($terms)) {
        continue;
    }
    foreach ($terms as $term) {
        $badges[] = $term->name;
    }
}

$signup = ownframework_agenda_get_signup($post_id);
?>
<a href="<?= esc_url(get_permalink()); ?>" class="own-agenda-card own-agenda-surface group flex flex-col no-underline bg-(--own-agenda-card-bg-color) rounded-2xl overflow-hidden">
    <?php // "items-start": the badge sits level with the title at the top of
    // the card, matching where a reader's eye lands first — tried vertically
    // centering it against the (taller) content column instead, but on a
    // short card that pulled the badge down away from the title and read as
    // misaligned rather than intentional. Top alignment is correct even when
    // it leaves some space below a small badge on a tall card. ?>
    <article class="flex flex-1 gap-4 sm:gap-5 items-start p-4 sm:p-6 lg:p-8">
        <?php if ($date_badge['day'] !== '') : ?>
            <time
                datetime="<?= esc_attr($date_badge['iso']); ?>"
                class="own-agenda-date-badge shrink-0 flex flex-col items-center justify-center gap-0.5 rounded-xl min-w-16 h-16 sm:min-w-18 sm:h-18 px-2 text-center">
                <span class="own-agenda-date-badge__month text-[0.65rem] sm:text-xs font-semibold uppercase tracking-wide leading-none">
                    <?= esc_html($date_badge['month']); ?>
                </span>
                <span class="own-agenda-date-badge__day <?= $date_badge['range'] ? 'text-base sm:text-lg' : 'text-xl sm:text-2xl'; ?> font-bold leading-none">
                    <?= esc_html($date_badge['day']); ?>
                </span>
                <?php if ($date_badge['year'] !== '') : ?>
                    <span class="own-agenda-date-badge__year text-[0.65rem] sm:text-xs font-medium opacity-70 leading-none">
                        <?= esc_html($date_badge['year']); ?>
                    </span>
                <?php endif; ?>
            </time>
        <?php endif; ?>

        <div class="flex flex-1 min-w-0 flex-col">
            <div>
                <h2 class="text-lg lg:text-xl font-semibold text-(--own-agenda-card-title-color) m-0! own-agenda-card__title">
                    <?= esc_html(get_the_title()); ?>
                </h2>
                <?php if ($date_caption !== '') : ?>
                    <p class="mt-1 text-sm text-(--own-agenda-card-text-color) m-0!">
                        <?= esc_html($date_caption); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (has_excerpt()) : ?>
                <div class="mt-3 prose prose-sm text-(--own-agenda-card-text-color) line-clamp-2">
                    <?= wp_kses_post(get_the_excerpt()); ?>
                </div>
            <?php endif; ?>

            <div class="mt-auto">
                <?php if (!empty($badges) || ownframework_agenda_is_multi_day($post_id) || $signup['enabled']) : ?>
                    <div class="mt-4 pt-4 border-t border-gray-200 flex flex-wrap gap-2">
                        <?php if (ownframework_agenda_is_multi_day($post_id)) : ?>
                            <span class="own-agenda-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                                <?php esc_html_e('Meerdaags', 'own-framework'); ?>
                            </span>
                        <?php endif; ?>
                        <?php foreach ($badges as $badge) : ?>
                            <span class="own-agenda-badge inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                                <?= esc_html($badge); ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if ($signup['enabled']) : ?>
                            <span class="own-agenda-badge own-agenda-badge--signup inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium">
                                <?php esc_html_e('Aanmelden mogelijk', 'own-framework'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between pt-5">
                    <span class="own-agenda-card__link inline-flex items-center gap-1 text-sm font-medium group-hover:underline!">
                        <?php esc_html_e('Moment bekijken', 'own-framework'); ?>
                        <?= ownframework_get_icon('arrow-right', 'w-4 h-4'); ?>
                    </span>
                </div>
            </div>
        </div>
    </article>
</a>
