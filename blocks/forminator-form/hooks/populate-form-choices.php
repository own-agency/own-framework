<?php
defined('ABSPATH') || exit();

/**
 * Populates the block's own "Formulier" select field with every published
 * Forminator form (id => name) — same Forminator_API::get_forms() call
 * Forminator's own native block uses (addons/pro/gutenberg/library/blocks/
 * class-forminator-gfblock-forms.php::get_forms()) to fill its own "Select
 * a form" dropdown, so this list matches exactly what an editor would see
 * there. Runs on acf/load_field (fires each time the field renders in the
 * block settings sidebar), not once at plugin load, so a form created or
 * renamed after this page first loaded still shows up without a cache to
 * clear.
 */
add_filter('acf/load_field/key=field_own_forminator_form_id', function ($field) {
    $field['choices'] = ['' => __('Kies een formulier…', 'own-framework')];

    if (!class_exists('Forminator_API') || !class_exists('Forminator_Form_Model')) {
        return $field;
    }

    $forms = Forminator_API::get_forms(null, 1, 100, Forminator_Form_Model::STATUS_PUBLISH);
    if (!is_array($forms)) {
        return $field;
    }

    foreach ($forms as $form) {
        $name = $form->settings['formName'] ?? $form->name;
        $field['choices'][$form->id] = $name;
    }

    return $field;
});
