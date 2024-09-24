<?php

namespace EmailOctopus;

use InvalidArgumentException;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Form class for EmailOctopus.
 */
class Form
{
    /**
     * The EmailOctopus form ID.
     */
    private string $form_id = '';

    /**
     * Form data as received from EmailOctopus.com API.
     */
    private array $form_data = [];

    /**
     * Errors to be rendered to the UI.
     */
    private array $errors = [];

    /**
     * Is this a pre-3.0 form?
     */
    private bool $is_deprecated_form = false;

    /**
     * Constructor.
     *
     * @param string $form_id The form ID.
     */
    public function __construct(string $form_id = '', bool $populate_data_from_api = true)
    {
        if (empty($form_id)) {
            // Nothing to render: form not selected yet.
            return;
        }

        $this->form_id = $form_id;
        if ($populate_data_from_api) {
            $this->populate_data_from_api();
        }
    }

    public function set_form_data(array $data)
    {
        $this->form_data = $data;
    }

    /**
     * Sets Form Data available to other class methods.
     */
    private function populate_data_from_api(): void
    {
        if (empty($this->form_id)) {
            throw new InvalidArgumentException(
                'This form does not have a form_id set, so its data cannot be retrieved from the API.'
            );
        }

        if (Utils::site_has_deprecated_forms()) {
            $deprecated_form_data = Deprecated::get_form_data($this->form_id);

            if (is_array($deprecated_form_data) && !empty($deprecated_form_data)) {
                $this->is_deprecated_form = true;
                $this->form_data = $deprecated_form_data;

                return;
            }
        }

        if ($this->has_errors()) {
            return;
        }

        $data = Utils::get_api_data(
            'https://emailoctopus.com/api/1.6/forms/' . $this->form_id,
            ['api_key' => get_option('emailoctopus_api_key', false)],
            MINUTE_IN_SECONDS,
            is_admin()
        );

        if (is_wp_error($data) || isset($data->error)) {
            $this->errors[] = $data->error;

            return;
        }

        $this->set_form_data(
            (array) $data // Cast from stdClass to array
        );
    }

    /**
     * Accessor for form data.
     */
    public function get_form_data(): array
    {
        return $this->form_data ?? [];
    }

    /**
     * Get the form's ID as defined on EmailOctopus.com.
     */
    public function get_id(): string
    {
        return trim($this->get_form_data()['id'] ?? '');
    }

    /**
     * Get the form's Name as defined on EmailOctopus.com.
     */
    public function get_name(): string
    {
        return trim($this->get_form_data()['name'] ?? '');
    }

    /**
     * Get the form's Type as defined on EmailOctopus.com.
     */
    public function get_type(): string
    {
        return trim($this->get_form_data()['type'] ?? '');
    }

    /**
     * Get the form's screenshot URL as defined on EmailOctopus.com.
     */
    public function get_screenshot_url(): string
    {
        return trim($this->get_form_data()['screenshot_url'] ?? '');
    }

    /**
     * Get the ID of this form's connected List.
     */
    public function get_list_id(): string
    {
        return trim($this->get_form_data()['list_id'] ?? '');
    }

    /**
     * Get the Name of this form's connected List.
     */
    public function get_list_name(): string
    {
        if (!empty($this->get_form_data()['list_name'])) {
            return trim($this->get_form_data()['list_name']);
        }

        $list_id = $this->get_list_id();

        if (empty($list_id)) {
            return '';
        }

        $list = new ContactList($list_id);

        return trim($list->get_list_data()['name'] ?? '');
    }

    /**
     * Get the script URL for this form.
     */
    public function get_script_url(): string
    {
        return trim($this->get_form_data()['script_url'] ?? '');
    }

    /**
     * Get the script tag necessary for rendering the form.
     */
    public function get_script_tag(): string
    {
        return sprintf(
            '<script async src="%s" data-form="%s"></script>',
            $this->get_script_url(),
            $this->get_id()
        );
    }

    /**
     * Looks for an `emailoctopus_form` post type corresponding to the Form ID.
     *
     * @return array|int|WP_Post
     */
    public function get_form_post()
    {
        $form_post = get_posts([
            'post_type' => 'emailoctopus_form',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_key' => '_emailoctopus_form_id',
            'meta_value' => $this->form_id,
            'no_found_rows' => true,
        ]);

        if (empty($form_post)) {
            $form_post_id = $this->create_form_post();
            $form_post = [get_post($form_post_id)];
        }

        return $form_post[0];
    }

    /**
     * Creates a new `emailoctopus_form` post type, whose meta links the WordPress data to the EmailOctopus.com version of the form.
     */
    public function create_form_post(): int
    {
        $post_id = wp_insert_post(
            [
                'post_type' => 'emailoctopus_form',
                'post_status' => 'publish',
            ]
        );

        update_post_meta($post_id, '_emailoctopus_form_id', $this->form_id);

        return $post_id;
    }

    public function has_errors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Display form.
     *
     * @todo Remove deprecated method arguments when pre-3.0 style forms no longer supported.
     */
    public function render_form(): void
    {
        if ($this->is_deprecated_form()) {
            Deprecated::output_template($this->form_id);

            return;
        }

        if ($this->has_errors()) {
            ?>
            <div class="emailoctopus-form-error" role="alert">
                <p>
                    <?php
                    esc_html_e('This EmailOctopus form cannot be rendered.', 'emailoctopus');
            if (is_user_logged_in()) {
                echo ' ' . esc_html__('Errors:', 'emailoctopus');
            } ?>
                </p>
                <?php
                if (is_user_logged_in()) {
                    echo '<ul>';
                    foreach ($this->errors as $error) {
                        echo sprintf(
                            '<li>%s: %s</li>',
                            isset($error->code) ? esc_html($error->code) : '',
                            isset($error->message) ? esc_html($error->message) : ''
                        );
                    }
                    echo '</ul>';
                } ?>
            </div>
            <?php

            return;
        }

        echo $this->get_script_tag();
    }

    /**
     * Accessor for deprecated form flag.
     */
    public function is_deprecated_form(): bool
    {
        return $this->is_deprecated_form;
    }
}
