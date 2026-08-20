<?php
defined('ABSPATH') || exit();

/**
 * Deelbalk onder de inhoud van een agenda-detailpagina — staat als ÉÉN
 * afgebakende kaart (".own-agenda-surface", rounded-2xl, eigen padding),
 * zelfde recept als blocks/news-share/render.php's eigen kaart: het
 * "Aanmelden mogelijk"-gedeelte (indien actief) en de deelrij zitten samen in
 * die ene kaart, gescheiden door een dunne "border-b" i.p.v. als twee losse,
 * na elkaar gestapelde kaartjes — zo oogt het als één samenhangend blok
 * i.p.v. losse stukjes onder de content, precies zoals de blog's tags+
 * deelrij dat ook doen. templates/single-agenda.html geeft dit block dezelfde
 * bovenmarge (spacing|m) als templates/single.html's eigen acf/news-share.
 * Padding "p-4 sm:p-6", niet "p-5": zelfde mobiel/desktop-stappen als
 * blocks/news/parts/card.php's eigen kaart, voor een site-breed consistente
 * schaal i.p.v. een eigen tussenstap voor dit ene block.
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
 * Draagt sinds kort ook het "Aanmelden mogelijk"-gedeelte (voorheen het losse
 * acf/agenda-aanmelden block, en kort ook een tweede knop in acf/
 * agenda-single-hero — beide weer verwijderd), met zijn eigen korte uitleg
 * ("Meld je hier aan voor dit moment.") naast de knop, net als de deelrij
 * zijn eigen "Deel dit moment"-label heeft — twee losse, ieder voor zich
 * begrijpelijke stukjes UI binnen dezelfde kaart, i.p.v. één gedeelde rij die
 * probeert allebei te zijn. Aanmelden gebeurt nu op precies ÉÉN plek op de
 * pagina, dus de modal ("Formulier"-methode) is gewoon lokale `x-data`
 * (`signupModalOpen`) op dit block's eigen wrapper — geen gedeelde `$store`,
 * die was alleen nodig zolang er een 2de trigger-plek (de hero) bestond.
 *
 * Alleen insertable binnen templates/single-agenda.html — hooks/
 * restrict-to-single-agenda-template.php.
 */

$post_id = get_the_ID();
$signup = ownframework_agenda_get_signup($post_id);
$has_forminator_form = $signup['form_id'] !== '' && shortcode_exists('forminator_form');

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
    'class' => 'own-agenda-share',
]);
?>
<div <?= $wrapper_attributes; ?> x-data="<?= esc_attr('{ copied: false, signupModalOpen: false }'); ?>" @keydown.escape.window="signupModalOpen = false">
    <div class="own-agenda-surface rounded-2xl p-4 sm:p-6 flex flex-col gap-4">
        <?php if ($signup['enabled']) : ?>
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-current/10">
                <div class="min-w-0">
                    <p class="text-sm font-semibold m-0!"><?php esc_html_e('Aanmelden mogelijk', 'own-framework'); ?></p>
                    <p class="mt-0.5 text-sm opacity-70 m-0!"><?php esc_html_e('Meld je hier aan voor dit moment.', 'own-framework'); ?></p>
                </div>
                <?php if ($signup['method'] === 'url' && $signup['url'] !== '') : ?>
                    <div class="wp-block-button shrink-0">
                        <a
                            href="<?= esc_url($signup['url']); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="wp-block-button__link wp-element-button inline-flex! items-center gap-2">
                            <?php esc_html_e('Meld je aan', 'own-framework'); ?>
                            <?= ownframework_get_icon('external-link', 'w-4 h-4'); ?>
                        </a>
                    </div>
                <?php elseif ($signup['method'] === 'form') : ?>
                    <div class="wp-block-button shrink-0">
                        <button
                            type="button"
                            @click="signupModalOpen = true"
                            class="wp-block-button__link wp-element-button inline-flex! items-center gap-2">
                            <?php esc_html_e('Meld je aan', 'own-framework'); ?>
                            <?= ownframework_get_icon('form', 'w-4 h-4'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <span class="text-sm font-medium opacity-70"><?php esc_html_e('Deel dit moment', 'own-framework'); ?></span>
            <div class="flex items-center gap-2">
                <?php foreach ($share_links as $share) : ?>
                    <a
                        href="<?= esc_url($share['url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="<?= esc_attr($share['label']); ?>"
                        class="own-agenda-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0"><?= ownframework_get_icon($share['icon'], 'w-4 h-4'); ?></a>
                <?php endforeach; ?>
                <button
                    type="button"
                    @click="<?= esc_attr($copy_click); ?>"
                    aria-label="<?php esc_attr_e('Link kopiëren', 'own-framework'); ?>"
                    class="own-agenda-share-btn inline-flex items-center justify-center rounded-full w-9 h-9 shrink-0">
                    <span x-show="!copied" class="inline-flex"><?= ownframework_get_icon('link', 'w-4 h-4'); ?></span>
                    <span x-show="copied" x-cloak class="inline-flex"><?= ownframework_get_icon('check', 'w-4 h-4'); ?></span>
                </button>
            </div>
        </div>
    </div>

    <?php if ($signup['enabled'] && $signup['method'] === 'form') : ?>
        <div x-show="signupModalOpen" x-cloak class="own-agenda-signup-modal fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                x-show="signupModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="signupModalOpen = false"
                class="absolute inset-0 bg-black/50"
                aria-hidden="true"></div>

            <div
                x-show="signupModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-4"
                @click.outside="signupModalOpen = false"
                class="own-agenda-signup-modal__panel relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="own-agenda-signup-modal-title">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 id="own-agenda-signup-modal-title" class="text-lg font-semibold text-gray-900 m-0!">
                        <?php
                        printf(
                            /* translators: %s: agenda item title */
                            esc_html__('Aanmelden voor "%s"', 'own-framework'),
                            esc_html($title)
                        );
                        ?>
                    </h3>
                    <button type="button" @click="signupModalOpen = false" class="own-agenda-icon-btn rounded-lg p-2 text-gray-500" aria-label="<?php esc_attr_e('Sluiten', 'own-framework'); ?>">
                        <?= ownframework_get_icon('close', 'w-4 h-4'); ?>
                    </button>
                </div>
                <div class="p-5">
                    <?php if ($has_forminator_form) : ?>
                        <?= do_shortcode('[forminator_form id="' . esc_attr($signup['form_id']) . '"]'); ?>
                    <?php else : ?>
                        <p class="text-gray-600 text-center"><?php esc_html_e('Er is momenteel geen aanmeldformulier gekoppeld.', 'own-framework'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>