<?php
defined('ABSPATH') || exit();

/**
 * Deelbalk onder de inhoud van een vacature — zelfde kaart-opzet als
 * blocks/news-share/render.php en blocks/agenda-share/render.php's eigen
 * deelrij (".own-vacatures-surface", rounded-2xl, eigen padding), hier
 * zonder tags/"Aanmelden mogelijk"-kaart: vacatures hebben geen native tags
 * (Traps geen filterbare tag-taxonomie, zie blocks/vacatures/hooks/
 * query.php) en het aanmelden/solliciteren gebeurt al op ÉÉN plek —
 * acf/vacature-apply's eigen kaart in de rechterkolom — dus dit block is
 * puur de deelrij. Padding "p-4 sm:p-6": zelfde mobiel/desktop-stappen als
 * blocks/news/parts/card.php's eigen kaart, voor een site-breed consistente
 * schaal.
 *
 * De deelknoppen zijn platte, uitgaande share-intent-links (X/Facebook/
 * LinkedIn/WhatsApp) — geen JS nodig, "target=_blank" + "rel=noopener".
 * Alleen "Link kopiëren" heeft een beetje Alpine nodig (navigator.clipboard),
 * lokale x-data op dit block's eigen wrapper.
 * @click blijft bewust een gewone "function () {}", geen pijl-functie: zie
 * CLAUDE.md's wptexturize-val (Alpine-attributen gaan door wpautop()/
 * wptexturize() via de_content, dat verminkt "=>"). Zelfde blanket-gewoonte
 * als elders in dit thema: ook de x-data/@click-strings hier gaan door
 * esc_attr(), ook al kunnen ze zelf nooit een aanhalingsteken bevatten.
 *
 * Alleen insertable op een echte `vacancy` of binnen templates/
 * single-vacancy.html — hooks/restrict-to-vacancy-context.php.
 */

$post_id = get_the_ID();

$permalink = (string) get_permalink($post_id);
$title = get_the_title($post_id);

$encoded_url = rawurlencode($permalink);
$encoded_title = rawurlencode($title);

$share_links = [
    'x' => [
        'label' => __('Delen op X', 'own-framework'),
        'url' => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title,
        'icon' => 'social-x',
    ],
    'facebook' => [
        'label' => __('Delen op Facebook', 'own-framework'),
        'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
        'icon' => 'social-facebook',
    ],
    'linkedin' => [
        'label' => __('Delen op LinkedIn', 'own-framework'),
        'url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
        'icon' => 'social-linkedin',
    ],
    'whatsapp' => [
        'label' => __('Delen via WhatsApp', 'own-framework'),
        'url' => 'https://api.whatsapp.com/send?text=' . $encoded_title . '%20' . $encoded_url,
        'icon' => 'social-whatsapp',
    ],
];

// Eén regel, geen pijl-functie — zie de docblock hierboven.
$copy_click = 'navigator.clipboard.writeText(\'' . esc_js($permalink) . '\').then(function () { copied = true; setTimeout(function () { copied = false }, 2000) })';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-vacature-share',
]);
?>
<div <?= $wrapper_attributes; ?> x-data="<?= esc_attr('{ copied: false }'); ?>">
    <div class="own-vacatures-surface rounded-2xl p-4 sm:p-6 flex flex-wrap items-center justify-between gap-4">
        <span class="text-sm font-medium opacity-70"><?php esc_html_e('Deel deze vacature', 'own-framework'); ?></span>
        <div class="flex items-center gap-2">
            <?php foreach ($share_links as $share) : ?>
                <a
                    href="<?= esc_url($share['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?= esc_attr($share['label']); ?>"
                    class="own-vacature-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0"><?= ownframework_get_icon($share['icon'], 'w-4 h-4'); ?></a>
            <?php endforeach; ?>
            <button
                type="button"
                @click="<?= esc_attr($copy_click); ?>"
                aria-label="<?php esc_attr_e('Link kopiëren', 'own-framework'); ?>"
                class="own-vacature-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0">
                <span x-show="!copied" class="inline-flex"><?= ownframework_get_icon('link', 'w-4 h-4'); ?></span>
                <span x-show="copied" x-cloak class="inline-flex"><?= ownframework_get_icon('check', 'w-4 h-4'); ?></span>
            </button>
        </div>
    </div>
</div>
