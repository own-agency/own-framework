<?php
defined('ABSPATH') || exit();

/**
 * Instagram-feed: de nieuwste posts van het account dat op de
 * ontwikkelaarspagina aan DEZE site is gekoppeld. Doet zelf nooit een
 * API-call; leest alleen wat de uurlijkse sync heeft opgeslagen
 * (inc/functions/instagram-feed.php), met lokale kopieën van de beelden.
 *
 * Front-end zonder koppeling of zonder posts: niets, liever geen blok dan
 * een leeg vlak. De editor zegt in die gevallen wat er ontbreekt. Moet het
 * account opnieuw gekoppeld worden, dan blijven de laatst opgehaalde posts
 * staan; alleen de editor meldt het.
 *
 * Twee weergaven: "cards" (standaard) toont elke post als kaart met
 * gebruikersnaam, type-icoon, staande foto (4:5, het formaat van Instagram's
 * feed) en twee regels bijschrift; "grid" alleen vierkante foto's.
 *
 * Class-lijsten eindigen nooit op een cijfer: wptexturize() maakt van een
 * cijfer gevolgd door een aanhalingsteken een prime-entity.
 */

$count = (int) ($block['count'] ?? 4);
if (!in_array($count, [3, 4, 6, 8], true)) {
    $count = 4;
}
$rounded = ($block['rounded'] ?? true) !== false;

$connected = ownframework_instagram_is_connected();
$feed = ownframework_instagram_feed();
$posts = array_slice($feed['posts'], 0, $count);

$missing = '';
if (!$connected) {
    $missing = __('Instagram is nog niet gekoppeld voor deze site. Het ontwikkelteam koppelt het account via de ontwikkelaarspagina.', 'own-framework');
} elseif (!$posts) {
    $missing = __('Er zijn nog geen Instagram-posts opgehaald. Ze verschijnen hier na de eerstvolgende synchronisatie.', 'own-framework');
}

if ($missing !== '' && empty($is_preview)) {
    return;
}

$align = $block['align'] ?? '';
$align_class = $align !== '' ? 'align' . $align : '';

if ($missing !== '') : ?>
    <div <?= get_block_wrapper_attributes(['class' => trim('own-instagram-feed not-prose rounded-2xl bg-gray-100 text-gray-500 p-6 text-sm text-center ' . $align_class)]); ?>>
        <?= esc_html($missing); ?>
    </div>
    <?php
    return;
endif;

$layout = ($block['layout'] ?? 'cards') === 'grid' ? 'grid' : 'cards';

// Kolommen op de breedte van het block zelf (@container op de wrapper), niet
// op het scherm: het block kan in een smalle kolom staan (CLAUDE.md, Traps #9).
// Kaarten hebben ruimte nodig voor gebruikersnaam en bijschrift, dus nooit
// meer dan vier naast elkaar; 6 wordt twee rijen van drie.
$grid_class = $layout === 'cards'
    ? match ($count) {
        3 => 'grid-cols-1 @xl:grid-cols-3',
        6 => 'grid-cols-2 @3xl:grid-cols-3',
        default => 'grid-cols-2 @3xl:grid-cols-4',
    }
    : match ($count) {
        3 => 'grid-cols-3',
        6 => 'grid-cols-2 @lg:grid-cols-3 @4xl:grid-cols-6',
        default => 'grid-cols-2 @2xl:grid-cols-4',
    };

$type_icons = [
    'VIDEO' => ['play', __('Video', 'own-framework')],
    'CAROUSEL_ALBUM' => ['carousel', __('Album', 'own-framework')],
    'IMAGE' => ['camera', __('Foto', 'own-framework')],
];

