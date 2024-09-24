<?php

namespace EmailOctopus;

use EmailOctopus\Ajax\Load_Forms;
use EmailOctopus\Ajax\Update_Settings;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    /**
     * EmailOctopus plugin singleton; the "one true" plugin instance that registers hooks.
     */
    private static ?Plugin $instance = null;

    /**
     * Plugin AJAX handlers.
     *
     * @var string[]
     */
    private array $ajax_handlers = [
        Update_Settings::class,
        Load_Forms::class,
    ];

    /**
     * Retrieve the EmailOctopus singleton.
     */
    public static function get_instance(): ?Plugin
    {
        if (!isset(self::$instance) && !(self::$instance instanceof Plugin)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Plugin bootstrap, essentially.
     *
     * @return void
     */
    public function run()
    {
        load_plugin_textdomain('emailoctopus', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        $this->register_hooks();
        $this->maybe_register_deprecated_hooks();

        (new Frontend())->run();

        if (is_admin()) {
            (new Admin())->run();
        }

        new Gutenberg();
    }

    /**
     * Main plugin hooks, and/or hooks for functionality used in both public-facing and admin contexts.
     */
    public function register_hooks()
    {
        add_action('activated_plugin', [$this, 'activation_redirect']);

        add_action('init', [$this, 'register_post_type']);

        foreach ($this->ajax_handlers as $ajax_handler) {
            (new $ajax_handler())->register_handler();
        }
    }

    /**
     * Redirect to the forms page (where a welcome screen awaits) on plugin activation.
     *
     * @param string $plugin Path to the plugin file relative to the `plugins` directory.
     *
     * @return void
     */
    public function activation_redirect($plugin)
    {
        if ($plugin === plugin_basename(EMAILOCTOPUS_FILE)) {
            wp_safe_redirect(admin_url('admin.php?page=emailoctopus-forms'));
            exit;
        }
    }

    /**
     * Registers Deprecated hooks when pre-3.0 DB tables are found.
     *
     * @return void
     *
     * @todo Remove when pre-3.0 code is removed.
     */
    public function maybe_register_deprecated_hooks()
    {
        if (!Utils::site_has_deprecated_forms()) {
            return;
        }

        (new Deprecated())->register_hooks();
    }

    /**
     * The private emailoctopus_form post type allows the mapping of emailoctopus.com form IDs to WP taxonomies, etc.
     *
     * @return void
     */
    public function register_post_type()
    {
        register_post_type(
            'emailoctopus_form',
            [
                'labels' => [
                    'name' => esc_html__('EmailOctopus Form', 'emailoctopus'),
                    'singular_name' => esc_html__('EmailOctopus Form', 'emailoctopus'),
                ],
                'description' => esc_html__('Private post type for mapping emailoctopus.com form data with that in WordPress.', 'emailoctopus'),
                'public' => false,
                'show_in_rest' => false,
                'supports' => ['custom-fields'],
                'rewrite' => false,
            ]
        );
    }
}
