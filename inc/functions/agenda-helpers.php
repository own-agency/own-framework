<?php
defined('ABSPATH') || exit();

/**
 * Shared helpers for the `agenda` CPT (acf-json/group_own9c3d5f1a0000.json),
 * used by blocks/agenda/parts/card.php, blocks/agenda-single-hero/render.php
 * and blocks/agenda-share/render.php — same role for this CPT as inc/
 * functions/vacature-taxonomy-settings.php has for `vacancy`, kept here (not
 * in one block's own folder) because more than one block folder needs it.
 *
 * A single moment can be "eendaags" (event_date, optionally with start/end
 * time unless "all_day") or "meerdaags" (start_date/end_date, e.g. a
 * vacation) — see the field group's own conditional logic for which fields
 * apply to which "event_type".
 */

const OWNFRAMEWORK_AGENDA_POST_TYPE = 'agenda';

/**
 * Denormalized "sort_start"/"sort_end" postmeta (plain Ymd strings, same
 * shape ACF's own date_picker already stores) so blocks/agenda/hooks/
 * query.php can orderby/filter a SINGLE meta key regardless of whether the
 * post is "eendaags" (event_date) or "meerdaags" (start_date/end_date) —
 * WP_Query has no way to orderby "whichever of two meta keys applies per
 * row", so this recomputes both into one shared pair of keys whenever the
 * post is saved via the block editor. Ymd strings sort/compare correctly as
 * plain strings (fixed width, zero-padded), no DATE-type meta_query casting
 * needed.
 */
add_action('acf/save_post', 'ownframework_agenda_sync_sort_meta', 20);

function ownframework_agenda_sync_sort_meta($post_id): void
{
    // acf/save_post fires for every "post" ACF saves, not just real posts —
    // an options page (this theme has several under Thema opties) passes
    // the literal string 'options', a user profile 'user_5', a term
    // 'term_12', etc. Only a real `agenda` post ever has anything for this
    // function to sync, and only a real post has an integer ID at all.
    if (!is_int($post_id) || get_post_type($post_id) !== OWNFRAMEWORK_AGENDA_POST_TYPE) {
        return;
    }

    $event_type = get_field('event_type', $post_id) ?: 'single';

    if ($event_type === 'multi') {
        $start = get_field('start_date', $post_id) ?: '';
        $end = get_field('end_date', $post_id) ?: $start;
    } else {
        $start = get_field('event_date', $post_id) ?: '';
        $end = $start;
    }

    update_post_meta($post_id, 'agenda_sort_start', $start);
    update_post_meta($post_id, 'agenda_sort_end', $end);
}

/**
 * One human-readable Dutch date (and, for a same-day moment with a set
 * time, time) string covering all three shapes a moment can take: a plain
 * day, a day with a start/end time, or a multi-day range. date_i18n() (not
 * date()) so month names follow WordPress' own Dutch locale rather than
 * being hardcoded here.
 */
function ownframework_agenda_format_date(int $post_id): string
{
    $event_type = get_field('event_type', $post_id) ?: 'single';

    if ($event_type === 'multi') {
        $start = get_field('start_date', $post_id);
        $end = get_field('end_date', $post_id) ?: $start;

        if (!$start) {
            return '';
        }

        $start_ts = strtotime($start);
        $end_ts = strtotime((string) $end);

        if ($start === $end) {
            return date_i18n('j F Y', $start_ts);
        }

        $same_year = date_i18n('Y', $start_ts) === date_i18n('Y', $end_ts);
        $same_month = $same_year && date_i18n('n', $start_ts) === date_i18n('n', $end_ts);

        if ($same_month) {
            return date_i18n('j', $start_ts) . ' - ' . date_i18n('j F Y', $end_ts);
        }
        if ($same_year) {
            return date_i18n('j F', $start_ts) . ' - ' . date_i18n('j F Y', $end_ts);
        }

        return date_i18n('j F Y', $start_ts) . ' - ' . date_i18n('j F Y', $end_ts);
    }

    $date = get_field('event_date', $post_id);
    if (!$date) {
        return '';
    }

    $formatted = date_i18n('j F Y', strtotime($date));

    if ((bool) get_field('all_day', $post_id)) {
        return $formatted;
    }

    $start_time = get_field('start_time', $post_id);
    $end_time = get_field('end_time', $post_id);

    if ($start_time && $end_time) {
        return sprintf(
            /* translators: 1: date, 2: start time, 3: end time */
            __('%1$s, %2$s - %3$s uur', 'own-framework'),
            $formatted,
            $start_time,
            $end_time
        );
    }
    if ($start_time) {
        return sprintf(
            /* translators: 1: date, 2: start time */
            __('%1$s, %2$s uur', 'own-framework'),
            $formatted,
            $start_time
        );
    }

    return $formatted;
}

