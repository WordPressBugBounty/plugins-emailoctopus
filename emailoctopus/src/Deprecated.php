<?php
/**
 * Container for deprecated methods and data, especially for functionality from pre-3.0 versions of the plugin.
 */

namespace EmailOctopus;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Deprecated
{
    public function register_hooks()
    {
        add_action('widgets_init', [$this, 'include_widget']);
        add_action('init', [$this, 'register_block']);
        add_filter('pre_render_block', [$this, 'pre_render_block'], 10, 2);
        add_action('enqueue_block_editor_assets', [$this, 'add_block_editor_scripts']);

        // Ajax handler for deprecated frontend form submissions.
        add_action('wp_ajax_submit_frontend_form', [$this, 'handle_ajax_form_submission']);
        add_action('wp_ajax_nopriv_submit_frontend_form', [$this, 'handle_ajax_form_submission']);
    }

    /**
     * Ensures that legacy block still output form content on front-end.
     *
     * @return string
     */
    public function pre_render_block($pre_render, $parsed_block)
    {
        if (empty($parsed_block['blockName']) || 'emailoctopus/form' !== $parsed_block['blockName']) {
            return null;
        }

        return (new Frontend())->render_shortcode($parsed_block['attrs']);
    }

    /**
     * Registers deprecated block.
     *
     * @deprecated 3.0
     *
     * @todo Remove this when the block no longer needs to be supported.
     */
    public function register_block()
    {
        if (function_exists('register_block_type') && Utils::site_has_deprecated_forms()) {
            register_block_type(
                EMAILOCTOPUS_DIR . 'src/json/legacy-block.json',
                [
                    'render_callback' => function ($attributes) {
                        return (new Frontend())->render_shortcode($attributes);
                    },
                ]
            );
        }
    }

    /**
     * @return void
     *
     * @todo Remove when Block is removed.
     */
    public function add_block_editor_scripts()
    {
        wp_enqueue_script(
            'emailoctopus_block',
            Utils::get_plugin_url('public/build/legacy-block.js'),
            [
                'wp-blocks',
                'wp-element',
            ],
            EMAILOCTOPUS_VERSION,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('emailoctopus_block', 'emailoctopus');
        }

        // Pass in REST URL
        wp_localize_script(
            'emailoctopus_block',
            'emailoctopus_block',
            [
                'rest_url' => esc_url(rest_url('emailoctopus/v1')),
                'svg' => Utils::get_icon_monochrome_url(),
                'nonce' => wp_create_nonce('wp_rest'),
                'loading' => __('Loading…', 'emailoctopus'),
            ]
        );
    }

    public function include_widget()
    {
        $widget = new Widget();
        register_widget($widget);
    }

