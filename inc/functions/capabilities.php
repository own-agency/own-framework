<?php
defined('ABSPATH') || exit();

/**
 * The theme's own capabilities, and granting them to the roles that existed
 * before them.
 *
 * Every CPT has its own capability type ("Rename capabilities" in its
 * acf-json/post_type_*.json), so a role can be allowed vacancies without also
 * getting news posts — which is what makes a role editor (User Role Editor)
 * show a separate checkbox group per post type. The taxonomies and options
 * pages follow suit:
 *
 *   edit_vacancies, publish_vacancies, …   vacancy CPT (likewise faqs,
 *                                           agendas, verhalen)
 *   manage_vacancy_terms                   add/edit/delete that CPT's terms
 *                                           (likewise faq, agenda, verhaal);
 *                                           assigning terms = edit_vacancies
 *   manage_notifications                   Melding(en)
 *
 * and one capability per Thema opties page, so each page can be switched on
 * or off per role on its own (OWNFRAMEWORK_SETTINGS_CAPABILITIES below).
 *
 * "Thema opties" itself has "redirect": 1 and uses Algemeen's capability. A
 * role without Algemeen but with another page still gets the top-level menu:
 * WP core (wp-admin/includes/menu.php) makes the first submenu item the user
 * CAN access the new parent.
 *
 * Nobody has these capabilities until they're granted, so each grant step
 * runs ONCE per site, mirroring what each role already had. After that the
 * roles are the site's own, managed in User Role Editor, so a step never runs
 * again. To grant a NEW capability, add a step and raise
 * OWNFRAMEWORK_CAPS_VERSION — never re-grant capabilities from an earlier
 * step, that would undo a site's own role changes.
 *
 * These capability names are stored in each site's wp_user_roles option: never
 * rename them. On multisite every site has its own roles, so this runs per site
 * (on that site's first admin request).
 */

const OWNFRAMEWORK_CAPS_VERSION = 3;

// Post type slug => plural capability name. Must match the singular/plural
// capability names in each CPT's acf-json/post_type_*.json.
const OWNFRAMEWORK_CPT_CAPABILITIES = [
    'vacancy' => 'vacancies',
    'faq' => 'faqs',
    'agenda' => 'agendas',
    'verhaal' => 'verhalen',
];

// Thema opties page => its capability, as set in its acf-json/ui_options_
// page_*.json.
const OWNFRAMEWORK_SETTINGS_CAPABILITIES = [
    'Algemeen' => 'manage_theme_general',
    'Stijlen' => 'manage_theme_styles',
    'Forminator' => 'manage_theme_forminator',
    'Live search' => 'manage_theme_live_search',
    // Koppelingen (manage_theme_integrations) is nu developer/; step 2
    // kent die capability nog toe, maar niets controleert 'm meer.
    'Vacatures' => 'manage_theme_vacancies',
    'Instagram' => 'manage_theme_instagram',
];

/**
 * Step 1: CPT and term capabilities, plus Melding(en).
 *
 * A role that could publish posts may now publish vacancies, one with
 * manage_categories may manage every CPT's terms, and editors/admins
 * (edit_others_posts) keep Melding(en). Authors and contributors deliberately
 * lose it — they only reached it through edit_posts.
 */
function ownframework_caps_step_1(WP_Role $role): void
{
    $post_caps = [
        'edit_posts',
        'edit_others_posts',
        'edit_private_posts',
        'edit_published_posts',
        'publish_posts',
        'read_private_posts',
        'delete_posts',
        'delete_others_posts',
        'delete_private_posts',
        'delete_published_posts',
    ];

    foreach (OWNFRAMEWORK_CPT_CAPABILITIES as $singular => $plural) {
        foreach ($post_caps as $cap) {
            if ($role->has_cap($cap)) {
                $role->add_cap(str_replace('posts', $plural, $cap));
            }
        }
        if ($role->has_cap('manage_categories')) {
            $role->add_cap("manage_{$singular}_terms");
        }
    }

    if ($role->has_cap('edit_others_posts')) {
        $role->add_cap('manage_notifications');
    }
}