/**
 * The compact "18 / aug"-style visual date badge used on blocks/agenda/
 * parts/card.php's own results-grid cards — separate from
 * ownframework_agenda_format_date() above (which stays the full, precise
 * text used for the single-page hero): the CARD's badge is a quick visual
 * scan aid, this CPT's most important piece of information ("wanneer is
 * dit?"), so it needs to read instantly rather than as a sentence.
 * date_i18n('j'/'M', …), not date(), so the month abbreviation follows
 * WordPress' own Dutch locale rather than a hardcoded list.
 *
 * A "meerdaags" moment that stays within one month renders as a range in the
 * SAME slot ("18–22"), just at a smaller font size (`range` => true tells
 * the caller to size down, since 5 characters doesn't fit the single-day
 * layout's type scale) — one crossing a month/year boundary can't
 * meaningfully compress into one small badge, so it falls back to anchoring
 * on the START date only.
 *
 * `complete` tells the caller (ownframework_agenda_get_card_caption() below)
 * whether the badge alone already says everything there is to say: false
 * for a timed single-day moment (the badge has no room for a time) or a
 * moment crossing a month/year (the badge shows only its start date) — both
 * need a small extra caption elsewhere on the card. True for a plain all-day
 * single date or an in-month range: repeating that as a text sentence right
 * next to the badge would just be the same date twice.
 *
 * "year" is rendered as its own small line directly under the badge square
 * (parts/card.php, agenda-single-hero/render.php) — not squeezed into the
 * fixed day/month box itself, and not folded into the under-title caption
 * either (that read oddly, a bare year floating under the title with no
 * date next to it): a moment is now filterable by month ACROSS years (the
 * "Maand"-dropdown, blocks/agenda/hooks/query.php), so a card needs a year
 * somewhere to stay unambiguous, and directly under the day/month it
 * belongs to is the least ambiguous place for it.
 *
 * @return array{day: string, month: string, year: string, range: bool, complete: bool, iso: string}
 *               "iso" is the anchor date as Y-m-d, for a semantic <time datetime>.
 */
function ownframework_agenda_get_date_badge(int $post_id): array
{
    $event_type = get_field('event_type', $post_id) ?: 'single';

    if ($event_type === 'multi') {
        $start = get_field('start_date', $post_id);
        $end = get_field('end_date', $post_id) ?: $start;

        if (!$start) {
            return ['day' => '', 'month' => '', 'year' => '', 'range' => false, 'complete' => true, 'iso' => ''];
        }

        $start_ts = strtotime($start);
        $end_ts = strtotime((string) $end);
        $iso = date_i18n('Y-m-d', $start_ts);
        $year = date_i18n('Y', $start_ts);
        $is_range = $start !== $end;
        $same_month = $is_range && date_i18n('n-Y', $start_ts) === date_i18n('n-Y', $end_ts);

        if ($same_month) {
            return [
                'day' => date_i18n('j', $start_ts) . '–' . date_i18n('j', $end_ts),
                'month' => date_i18n('M', $start_ts),
                'year' => $year,
                'range' => true,
                'complete' => true,
                'iso' => $iso,
            ];
        }

        return [
            'day' => date_i18n('j', $start_ts),
            'month' => date_i18n('M', $start_ts),
            'year' => $year,
            'range' => false,
            // A range crossing months/years can't be read off the badge alone
            // (it only shows the START day) — an in-month range above already
            // can, and a same-day "multi" entry is really just a single date.
            'complete' => !$is_range,
            'iso' => $iso,
        ];
    }

    $date = get_field('event_date', $post_id);
    if (!$date) {
        return ['day' => '', 'month' => '', 'year' => '', 'range' => false, 'complete' => true, 'iso' => ''];
    }

    $ts = strtotime($date);

    return [
        'day' => date_i18n('j', $ts),
        'month' => date_i18n('M', $ts),
        'year' => date_i18n('Y', $ts),
        'range' => false,
        // A timed moment needs the time spelled out somewhere — the badge has
        // no room for it, only the day/month.
        'complete' => (bool) get_field('all_day', $post_id),
        'iso' => date_i18n('Y-m-d', $ts),
    ];
}

