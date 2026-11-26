<?php
defined('ABSPATH') || exit();

/**
 * own-framework/v1/developer — de gegevens van de ontwikkelaarspagina.
 *
 *   GET   de hele pagina (ownframework_developer_state())
 *   POST  opslaan (ownframework_developer_save()), antwoordt met de nieuwe stand
 *
 * Alleen voor ownframework_is_developer(); apiFetch stuurt de wp_rest-nonce
 * mee, dus zonder ingelogde sessie komt hier niemand.
 */

add_action('rest_api_init', function (): void {
    register_rest_route('own-framework/v1', '/developer', [
        [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => 'ownframework_is_developer',
            'callback' => function (): WP_REST_Response {
                return rest_ensure_response(ownframework_developer_state());
            },
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => 'ownframework_is_developer',
            'callback' => function (WP_REST_Request $request): WP_REST_Response {
                ownframework_developer_save((array) $request->get_json_params());
                return rest_ensure_response(ownframework_developer_state());
            },
        ],
    ]);
});
