<?php
defined('ABSPATH') || exit();

/**
 * Deelbalk onder de inhoud van een nieuwsbericht — structureel gebaseerd op
 * blocks/agenda-share/render.php, hier zonder de "Aanmelden mogelijk"-kaart:
 * niets bij een nieuwsbericht is analoog aan agenda's per-moment
 * aanmeldflow, dus dit block is de deelrij, plus (indien aanwezig) de tags
 * van dit bericht erboven.
 *
 * Staat als ÉÉN afgebakende kaart (".own-news-surface", rounded-2xl, eigen
 * padding) i.p.v. de eerdere kale "border-t"-scheidingslijn — zelfde
 * kaart-recept als agenda-share's eigen "Aanmelden mogelijk"-blok
 * (own-agenda-surface), hier toegepast op de HELE inhoud van dit block (tags
 * + deelrij samen) zodat het als één samenhangend geheel oogt i.p.v. losse
 * regels onder de content. templates/single.html geeft dit block zijn eigen
 * marge boven (spacing|m) én onder (spacing|xl), i.p.v. te leunen op de
 * kaart's eigen padding voor afstand tot de content erboven. Padding
 * "p-4 sm:p-6": zelfde
 * mobiel/desktop-stappen als blocks/news/parts/card.php's eigen kaart, voor
 * een site-breed consistente schaal.
 *
 * Tags (get_the_tags(), native) stonden eerst in blocks/news-single-hero/
 * render.php's eigen hero, maar zijn hierheen verplaatst: minder belangrijk
 * dan de categorie-badges (geen filterbare facet, puur losse context), dus
 * hoort niet zo prominent bovenaan als in de hero zelf — wel nog altijd
 * zichtbaar, alleen lager in de hiërarchie. Zelfde ".own-news-tag"-stijl als
 * blocks/news/parts/card.php's eigen tags. Een dunne "border-b" ONDER de
 * tags-rij (alleen als er tags zijn) scheidt 'm visueel van de deelrij
 * eronder, zonder de kaart zelf in twee losse vlakken te knippen.
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
 * Alleen insertable op een echte `post` of binnen templates/single.html —
 * hooks/restrict-to-single-post.php.
 */

$post_id = get_the_ID();
$tags = get_the_tags($post_id);

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
    'class' => 'own-news-share',
]);
?>
<div <?= $wrapper_attributes; ?> x-data="<?= esc_attr('{ copied: false }'); ?>">
    <div class="own-news-surface rounded-2xl p-4 sm:p-6 flex flex-col gap-4">
        <?php if (!empty($tags)) : ?>
            <div class="flex flex-wrap items-center gap-2 pb-4 border-b border-current/10">
                <?php foreach ($tags as $tag) : ?>
                    <span class="own-news-tag inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium">
                        #<?= esc_html($tag->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <span class="text-sm font-medium opacity-70"><?php esc_html_e('Deel dit bericht', 'own-framework'); ?></span>
            <div class="flex items-center gap-2">
                <?php foreach ($share_links as $share) : ?>
                    <a
                        href="<?= esc_url($share['url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="<?= esc_attr($share['label']); ?>"
                        class="own-news-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0"><?= ownframework_get_icon($share['icon'], 'w-4 h-4'); ?></a>
                <?php endforeach; ?>
                <button
                    type="button"
                    @click="<?= esc_attr($copy_click); ?>"
                    aria-label="<?php esc_attr_e('Link kopiëren', 'own-framework'); ?>"
                    class="own-news-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0">
                    <span x-show="!copied" class="inline-flex"><?= ownframework_get_icon('link', 'w-4 h-4'); ?></span>
                    <span x-show="copied" x-cloak class="inline-flex"><?= ownframework_get_icon('check', 'w-4 h-4'); ?></span>
                </button>
            </div>
        </div>
    </div>
</div>
