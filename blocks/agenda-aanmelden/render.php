<?php
defined('ABSPATH') || exit();

/**
 * Aanmeldkaart voor de agenda-detailpagina — het rechter (1/3) blok naast de
 * inhoud, direct in templates/single-agenda.html geplaatst (niet in het
 * moment's eigen post-content), zodat de 2/3+1/3 kolommenlayout voor elk
 * agenda-moment geldt ongeacht hoe het is aangemaakt — zelfde reden als
 * blocks/vacature-apply/render.php.
 *
 * In tegenstelling tot vacature-apply (waar solliciteren altijd mogelijk is,
 * een SITE-BREDE instelling) is "aanmelden mogelijk" hier per moment aan/uit
 * (inc/functions/agenda-helpers.php's ownframework_agenda_get_signup()) —
 * niet elk agenda-item heeft een aanmelding nodig (een schoolvakantie bv.
 * niet, een ouderavond wel). Is aanmelden voor dit moment uit, dan rendert
 * dit block bewust NIETS: geen lege kaart in de kolom. In de Site Editor zelf
 * (de TEMPLATE bewerken, geen echt moment als context) toont dit block dus
 * ook leeg — verwacht, bekijk een echt agenda-moment met aanmelden aan om
 * het te zien.
 *
 * "Formulier"-methode: Forminator Pro (shortcode), zelfde patroon/reden als
 * blocks/vacature-apply/render.php's eigen "Formulier"-tak.
 */

$post_id = get_the_ID();
$signup = ownframework_agenda_get_signup($post_id);

if (!$signup['enabled']) {
    return;
}

$has_forminator_form = $signup['form_id'] !== '' && shortcode_exists('forminator_form');

$card_bg_color = $block['cardBackgroundColor'] ?? '';

$style_parts = [];
if ($card_bg_color !== '') {
    $style_parts[] = '--own-agenda-aanmelden-card-bg-color:' . esc_attr($card_bg_color);
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'own-agenda-aanmelden',
    'style' => implode(';', $style_parts),
]);

$inner_blocks = '<InnerBlocks '
    . 'template=\'[["core/heading",{"level":3,"placeholder":"Aanmelden","className":"text-lg font-semibold m-0"}],["core/paragraph",{"placeholder":"Meld je aan voor dit moment.","className":"mt-2 text-sm opacity-80"}]]\' '
    . 'allowedBlocks=\'["core/heading","core/paragraph","core/buttons"]\' '
    . 'templateLock=\'false\' '
    . 'className=\'space-y-2\' />';
?>
<div <?= $wrapper_attributes; ?> x-data="{ signupModalOpen: false }" @keydown.escape.window="signupModalOpen = false">
    <div class="own-agenda-aanmelden__card own-agenda-surface bg-(--own-agenda-aanmelden-card-bg-color) rounded-2xl p-6 sticky top-24 [&_:is(h1,h2,h3,h4,h5,h6)]:mt-0!">
        <?= $inner_blocks; ?>

        <?php if ($signup['method'] === 'url' && $signup['url'] !== '') : ?>
            <div class="wp-block-button">
                <a
                    href="<?= esc_url($signup['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="wp-block-button__link wp-element-button mt-5 w-full">
                    <?php esc_html_e('Meld je aan', 'own-framework'); ?>
                </a>
            </div>
        <?php elseif ($signup['method'] === 'form') : ?>
            <div class="wp-block-button">
                <button
                    type="button"
                    @click="signupModalOpen = true"
                    class="wp-block-button__link wp-element-button mt-5 w-full">
                    <?php esc_html_e('Meld je aan', 'own-framework'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($signup['method'] === 'form') : ?>
        <div x-show="signupModalOpen" x-cloak class="own-agenda-aanmelden-modal fixed inset-0 z-50 flex items-center justify-center p-4">
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
                class="own-agenda-aanmelden-modal__panel relative w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-xl bg-white shadow-xl"
                role="dialog"
                aria-modal="true"
                aria-labelledby="own-agenda-aanmelden-modal-title">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 id="own-agenda-aanmelden-modal-title" class="text-lg font-semibold text-gray-900 m-0!">
                        <?php
                        printf(
                            /* translators: %s: agenda item title */
                            esc_html__('Aanmelden voor "%s"', 'own-framework'),
                            esc_html(get_the_title($post_id))
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
