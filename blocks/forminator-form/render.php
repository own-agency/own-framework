<?php
defined('ABSPATH') || exit();

/**
 * Generic "Forminator formulier" block — any editor can drop this directly
 * into page content to embed a Forminator Pro form, same do_shortcode()
 * approach acf/vacature-apply/acf/agenda-share already use (Forminator
 * itself is installed/configured separately, no own REST/wp_mail mechanism
 * here) — but as its own, general-purpose block instead of tied to a CPT's
 * context.
 *
 * Background colour and padding are both native supports (block.json),
 * OFF by default like any other block's own Kleuren/Ruimte panel: nothing
 * renders until an editor actually picks one — get_block_wrapper_
 * attributes() is what applies whatever they choose, same as every other
 * block here.
 *
 * Which form to show is a real ACF "select" field on the block itself
 * (acf-json/group_own_forminator_form_block.json, location: block ==
 * acf/forminator-form), its choices populated dynamically from
 * Forminator_API::get_forms() (hooks/populate-form-choices.php) — same
 * reasoning acf/vacatures-uitgelicht's own Relationship field docblock
 * gives for reusing a well-polished native ACF UI instead of hand-rolling
 * a React picker: "choose one from a list" is exactly what ACF's own
 * select field already does well.
 *
 * Never renders the REAL form inside the block editor's own preview — a
 * Forminator form depends on its own front-end JS (validation, Select2,
 * intlTelInput, the multi-page pagination script, …), which Forminator only
 * ever enqueues via ITS OWN block registration (addons/pro/gutenberg/
 * library/blocks/class-forminator-gfblock-forms.php), never wired up for a
 * third-party block like this one — a live form inside the editor's
 * reduced single-block DOM would be unstyled/non-functional at best. Same
 * reasoning as blocks/google-maps' own static-placeholder-in-editor
 * approach.
 *
 * Padding is a fixed "p-4 sm:p-6 lg:p-8" — not a native Ruimte-panel
 * control — same exact class acf/cta/acf/content-blok/acf/vacatures' own
 * card-style inner content already uses, so this reads as the same kind of
 * padded box as every other coloured section on the site rather than
 * inventing its own scale. Applied unconditionally (same as those blocks:
 * cta's own card padding isn't conditional on its background either), so
 * the form still gets breathing room even with no background chosen.
 */
$form_id = get_field('form_id');
$has_form = $form_id && shortcode_exists('forminator_form');

$has_background = !empty($block['backgroundColor']) || !empty($block['style']['color']['background'] ?? '');

// Padding/radius (same "p-4 sm:p-6 lg:p-8"/"rounded-2xl" card look as acf/
// cta/acf/content-blok/acf/vacatures' own coloured boxes) only apply when a
// background colour is actually chosen — without one there's no box to
// round or pad in the first place, just a form sitting directly in the
// page/column flow. Forminator's own form markup already carries its own
// field spacing, so the unconditional version of this padding used to add a
// second, visible gap around the form wherever this block sits next to
// plain content with no padding of its own — patterns/content-met-
// formulier.php's own "text next to a form" column layout is exactly that:
// the form looked visibly more inset than the text beside it, a double
// padding no coloured card justified.
$wrapper_class = 'own-forminator-form';
if ($has_background) {
    $wrapper_class .= ' rounded-2xl p-4 sm:p-6 lg:p-8';
}

// One box, not two: the editor placeholder below only gets its OWN
// background/radius/padding when the wrapper above has none of its own —
// giving it a second, separate grey box on top of an already-coloured
// wrapper would read as "a background on a background" rather than one
// section. Its own padding here (unlike the wrapper's) isn't conditional
// on anything else: this placeholder always needs SOME breathing room
// around its icon/text in the editor, since it never inherits Forminator's
// own field spacing the way the real, front-end-only form does.
$placeholder_class = 'flex min-h-40 flex-col items-center justify-center gap-2 text-center text-sm';
$placeholder_class .= $has_background ? '' : ' rounded-2xl p-4 sm:p-6 lg:p-8 bg-gray-100 text-gray-500';
?>
<div <?= get_block_wrapper_attributes(['class' => $wrapper_class]); ?>>
    <?php if (!empty($is_preview)) : ?>
        <?php // Placeholder i.p.v. het echte formulier — zie de docblock hierboven. 
        ?>
        <div class="<?= esc_attr($placeholder_class); ?>">
            <?= ownframework_get_icon('mail', 'w-6 h-6'); ?>
            <?php if ($has_form) : ?>
                <span><?php esc_html_e('Het formulier wordt hier getoond op de website.', 'own-framework'); ?></span>
            <?php else : ?>
                <span><?php esc_html_e('Kies hiernaast een formulier.', 'own-framework'); ?></span>
            <?php endif; ?>
        </div>
    <?php elseif ($has_form) : ?>
        <?= do_shortcode('[forminator_form id="' . esc_attr($form_id) . '"]'); ?>
    <?php else : ?>
        <p class="text-center text-gray-600"><?php esc_html_e('Er is nog geen formulier gekoppeld.', 'own-framework'); ?></p>
    <?php endif; ?>
</div>