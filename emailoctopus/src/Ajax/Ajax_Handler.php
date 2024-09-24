<?php
/**
 * Generic structure for EmailOctopus plugin AJAX request handlers.
 */

namespace EmailOctopus\Ajax;

abstract class Ajax_Handler
{
    /**
     * By default, all WP ajax handlers only register for logged-in users.
     */
    public bool $enabled_for_non_logged_in_users = false;

    /**
     * The action name, i.e. `wp_ajax_{action name}` in WP hook usage.
     */
    public string $action_name = '';

    /**
     * The name of the nonce to be checked.
     *
     * @see wp_nonce_field()
     * @see check_ajax_referer()
     * @see wp_create_nonce()
     */
    public string $nonce_name = '';

    /**
     * Registers admin and, if enabled, public response handlers for specified AJAX action.
     */
    public function register_handler(): void
    {
        add_action("wp_ajax_{$this->action_name}", [$this, 'process_request']);

        if ($this->enabled_for_non_logged_in_users) {
            add_action("wp_ajax_nopriv_{$this->action_name}", [$this, 'process_request']);
        }
    }

    /**
     * Verifies the $nonce passed to this function is what this handler's $nonce_name is expecting.
     */
    protected function validate_nonce(): void
    {
        if (!check_ajax_referer($this->nonce_name, '_eo_nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Could not verify the request.', 'emailoctopus')]);
        }
    }

    /**
     * Main entry point for the AJAX request; processes that request and responds accordingly.
     */
    abstract public function process_request(): void;
}
