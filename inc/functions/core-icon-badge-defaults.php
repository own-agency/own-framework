<?php
defined('ABSPATH') || exit();

/**
 * acf/voordeel's own InnerBlocks template pre-fills its core/icon with a
 * background colour, text colour, border radius, padding and width (that
 * block's own render.php docblock) — a "badge" look, editable straight away
 * because Gutenberg auto-expands any Dimensions/Border ToolsPanel item that
 * already has a stored value, regardless of the block's own
 * "__experimentalDefaultControls". A freshly inserted, stand-alone core/icon
 * has none of those values yet, so the SAME controls stay hidden behind the
 * panel's "+" menu — background/text colour are the only ones core already
 * defaults to visible ("__experimentalDefaultControls": {"background": true,
 * "text": true}), and width too, for a different reason (dimensions.width
 * has no preset scale to gate on). Padding and border radius are the two
 * that stayed opt-in. This filter flips both to visible-by-default for every
 * core/icon instance, so a plain icon block's Styles tab looks and behaves
 * exactly like editing acf/voordeel's own icon, not just visually matching
 * it after values happen to already be set.
 *
 * "core/icon" is also excluded (alongside core/button) from functions.php's
 * site-wide block_type_metadata filter, which otherwise narrows every
 * block's padding/margin support to top/bottom only — an icon's padding
 * shapes its own badge box, not spacing between blocks, so it needs all
 * four sides like core/button already gets.
 *
 * theme.json's "settings.blocks.core/icon.spacing.spacingSizes": [] (plus
 * "defaultSpacingSizes": false to also exclude WordPress' own built-in
 * preset scale) is the third piece of the same picture: with no presets to
 * choose from, Gutenberg's SpacingSizesControl falls back to a plain
 * UnitControl (number + unit) instead of the site-wide spacingSizes slider
 * every other block gets — the same kind of field core/icon's own "Breedte"
 * (dimensions.width) already uses, since width never had a preset scale to
 * begin with.
 *
 * Border radius needed a FOURTH piece, in theme.json too: WordPress' own
 * default theme.json sets "settings.border.radius" to false SITE-WIDE
 * (verified live via wp_get_global_settings(['border','radius']), no
 * per-block scoping) — a block's own "supports.__experimentalBorder.radius"
 * only declares the CAPABILITY, this "settings" flag is the separate gate
 * that actually turns the control on, same relationship as
 * "settings.spacing.padding" is to a block's own padding support. This
 * theme never touched "settings.border" at all, so that "false" default was
 * silently winning even after this file's own __experimentalDefaultControls
 * fix — WordPress core itself works around exactly this for core/button via
 * its own "settings.blocks.core/button.border.radius": true (visible in the
 * same wp_get_global_settings() dump), which is the precedent
 * "settings.blocks.core/icon.border.radius": true (theme.json) follows here.
 */
add_filter('register_block_type_args', function (array $args, string $block_type): array {
    if ($block_type !== 'core/icon') {
        return $args;
    }

    $args['supports']['spacing'] ??= [];
    $args['supports']['spacing']['__experimentalDefaultControls'] ??= [];
    $args['supports']['spacing']['__experimentalDefaultControls']['padding'] = true;

    $args['supports']['__experimentalBorder'] ??= [];
    $args['supports']['__experimentalBorder']['__experimentalDefaultControls'] ??= [];
    $args['supports']['__experimentalBorder']['__experimentalDefaultControls']['radius'] = true;

    return $args;
}, 10, 2);
