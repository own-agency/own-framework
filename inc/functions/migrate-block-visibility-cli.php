<?php
defined('ABSPATH') || exit();

/**
 * One-off migration for WordPress 7.0's native block visibility feature
 * (metadata.blockVisibility) replacing this theme's own former
 * "ownHideOnMobile"/"ownHideOnDesktop" attributes (removed together with
 * inc/functions/block-visibility.php — see that commit).
 *
 * The old system was a binary mobile/desktop split at 1024px (Tailwind's
 * "lg"). Native block visibility is a three-tier mobile/tablet/desktop
 * split, defaulting to 480px/782px (this theme doesn't override
 * settings.viewport in theme.json), so the mapping below is a best-effort
 * approximation, not an exact equivalent — a section previously hidden
 * "on mobile" (<1024px) now only stays hidden up to 782px; a section
 * previously hidden "on desktop" (>=1024px) now only hides above 782px.
 * Content in the 782-1024px range can end up on the wrong side of the old
 * intent. Review the dry-run output against the actual pages before
 * running live, rather than assuming the mapping is lossless.
 *
 * Registered only under WP-CLI — this file is autoloaded on every request
 * (inc/functions/*.php glob in functions.php), so the class/command
 * definition itself must never run outside a CLI context.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

class OwnFramework_Migrate_Block_Visibility_Command
{
    /**
     * Converts every block's ownHideOnMobile/ownHideOnDesktop attribute to
     * native metadata.blockVisibility.
     *
     * ## OPTIONS
     *
     * [--live]
     * : Actually write the changes. Without this flag the command only
     * reports what it would change.
     *
     * ## EXAMPLES
     *
     *     wp own-framework migrate-block-visibility
     *     wp own-framework migrate-block-visibility --live
     */
    public function __invoke(array $args, array $assoc_args): void
    {
        global $wpdb;

        $live = !empty($assoc_args['live']);

        $post_types = get_post_types([], 'names');
        $posts = get_posts([
            'post_type' => $post_types,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'suppress_filters' => true,
        ]);

        $touched = 0;

        foreach ($posts as $post_id) {
            $post = get_post($post_id);

            if (!$post || !has_blocks($post->post_content)) {
                continue;
            }

            $changed = false;
            $blocks = self::migrate_blocks(parse_blocks($post->post_content), $changed);

            if (!$changed) {
                continue;
            }

            $touched++;
            $new_content = serialize_blocks($blocks);

            WP_CLI::log(sprintf(
                '%s #%d (%s): %s',
                $post->post_type,
                $post->ID,
                $post->post_title !== '' ? $post->post_title : '(zonder titel)',
                $live ? 'bijgewerkt' : 'zou bijgewerkt worden',
            ));

            if ($live) {
                // Direct $wpdb write, not wp_update_post(): WP-CLI has no
                // real user context by default, so wp_update_post() would
                // run post_content through wp_filter_post_kses() and strip
                // every <!-- wp:... --> block comment outright.
                $wpdb->update($wpdb->posts, ['post_content' => $new_content], ['ID' => $post->ID]);
                clean_post_cache($post->ID);
            }
        }

        WP_CLI::success(sprintf(
            '%d post(s) %s.',
            $touched,
            $live ? 'bijgewerkt' : 'zouden bijgewerkt worden (voeg --live toe om het echt te doen)',
        ));
    }

    private static function migrate_blocks(array $blocks, bool &$changed): array
    {
        foreach ($blocks as &$block) {
            if (!empty($block['blockName'])) {
                $hide_mobile = !empty($block['attrs']['ownHideOnMobile']);
                $hide_desktop = !empty($block['attrs']['ownHideOnDesktop']);

                if ($hide_mobile || $hide_desktop) {
                    unset($block['attrs']['ownHideOnMobile'], $block['attrs']['ownHideOnDesktop']);

                    $block['attrs']['metadata']['blockVisibility'] = $hide_mobile && $hide_desktop
                        ? false
                        : [
                            'viewport' => $hide_mobile
                                ? ['mobile' => false, 'tablet' => false, 'desktop' => true]
                                : ['mobile' => true, 'tablet' => true, 'desktop' => false],
                        ];

                    $changed = true;
                }
            }

            if (!empty($block['innerBlocks'])) {
                $block['innerBlocks'] = self::migrate_blocks($block['innerBlocks'], $changed);
            }
        }

        return $blocks;
    }
}

WP_CLI::add_command('own-framework migrate-block-visibility', 'OwnFramework_Migrate_Block_Visibility_Command');