$uploads = ownframework_instagram_upload_dir();
$username = $feed['username'];
?>
<div <?= get_block_wrapper_attributes(['class' => trim('own-instagram-feed not-prose @container ' . $align_class)]); ?>>
    <?php if (!empty($is_preview) && ownframework_instagram_needs_reconnect()) : ?>
        <p class="mb-3 rounded-lg bg-amber-50 px-4 py-2 text-sm text-amber-800">
            <?php esc_html_e('De Instagram-koppeling moet opnieuw worden gemaakt. Dit zijn de laatst opgehaalde posts.', 'own-framework'); ?>
        </p>
    <?php endif; ?>

    <ul class="<?= esc_attr('grid list-none p-0 m-0 ' . ($layout === 'cards' ? 'gap-3 @xl:gap-5 ' : 'gap-2 @xl:gap-4 ') . $grid_class . ' own-instagram-feed__grid'); ?>">
        <?php foreach ($posts as $ig_post) :
            $caption = trim(preg_replace('/\s+/', ' ', $ig_post['caption']));
            $type = $type_icons[$ig_post['type']] ?? $type_icons['IMAGE'];
            $image_src = $uploads['url'] . '/' . $ig_post['file'];
            ?>
            <?php if ($layout === 'cards') : ?>
                <li class="m-0 p-0 flex">
                    <a
                        href="<?= esc_url($ig_post['permalink']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="<?= esc_attr('own-instagram-card group flex w-full flex-col overflow-hidden no-underline ' . ($rounded ? 'rounded-2xl' : 'rounded-none')); ?>">
                        <span class="flex items-center justify-between gap-2 px-3 py-2.5 text-sm font-medium">
                            <span class="truncate">@<?= esc_html($username); ?></span>
                            <span class="flex shrink-0 opacity-50" aria-hidden="true"><?= ownframework_get_icon($type[0], 'h-4 w-4'); ?></span>
                            <span class="sr-only"><?= esc_html($type[1]); ?></span>
                        </span>
                        <span class="relative block aspect-[4/5] overflow-hidden bg-gray-100">
                            <?php // Lege alt: het bijschrift onder de foto is al de tekst van de link. ?>
                            <img
                                src="<?= esc_url($image_src); ?>"
                                alt="<?= esc_attr($caption !== '' ? '' : sprintf(__('Instagram-post van @%s', 'own-framework'), $username)); ?>"
                                width="<?= (int) $ig_post['width']; ?>"
                                height="<?= (int) $ig_post['height']; ?>"
                                loading="lazy"
                                decoding="async"
                                class="m-0! h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                            <?php if ($ig_post['type'] === 'VIDEO') : ?>
                                <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/45 text-white">
                                        <?= ownframework_get_icon('play', 'h-5 w-5 translate-x-px'); ?>
                                    </span>
                                </span>
                            <?php endif; ?>
                        </span>
                        <?php if ($caption !== '') : ?>
                            <?php // Padding op een eigen wrapper: line-clamp knipt af op de padding-box, dus padding op dezelfde span liet een derde regel doorschemeren. ?>
                            <span class="block px-3 pt-3 pb-3.5">
                                <span class="line-clamp-2 text-sm leading-snug"><?= esc_html($caption); ?></span>
                            </span>
                        <?php endif; ?>
                        <span class="sr-only"><?php esc_html_e('(opent in een nieuw venster)', 'own-framework'); ?></span>
                    </a>
                </li>
            <?php else : ?>
                <li class="m-0 p-0">
                    <a
                        href="<?= esc_url($ig_post['permalink']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="<?= esc_attr('group relative block aspect-square overflow-hidden bg-gray-100 ' . ($rounded ? 'rounded-xl ' : '') . 'own-instagram-feed__item'); ?>">
                        <img
                            src="<?= esc_url($image_src); ?>"
                            alt="<?= esc_attr($caption !== '' ? mb_strimwidth($caption, 0, 125, '…') : sprintf(__('Instagram-post van @%s', 'own-framework'), $username)); ?>"
                            width="<?= (int) $ig_post['width']; ?>"
                            height="<?= (int) $ig_post['height']; ?>"
                            loading="lazy"
                            decoding="async"
                            class="m-0! h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <span class="absolute inset-0 flex items-center justify-center bg-black/0 text-white opacity-0 transition group-hover:bg-black/30 group-hover:opacity-100 group-focus-visible:bg-black/30 group-focus-visible:opacity-100" aria-hidden="true">
                            <?= ownframework_get_icon('social-instagram', 'h-8 w-8'); ?>
                        </span>
                        <span class="sr-only"><?php esc_html_e('(opent in een nieuw venster)', 'own-framework'); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