    /**
     * Legacy output method for pre-3.0 forms.
     *
     * @param mixed $form_id Form ID.
     *
     * @deprecated 3.0
     */
    public static function output_template($form_id)
    {
        // Pre load some variables.
        $form_data = self::get_form_data($form_id);

        if (!$form_data) {
            return;
        }

        wp_enqueue_script('emailoctopus_frontend');
        wp_enqueue_style('emailoctopus_frontend');

        $settings = [];

        $settings['tabindex'] = 100;
        $settings['appearance'] = self::get_form_meta($form_id, 'appearance');
        $settings['consent'] = self::get_form_meta($form_id, 'consent_checkbox');
        $settings['redirect'] = self::get_form_meta($form_id, 'redirect_checkbox');
        $settings['redirect_url'] = self::get_form_meta($form_id, 'redirect_url');
        $settings['consent_content'] = self::get_form_meta($form_id, 'consent_content');
        $settings['classname'] = self::get_form_meta($form_id, 'form_class');
        $settings['button_color'] = self::get_form_meta($form_id, 'button_color');
        $settings['button_text_color'] = self::get_form_meta($form_id, 'button_text_color');
        $settings['custom_fields'] = self::get_custom_fields($form_id);
        $settings['list_id'] = $form_data['list_id'];

        $settings['show_consent'] = self::get_form_meta($form_id, 'consent_checkbox') === '1';
        $settings['show_branding'] = self::get_form_meta($form_id, 'branding') === '1';

        // Get Messages.
        $settings['message_submit'] = self::get_form_meta($form_id, 'message_submit');
        $settings['message_success'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_success'));
        $settings['message_missing_email'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_missing_email'));
        $settings['message_invalid_email'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_invalid_email'));
        $settings['message_bot'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_bot'));
        $settings['message_consent_required'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_consent_required'));
        $settings['message_unknown'] = apply_filters('emailoctopus_the_content', self::get_form_meta($form_id, 'message_unknown'));

        // Get class names and allow them to be filterable.
        $settings['main_class_names'] = ['emailoctopus-form-wrapper'];

        // Adding custom styles
        $settings['main_class_styles'] = [];

        switch ($settings['appearance']) {
            case 'custom':
                $settings['main_class_names'][] = 'emailoctopus-custom-colors';
                $settings['main_class_styles'][] = sprintf(
                    'background: %s !important; ',
                    self::get_form_meta($form_id, 'background_color')
                );

                $settings['main_class_styles'][] = sprintf(
                    'color: %s !important;',
                    self::get_form_meta($form_id, 'text_color')
                );
                break;

            case 'light':
                $settings['main_class_names'][] = 'emailoctopus-theme-light';
                break;

            case 'dark':
                $settings['main_class_names'][] = 'emailoctopus-theme-dark';
                break;
        }

        if (!empty($settings['classname'])) {
            $classname = explode(' ', $settings['classname']);

            foreach ($classname as $class) {
                $settings['main_class_names'][] = $class;
            }
        }

        $tabindex = $settings['tabindex'];
        $main_class_names = apply_filters('emailoctopus_class_names', $settings['main_class_names'], $form_id);

        if (!is_array($main_class_names)) {
            $main_class_names = false;
        } ?>

        <div
            class="<?php echo esc_attr(implode(' ', $main_class_names)); ?>" <?php echo $settings['appearance'] === 'custom' ? sprintf('style="%s"', implode(' ', $settings['main_class_styles'])) : ''; ?>>

            <form method="post" action="https://emailoctopus.com/lists/<?php echo esc_attr($settings['list_id']); ?>/members/external-add"
                  class="emailoctopus-form">

                <div class="emailoctopus-form-textarea-hidden" aria-hidden="true">
                    <textarea class="emailoctopus-form-textarea-hidden" name="message_consent_required"
                              aria-hidden="true"><?php echo esc_textarea(wp_kses_post($settings['message_consent_required'])); ?></textarea>
                    <textarea class="emailoctopus-form-textarea-hidden" name="message_missing_email"
                              aria-hidden="true"><?php echo esc_textarea(wp_kses_post($settings['message_missing_email'])); ?></textarea>
                    <textarea class="emailoctopus-form-textarea-hidden" name="message_invalid_email"
                              aria-hidden="true"><?php echo esc_textarea(wp_kses_post($settings['message_invalid_email'])); ?></textarea>
                    <textarea class="emailoctopus-form-textarea-hidden" name="message_bot"
                              aria-hidden="true"><?php echo esc_textarea(wp_kses_post($settings['message_bot'])); ?></textarea>
                    <textarea class="emailoctopus-form-textarea-hidden" name="message_success"
                              aria-hidden="true"><?php echo esc_textarea(wp_kses_post($settings['message_success'])); ?></textarea>
                </div>

                <?php
                $show_title = isset($settings['show_title']) ? $settings['show_title'] : true;
        $show_description = isset($settings['show_description']) ? $settings['show_description'] : true;

        if ($show_title) {
            printf('<h2 class="emailoctopus-heading">%s</h2>', wp_kses_post($form_data['title']));
        }

        if ($show_description) {
            printf('<p>%s</p>', wp_kses_post($form_data['description']));
        } ?>

                <p class="emailoctopus__success-message"></p>
                <p class="emailoctopus__error-message"></p>

                <div class="emailoctopus-form-copy-wrapper">
                    <input type="hidden" name="emailoctopus_form_id" value="<?php echo absint($form_id); ?>"/>
                    <input type="hidden" name="emailoctopus_list_id" value="<?php echo esc_attr($settings['list_id']); ?>"/>

                    <?php foreach ($settings['custom_fields'] as $custom_field) : ?>

                        <div class="emailoctopus-form-row">

                            <?php
                            printf(
                                '<label><span class="emailoctopus-label">%s %s</span><br><input type="%s" name="%s" class="emailoctopus-custom-fields" tabindex="%d" /></label>',
                                esc_html($custom_field['label']),
                                $custom_field['tag'] === 'EmailAddress' ? '<span class="required">*</span>' : '',
                                esc_attr(strtolower($custom_field['type'])),
                                esc_attr($custom_field['tag']),
                                absint($tabindex)
                            ); ?>

                        </div>

                        <?php ++$tabindex; ?>
                    <?php endforeach; ?>

                    <?php if (isset($settings['show_consent']) && $settings['show_consent']) : ?>
                        <div class="emailoctopus-form-row">
                            <label>
                                <input type="checkbox" name="consent" class="emailoctopus-consent"
                                       tabindex="<?php echo absint($tabindex); ?>"/>&nbsp;<?php echo wp_kses_post($settings['consent_content']); ?>
                            </label>
                        </div>
                        <?php ++$tabindex; ?>
                    <?php endif; ?>

                    <div class="emailoctopus-form-row-hp" aria-hidden="true">
                        <!-- Do not remove this field, otherwise you risk bot signups -->
                        <input type="text" name="hp<?php echo esc_attr($settings['list_id']); ?>" tabindex="-1" autocomplete="nope">
                    </div>

                    <div class="emailoctopus-form-row-subscribe">
                        <?php if ($settings['redirect']) : ?>
                            <?php printf('<input type="hidden" name="successRedirectUrl" class="emailoctopus-success-redirect-url" value="%s" />', esc_attr($settings['redirect_url'])); ?>
                        <?php endif; ?>
                        <button type="submit" tabindex="<?php echo absint($tabindex); ?>"
                                style="<?php echo $settings['appearance'] === 'custom' ? sprintf('background: %s; color: %s;', esc_attr($settings['button_color']), esc_attr($settings['button_text_color'])) : ''; ?>"><?php echo esc_html($settings['message_submit']); ?></button>
                    </div>

                    <?php if (isset($settings['show_branding']) && $settings['show_branding']) : ?>
                        <div class="emailoctopus-referral">
                            <?php // translators: %s: EmailOctopus home page URL.?>
                            <?php echo sprintf(__('Powered by <a href="%s" target="_blank" rel="noopener">EmailOctopus</a>', 'emailoctopus'), 'https://emailoctopus.com?utm_source=form&utm_medium=wordpress_plugin'); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </form>
        </div>

        <?php
    }

    /**
     * Processes form submissions from deprecated forms.
     *
     * @return void
     *
     * @deprecated 3.0
     */
    public static function handle_ajax_form_submission()
    {
        $api = sprintf('https://emailoctopus.com/api/1.6/lists/%s/contacts', sanitize_text_field($_POST['list_id']));
        $form_data = $_POST['form_data'];
        $body = ['api_key' => get_option('emailoctopus_api_key')];
        $fields = [];
        $custom_fields = [];

        foreach ($form_data as $index => $data) {
            if ($data['name'] === 'EmailAddress') {
                $email = $data['value'];

                if (!is_email($email)) {
                    wp_send_json(
                        [
                            'errors' => true,
                            'message' => 'message_invalid_email',
                        ]
                    );
                    exit;
                }

                $body['email_address'] = $data['value'];
            } else {
                $fields[$data['name']] = $data['value'];
            }
        }

        if (!empty($fields)) {
            $body['fields'] = $fields;
        }

        $response = wp_remote_post($api, ['body' => $body]);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body);

        // Error detected - send message.
        if (isset($response_body->error)) {
            wp_send_json(
                [
                    'errors' => true,
                    'message' => $response_body->error->message,
                ]
            );
        }

        // Contact pending, send response.
        wp_send_json(
            [
                'errors' => false,
                'message' => 'message_success',
            ]
        );
    }

    /**
     * Get a piece of form meta.
     *
     * @param int    $form_id  The form ID.
     * @param string $meta_key The meta key to retrieve the value for.
     *
     * @return mixed false, single meta value (string), or array of values
     */
    public static function get_form_meta($form_id, $meta_key)
    {
        // Make sure form ID is valid
        if (!is_numeric($form_id)) {
            return false;
        }
        $form_id = absint($form_id);
        if (!$form_id) {
            return false;
        }

        // Get cache.
        $meta_cache = wp_cache_get($form_id, 'emailoctopus_meta_key_results');

        if (!$meta_cache) {
            global $wpdb;
            $meta_table_name = $wpdb->prefix . 'emailoctopus_forms_meta';
            $query = $wpdb->prepare(
                "SELECT * FROM {$meta_table_name} WHERE form_id = %d",
                $form_id
            );
            $results = $wpdb->get_results($query);
            $meta_value = '';
            foreach ($results as $index => $values) {
                if ($values->meta_key === $meta_key) {
                    $meta_value = $values->meta_value;

                    break;
                }
            }
            if (empty($meta_value)) {
                return false;
            }
            wp_cache_set($form_id, $results, 'emailoctopus_meta_key_results');

            return $meta_value;
        } else {
            foreach ($meta_cache as $index => $values) {
                if ($values->meta_key === $meta_key) {
                    $meta_value = $values->meta_value;

                    return $meta_value;
                }
            }
        }

        return false;
    }

    /**
     * Get form data.
     *
     * @param int $form_id The form ID.
     *
     * @return mixed false, single meta value (string), or array of values
     */
    public static function get_form_data($form_id)
    {
        // Make sure form ID is valid
        if (!is_numeric($form_id)) {
            return false;
        }
        $form_id = absint($form_id);
        if (!$form_id) {
            return false;
        }

        // Get cache
        $meta_cache = wp_cache_get($form_id, 'emailoctopus_form_table_results');

        if (!$meta_cache) {
            global $wpdb;
            $form_table_name = $wpdb->prefix . 'emailoctopus_forms';
            $query = $wpdb->prepare(
                "SELECT * FROM {$form_table_name} WHERE form_id = %d",
                $form_id
            );
            $results = $wpdb->get_results($query, ARRAY_A);
            if (empty($results)) {
                return false;
            }
            wp_cache_set($form_id, $results[0], 'emailoctopus_form_table_results');

            return $results[0];
        } else {
            return $meta_cache;
        }

        return false;
    }

    /**
     * Get form custom fields.
     *
     * @param int $form_id The form ID.
     *
     * @return mixed false, single meta value (string), or array of values
     */
    public static function get_custom_fields($form_id)
    {
        // Make sure form ID is valid.
        if (!is_numeric($form_id)) {
            return false;
        }
        $form_id = absint($form_id);
        if (!$form_id) {
            return false;
        }

        // Get cache.
        $meta_cache = wp_cache_get($form_id, 'emailoctopus_custom_fields_results');

        if (!$meta_cache) {
            global $wpdb;
            $table = $wpdb->prefix . 'emailoctopus_custom_fields';
            $query = $wpdb->prepare("SELECT * FROM {$table} where form_id = %d order by `order` ASC", $form_id);
            $results = $wpdb->get_results($query, ARRAY_A);
            if (empty($results)) {
                return false;
            }
            wp_cache_set($form_id, $results, 'emailoctopus_custom_fields_results');

            return $results;
        } else {
            return $meta_cache;
        }

        return false;
    }
}