/**
 * Step 2: one capability per Thema opties page.
 *
 * Replaces the shared manage_theme_settings of version 1 (Algemeen, Stijlen,
 * Forminator, Live search): a site that already had it keeps exactly those
 * pages per role, then the shared capability is removed. A site that never had
 * version 1 grants them to editors/admins (edit_others_posts), same as step 1
 * did. Koppelingen goes to whoever had manage_options (its old capability),
 * Vacatures to whoever had manage_vacancy_terms (its old capability).
 */
function ownframework_caps_step_2(WP_Role $role, int $from_version): void
{
    $shared = $from_version >= 1
        ? $role->has_cap('manage_theme_settings')
        : $role->has_cap('edit_others_posts');

    if ($shared) {
        $role->add_cap('manage_theme_general');
        $role->add_cap('manage_theme_styles');
        $role->add_cap('manage_theme_forminator');
        $role->add_cap('manage_theme_live_search');
    }
    if ($role->has_cap('manage_options')) {
        $role->add_cap('manage_theme_integrations');
    }
    if ($role->has_cap('manage_vacancy_terms')) {
        $role->add_cap('manage_theme_vacancies');
    }

    $role->remove_cap('manage_theme_settings');
}

/**
 * Step 3: Thema opties → Instagram (inc/functions/instagram-settings-page.php)
 * for editors/admins (edit_others_posts), same group as step 2's pages.
 */
function ownframework_caps_step_3(WP_Role $role): void
{
    if ($role->has_cap('edit_others_posts')) {
        $role->add_cap('manage_theme_instagram');
    }
}

// Not admin_init: wp-admin/admin.php builds the admin menu (and refuses pages
// the user can't access) BEFORE admin_init, so granting there left the first
// admin request after a deploy with the old capabilities. init runs earlier.
add_action('init', function (): void {
    if (!is_admin()) {
        return;
    }

    $from_version = (int) get_option('ownframework_caps_version');
    if ($from_version >= OWNFRAMEWORK_CAPS_VERSION) {
        return;
    }

    foreach (wp_roles()->role_objects as $role) {
        if ($from_version < 1) {
            ownframework_caps_step_1($role);
        }
        if ($from_version < 2) {
            ownframework_caps_step_2($role, $from_version);
        }
        if ($from_version < 3) {
            ownframework_caps_step_3($role);
        }
    }
    update_option('ownframework_caps_version', OWNFRAMEWORK_CAPS_VERSION);

    // The current user's caps were computed from the roles before the grant.
    wp_get_current_user()->get_role_caps();
});

/**
 * User Role Editor: file the theme's capabilities under a findable group
 * instead of the flat "Custom capabilities" list. Term capabilities join their
 * CPT's own group (URE keys that group by post type slug), the settings
 * capabilities get a "Thema opties" group. Both filters are inert without URE.
 */
add_filter('ure_capabilities_groups_tree', function (array $groups): array {
    $groups['own_framework_settings'] = [
        'caption' => 'Thema opties',
        'parent' => 'custom',
        'level' => 2,
    ];
    return $groups;
});

add_filter('ure_custom_capability_groups', function (array $groups, string $cap_id): array {
    $settings_caps = array_merge(array_values(OWNFRAMEWORK_SETTINGS_CAPABILITIES), ['manage_notifications']);
    if (in_array($cap_id, $settings_caps, true)) {
        $groups[] = 'own_framework_settings';
    }

    foreach (array_keys(OWNFRAMEWORK_CPT_CAPABILITIES) as $post_type) {
        if ($cap_id === "manage_{$post_type}_terms") {
            $groups[] = 'custom_post_types';
            $groups[] = $post_type;
        }
    }

    return $groups;
}, 10, 2);
