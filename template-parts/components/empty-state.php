<?php
/**
 * Empty state — shown wherever a block loads posts and there are none: the
 * four archives (vacatures, agenda, news, verhalen), their four *-uitgelicht
 * blocks and faqs. Deliberately small: a short line in a light box, not a
 * hero. Styling: src/css/components/empty-state.css.
 *
 * get_template_part('template-parts/components/empty-state', null, [
 *     'title'  => 'Geen vacatures gevonden',
 *     'text'   => 'Probeer andere filters.',   // optional
 *     'action' => [                            // optional, a text link
 *         'label'  => 'Filters wissen',
 *         'alpine' => '$store.vacaturesFilters.clearTaxonomyFilters()', // @click
 *         // or 'url' => '…' for a plain link
 *     ],
 * ]);
 *
 * "not-prose" because the uitgelicht blocks can sit in post content.
 */

defined('ABSPATH') || exit();

$title = (string) ($args['title'] ?? '');
$text = (string) ($args['text'] ?? '');
$action = is_array($args['action'] ?? null) ? $args['action'] : null;
?>
<div class="own-empty-state not-prose" role="status">
    <?php if ($title !== '') : ?>
        <p class="own-empty-state__title"><?= esc_html($title); ?></p>
    <?php endif; ?>
    <?php if ($text !== '') : ?>
        <p class="own-empty-state__text"><?= esc_html($text); ?></p>
    <?php endif; ?>
    <?php if ($action && !empty($action['label'])) : ?>
        <?php if (!empty($action['alpine'])) : ?>
            <button type="button" class="own-empty-state__action" @click="<?= esc_attr($action['alpine']); ?>"><?= esc_html($action['label']); ?></button>
        <?php else : ?>
            <a class="own-empty-state__action" href="<?= esc_url($action['url'] ?? ''); ?>"><?= esc_html($action['label']); ?></a>
        <?php endif; ?>
    <?php endif; ?>
</div>
