<?php

namespace EmailOctopus;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    /**
     * Initializes admin functionality.
     */
    public function run(): void
    {
        $this->register_hooks();
    }

    /**
     * Admin actions & filters.
     */
    public function register_hooks(): void
    {
        add_action('wp_loaded', [$this, 'listen_for_api_refresh']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_scripts']);

        add_action('wp_loaded', [$this, 'listen_for_api_refresh']);
        add_action('wp_loaded', [$this, 'listen_for_api_disconnect']);

        // The action content after `admin_post_` is defined on the "action" hidden input in page-form.php.
        add_action('admin_post_emailoctopus_save_form', [$this, 'handle_save_form']);
    }

    /**
     * Clear the API responses cache if an `emailoctopus_api_refresh` nonce is
     * present in the URL.
     */
    public function listen_for_api_refresh(): void
    {
        if (isset($_GET['emailoctopus_api_refresh'])) {
            $success = false;
            if (wp_verify_nonce($_GET['emailoctopus_api_refresh'], 'emailoctopus-api-refresh')) {
                $success = Utils::clear_transients();
            }

            set_transient(
                'emailoctopus_api_refresh_status',
                $success ? 1 : -1,
                30
            );

            wp_redirect(admin_url('admin.php?page=emailoctopus-forms'));
            exit;
        }
    }

    /**
     * Clear the API responses cache if an `emailoctopus_api_disconnect` nonce
     * is present in the URL.
     */
    public function listen_for_api_disconnect(): void
    {
        if (isset($_GET['emailoctopus_api_disconnect'])) {
            $success = false;
            if (wp_verify_nonce($_GET['emailoctopus_api_disconnect'], 'emailoctopus-api-disconnect')) {
                $delete_api_key_success = delete_option('emailoctopus_api_key');
                $clear_transients_success = Utils::clear_transients();
                $delete_automatic_displays_success = Utils::delete_automatic_displays();

                $success = $delete_api_key_success &&
                    $clear_transients_success &&
                    $delete_automatic_displays_success;
            }

            set_transient(
                'emailoctopus_api_disconnect_status',
                $success ? 1 : -1,
                30
            );

            wp_redirect(admin_url('admin.php?page=emailoctopus-settings'));
            exit;
        }
    }

    /**
     * Admin JS & CSS.
     */
    public function register_admin_scripts(): void
    {
        wp_register_style('emailoctopus_admin_styles', Utils::get_plugin_url('public/css/admin.css'), [], EMAILOCTOPUS_VERSION);

        $l10n_data = [
            // Forms list page.
            'formsUntitled' => _x('Untitled', 'Fallback title for forms with no title', 'emailoctopus'),
            'formsViewOnEo' => __('View on EmailOctopus', 'emailoctopus'),
            'formsSettings' => __('Display settings', 'emailoctopus'),
            'formsError' => __(
                sprintf(
                    'Could not load forms. Check your <a href="%s">API key</a> is valid and your internet connection is working.',
                    admin_url('admin.php?page=emailoctopus-settings')
                ),
                'emailoctopus'
            ),
            'formsCopyShortcode' => __('Copy Shortcode', 'emailoctopus'),
            'formsCopyShortcodeSuccess' => __('Shortcode copied to clipboard!', 'emailoctopus'),
            'formsCopyShortcodeError' => __('Failed to copy; view form settings for shortcode', 'emailoctopus'),
            'formsTypeBar' => __('Hello bar', 'emailoctopus'),
            'formsTypeInline' => __('Inline', 'emailoctopus'),
            'formsTypeModal' => __('Pop-up', 'emailoctopus'),
            'formsTypeSlideIn' => __('Slide-in', 'emailoctopus'),
            'formsTypeUnknown' => __('Unknown', 'emailoctopus'),
            'formsAutomaticDisplayNone' => __('Nowhere (shortcode only)', 'emailoctopus'),
            'formsAutomaticDisplayNonInline' => __('On selected post types', 'emailoctopus'),
            'formsAutomaticDisplayTop' => __('At top of selected post types', 'emailoctopus'),
            'formsAutomaticDisplayBottom' => __('At bottom of selected post types', 'emailoctopus'),
            // API key page.
            'apiCheckSaving' => __('Saving…', 'emailoctopus'),
            'apiCheckSave' => __('Save Changes', 'emailoctopus'),
            'apiKeyEmpty' => __('Your API key cannot be blank.', 'emailoctopus'),
            // Etc.
            'ajaxNonce' => wp_create_nonce('_eo_nonce'),
            'formUrlBase' => admin_url('admin.php?page=emailoctopus-form'),
        ];

        wp_register_script('emailoctopus_page_api_key', Utils::get_plugin_url('public/build/page-settings.js'), [
            'jquery',
            'wp-ajax-response',
        ], EMAILOCTOPUS_VERSION, true);

        wp_register_script('emailoctopus_page_form', Utils::get_plugin_url('public/build/page-form.js'), ['jquery'], EMAILOCTOPUS_VERSION, true);
        wp_register_script('emailoctopus_page_forms', Utils::get_plugin_url('public/build/page-forms.js'), ['jquery'], EMAILOCTOPUS_VERSION, true);

        // Make l10n data available to each page's script.
        wp_localize_script('emailoctopus_page_api_key', 'emailOctopusL10n', $l10n_data);
        wp_localize_script('emailoctopus_page_form', 'emailOctopusL10n', $l10n_data);
        wp_localize_script('emailoctopus_page_forms', 'emailOctopusL10n', $l10n_data);

        // We can call wp_enqueue_script() from the app/views PHP templates, as the scripts load in the footer; but we need to get styles in the <head>.
        if (isset($_GET['page']) && in_array($_GET['page'], ['emailoctopus-forms', 'emailoctopus-form', 'emailoctopus-settings'], true)) {
            wp_enqueue_style('emailoctopus_admin_styles');
        }
    }

    /**
     * Add admin menu and sub-menus.
     */
    public function add_admin_menu(): void
    {
        add_menu_page(
            __('EmailOctopus', 'emailoctopus'),
            __('EmailOctopus', 'emailoctopus'),
            'manage_options',
            'emailoctopus-forms',
            '__return_false',
            Utils::get_icon_data_uri()
        );

        add_submenu_page(
            'emailoctopus-forms',
            __('Forms', 'emailoctopus'),
            __('Forms', 'emailoctopus'),
            'manage_options',
            'emailoctopus-forms',
            function () {
                require_once EMAILOCTOPUS_DIR . '/public/views/page-forms.php';
            }
        );

        add_submenu_page(
            'emailoctopus-forms',
            __('Settings', 'emailoctopus'),
            __('Settings', 'emailoctopus'),
            'manage_options',
            'emailoctopus-settings',
            function () {
                require_once EMAILOCTOPUS_DIR . '/public/views/page-settings.php';
            }
        );

        add_submenu_page(
            'emailoctopus-form-display-settings',
            __('Form Display Settings', 'emailoctopus'),
            __('Form Display Settings', 'emailoctopus'),
            'manage_options',
            'emailoctopus-form',
            function () {
                require_once EMAILOCTOPUS_DIR . '/public/views/page-form.php';
            }
        );
    }

    /**
     * Handles a "Save Changes" submission of a single form in the wp-admin.
     */
    public function handle_save_form(): void
    {
        $nonce = filter_input(INPUT_POST, '_eo_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $referer = filter_input(INPUT_POST, '_wp_http_referer', FILTER_SANITIZE_URL);

        // There is no legitimate reason either of these should be missing, or if either is missing, no legitimate reason to proceed.
        if (empty($referer) || empty($nonce)) {
            wp_safe_redirect(wp_login_url('', true));
            exit;
        }

        $data = filter_var_array(
            $_POST,
            [
                'emailoctopus-form-automatic-display' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'emailoctopus-form-form-id' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                'emailoctopus-form-post-id' => FILTER_SANITIZE_NUMBER_INT,
                'emailoctopus-form-post-types' => [
                    'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                    'flags' => FILTER_FORCE_ARRAY,
                ],
            ]
        );

        $post_id = $data['emailoctopus-form-post-id'];

        update_post_meta($post_id, '_emailoctopus_form_automatic_display', $data['emailoctopus-form-automatic-display']);
        update_post_meta($post_id, '_emailoctopus_form_id', $data['emailoctopus-form-form-id']);

        if (empty($data['emailoctopus-form-post-types'])) {
            delete_post_meta($post_id, '_emailoctopus_form_post_types');
        } else {
            update_post_meta($post_id, '_emailoctopus_form_post_types', $data['emailoctopus-form-post-types']);
        }

        set_transient('emailoctopus_form_settings_saved_status', true, 30);
        wp_redirect($referer);
        exit;
    }
}
