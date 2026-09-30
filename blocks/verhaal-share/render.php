<?php
defined('ABSPATH') || exit();

/**
 * Deelbalk naast de inhoud van een verhaal — structureel een kopie van
 * blocks/news-share/render.php (X/Facebook/LinkedIn/WhatsApp + link
 * kopiëren, zelfde ".own-verhalen-surface"-kaart i.p.v. een kale
 * "border-t"-scheidingslijn). Geen tags-rij zoals de nieuws-versie: dit CPT
 * heeft geen eigen tags-achtig concept, alleen de categorie-taxonomie (al
 * getoond in blocks/verhaal-single-hero's eigen hero).
 *
 * Staat, anders dan acf/news-share/acf/vacature-share/acf/agenda-share (elk
 * ONDER de inhoud, in dezelfde brede kolom), hier in de sticky 1/3-zijkolom
 * NAAST de inhoud (templates/single-verhaal.html's eigen "core/columns"-
 * splitsing, 66.66%/33.33% — zelfde verhouding als single-vacancy.html/
 * single-agenda.html) — dit CPT heeft geen per-post CTA-blok zoals acf/
 * vacature-apply/acf/agenda-aanmelden om die zijkolom te vullen, dus vult
 * de deelbalk 'm in plaats daarvan. De eigen "flex-wrap" zorgt dat het label
 * en de knoppenrij netjes onder elkaar vallen zodra de smallere kolom
 * onvoldoende breedte heeft voor beide naast elkaar.
 *
 * @click blijft een gewone "function () {}", geen pijl-functie: zie
 * CLAUDE.md's wptexturize-val (Alpine-attributen gaan door wpautop()/
 * wptexturize() via de_content, dat verminkt "=>").
 *
 * Alleen insertable op templates/single-verhaal.html — hooks/
 * restrict-to-single-verhaal.php.
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

$copy_click = 'navigator.clipboard.writeText(\'' . esc_js($permalink) . '\').then(function () { copied = true; setTimeout(function () { copied = false }, 2000) })';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-verhalen-share',
]);
?>
<div <?= $wrapper_attributes; ?> x-data="<?= esc_attr('{ copied: false }'); ?>">
    <div class="own-verhalen-surface rounded-2xl p-4 sm:p-6 lg:pointer-fine:p-8 flex flex-wrap items-center justify-between gap-4">
        <span class="text-sm font-medium opacity-70"><?php esc_html_e('Deel dit verhaal', 'own-framework'); ?></span>
        <div class="flex items-center gap-2">
            <?php foreach ($share_links as $share) : ?>
                <a
                    href="<?= esc_url($share['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?= esc_attr($share['label']); ?>"
                    class="own-verhalen-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0"><?= ownframework_get_icon($share['icon'], 'w-4 h-4'); ?></a>
            <?php endforeach; ?>
            <button
                type="button"
                @click="<?= esc_attr($copy_click); ?>"
                aria-label="<?php esc_attr_e('Link kopiëren', 'own-framework'); ?>"
                class="own-verhalen-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0">
                <span x-show="!copied" class="inline-flex"><?= ownframework_get_icon('link', 'w-4 h-4'); ?></span>
                <span x-show="copied" x-cloak class="inline-flex"><?= ownframework_get_icon('check', 'w-4 h-4'); ?></span>
            </button>
        </div>
    </div>
</div>
