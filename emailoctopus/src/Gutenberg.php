<?php
/**
 * Gutenberg Block.
 */

namespace EmailOctopus;

use WP_REST_Request;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers all the plugin blocks.
 */
class Gutenberg
{
    /**
     * Namespace of REST API.
     */
    public string $rest_api_namespace = 'emailoctopus/v1';

    /**
     * Call Gutenberg actions.
     */
    public function __construct()
    {
        // Register custom Block Category.
        add_filter('block_categories_all', [$this, 'block_category']);
        // Form Block.
        add_action('init', [$this, 'register_form_editor_scripts'], 10);
        add_action('init', [$this, 'register_form_block'], 11);
        // Register REST API route for shortcode preview.
        add_action('rest_api_init', [$this, 'preview_shortcode_endpoint']);
    }

    /**
     * Register custom Block Category.
     *
     * @param array $categories Array of block categories.
     *
     * @return array.
     */
    public function block_category($categories): array
    {
        $categories[] = [
            'slug' => 'emailoctopus',
            'title' => esc_html__('EmailOctopus', 'emailoctopus'),
        ];

        return $categories;
    }

    /**
     * Registers and enqueues Block Editor script.
     */
    public function register_form_editor_scripts(): void
    {
        $api_key_valid = true;
        $forms = [];
        // Check the API key and retrieve the forms only on edit post / page screen.
        if (is_admin()) {
            global $pagenow;
            if (in_array($pagenow, ['post.php', 'post-new.php', 'widgets.php'], true)) {
                $api_key = get_option('emailoctopus_api_key', false);
                $api_key_valid = Utils::is_valid_api_key($api_key);
                $forms = $this::get_forms();
            }
        }

        $api_connection_required = wp_sprintf(
            /* translators: %1$s - create account button, %2$s - connect account button */
            '<div>%s<br><br>%s<br>%s</div>',
            esc_html__('To use this block, create an EmailOctopus account or connect an existing one.', 'emailoctopus'),
            wp_sprintf(
                '<a target="_blank" rel="noopener" href="' . apply_filters('emailoctopus_block_link_create_account', 'https://emailoctopus.com/account/sign-up?utm_source=wordpress_plugin&utm_medium=referral&utm_campaign=welcome_banner') . '">%s</a>',
                esc_html__('Create an account for free', 'emailoctopus')
            ),
            wp_sprintf(
                '<a target="_blank" href="' . apply_filters('emailoctopus_block_link_connect_account', admin_url('admin.php?page=emailoctopus-settings')) . '">%s</a>',
                esc_html__('Connect an existing account', 'emailoctopus')
            )
        );

        $no_forms = wp_sprintf(
            /* translators: %s - create form link */
            esc_html__('No forms yet. %s.', 'emailoctopus'),
            '<a target="_blank" rel="noopener" href="https://emailoctopus.com/forms/embedded/list">' . esc_html__('Create one', 'emailoctopus') . '</a>',
        );

        $block_data = [
            'forms' => $forms,
            'is_connected' => $api_key_valid,
            'labels' => [
                'untitled' => esc_html__('Untitled', 'emailoctopus'),
                'tab_label' => esc_html__('General', 'emailoctopus'),
                'select_label' => esc_html__('Form', 'emailoctopus'),
                'api_connection_required' => $api_connection_required,
                'add_form' => esc_html__('Select a form using the sidebar.', 'emailoctopus'),
                /* translators: %1$s - form name, %2$s - form type */
                'preview_form_selection' => esc_html__('%1$s (%2$s)', 'emailoctopus'),
                'preview_form_view' => esc_html__('Click preview or publish to view.', 'emailoctopus'),
                /* translators: %s - form_id */
                'error_form_not_found' => esc_html__('Error: could not load form %s from the API.', 'emailoctopus'),
                'no_forms' => $no_forms,
                'display_settings' => esc_html__('Display settings', 'emailoctopus'),
                'view_on' => esc_html__('View on EmailOctopus', 'emailoctopus'),
            ],
            'form_url_base' => admin_url('admin.php?page=emailoctopus-form'),
        ];
        wp_register_style('emailoctopus-form', Utils::get_plugin_url('public/build/block.css'), [], EMAILOCTOPUS_VERSION);
        wp_register_script('emailoctopus-form', Utils::get_plugin_url('public/build/block.js'), ['wp-blocks', 'wp-element'], EMAILOCTOPUS_VERSION, true);
        wp_localize_script('emailoctopus-form', 'emailoctopus_form', $block_data);
    }

    /**
     * Registers Form Block with PHP.
     */
    public function register_form_block(): void
    {
        if (function_exists('register_block_type')) {
            register_block_type(
                EMAILOCTOPUS_DIR . 'src/json/block.json',
                [
                    'render_callback' => [$this, 'form_render_callback'],
                ]
            );
        }
    }

    /**
     * Renders the subscription form on front-end using the corresponding shortcode.
     *
     * @param array $attributes Block attributes.
     *
     * @return string HTML.
     */
    public function form_render_callback($attributes): string
    {
        $form_id = '';
        if (isset($attributes['form_id'])) {
            $form_id = $attributes['form_id'];
        }

        return do_shortcode('[emailoctopus form_id="' . $form_id . '"]');
    }

    /**
     * Returns an array of forms data.
     *
     * @return array Array of forms prepared for the block.
     */
    public static function get_forms(): array
    {
        $forms = [];
        $forms[] = [
            'value' => '',
            'label' => esc_html__('None', 'emailoctopus'),
            'type' => '',
        ];
        $forms_api = Utils::get_forms();
        if (!empty($forms_api)) {
            foreach ($forms_api as $form) {
                $form_name = $form->name ?? esc_html__('Untitled', 'emailoctopus');
                switch ($form->type) {
                    case 'bar':
                        $form_type_friendly = esc_html__('hello bar', 'emailoctopus');
                        break;
                    case 'inline':
                        $form_type_friendly = esc_html__('inline', 'emailoctopus');
                        break;
                    case 'modal':
                        $form_type_friendly = esc_html__('pop-up', 'emailoctopus');
                        break;
                    case 'slide-in':
                        $form_type_friendly = esc_html__('slide-in', 'emailoctopus');
                        break;
                    default:
                        $form_type_friendly = esc_html__($form->type, 'emailoctopus');
                        break;
                }
                $forms[] = [
                    'value' => $form->id,
                    'name' => $form_name,
                    'label' => sprintf('%s (%s)', $form_name, $form_type_friendly),
                    'type' => $form->type,
                    'type_friendly' => $form_type_friendly,
                ];
            }
        }

        return $forms;
    }

    /**
     * Registers rest route to preview the shortcodes.
     */
    public function preview_shortcode_endpoint(): void
    {
        $route = '/preview-shortcode';
        $route_params = [
            'methods' => 'GET',
            'callback' => [$this, 'preview_shortcode_callback'],
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ];
        register_rest_route($this->rest_api_namespace, $route, $route_params);
    }

    /**
     * Preview the shortcode.
     *
     * @paramWP_REST_Request $request Request object.
     *
     * @return array.
     */
    public function preview_shortcode_callback(WP_REST_Request $request): array
    {
        return [
            'js' => do_shortcode($request->get_param('shortcode')),
            'html' => '',
            'style' => '<style>.grecaptcha-badge {display: none!important;}</style>',
        ];
    }
}