/**
 * The small text caption shown under the title on blocks/agenda/parts/
 * card.php's cards, ALONGSIDE the date badge above — deliberately NOT
 * ownframework_agenda_format_date()'s full sentence, which would repeat the
 * same date the badge already shows right next to it (the year is its own
 * separate line directly under the badge square instead, see that
 * function's own docblock — not here, that read oddly floating alone under
 * the title). Returns '' whenever the badge is already "complete" (nothing
 * to add); otherwise returns only the piece of information the badge itself
 * couldn't fit:
 * - a timed single-day moment → just the time ("14:00 - 15:30 uur"), not
 *   the date again;
 * - a moment crossing a month/year → the full range (reusing
 *   ownframework_agenda_format_date(), since at that point the badge only
 *   anchors on the start day and the end date is genuinely new information).
 *
 * Takes the already-computed $date_badge rather than recomputing it, since
 * every caller already has one from rendering the badge itself.
 *
 * @param array{day: string, month: string, year: string, range: bool, complete: bool, iso: string} $date_badge
 */
function ownframework_agenda_get_card_caption(int $post_id, array $date_badge): string
{
    if ($date_badge['complete']) {
        return '';
    }

    $event_type = get_field('event_type', $post_id) ?: 'single';

    if ($event_type === 'multi') {
        return ownframework_agenda_format_date($post_id);
    }

    $start_time = get_field('start_time', $post_id);
    $end_time = get_field('end_time', $post_id);

    if ($start_time && $end_time) {
        return sprintf(
            /* translators: 1: start time, 2: end time */
            __('%1$s - %2$s uur', 'own-framework'),
            $start_time,
            $end_time
        );
    }
    if ($start_time) {
        return sprintf(
            /* translators: %s: start time */
            __('%s uur', 'own-framework'),
            $start_time
        );
    }

    return '';
}

/**
 * True for a "meerdaags" moment whose start and end date genuinely differ —
 * used by parts/card.php to decide whether to show a "meerdaags" badge.
 */
function ownframework_agenda_is_multi_day(int $post_id): bool
{
    if ((get_field('event_type', $post_id) ?: 'single') !== 'multi') {
        return false;
    }

    $start = get_field('start_date', $post_id);
    $end = get_field('end_date', $post_id) ?: $start;

    return $start && $end && $start !== $end;
}

/**
 * Normalizes the four signup-related fields (signup_enabled/signup_method/
 * signup_url/signup_form_id) into one shape, so blocks/agenda/parts/card.php
 * (the "Aanmelden mogelijk" badge) and blocks/agenda-single-hero/render.php
 * + blocks/agenda-share/render.php (the actual "Meld je aan" buttons/modal)
 * don't each re-read/re-branch on the raw fields.
 *
 * @return array{enabled: bool, method: string, url: string, form_id: string}
 */
function ownframework_agenda_get_signup(int $post_id): array
{
    $enabled = (bool) get_field('signup_enabled', $post_id);
    $method = get_field('signup_method', $post_id) ?: 'url';

    return [
        'enabled' => $enabled,
        'method' => $method,
        'url' => $enabled && $method === 'url' ? (string) get_field('signup_url', $post_id) : '',
        'form_id' => $enabled && $method === 'form' ? (string) get_field('signup_form_id', $post_id) : '',
    ];
}
