<?php

namespace EmailOctopus;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Frontend
{
    /**
     * Initializes front-end functionality.
     *
     * @return void
     */
    public function run()
    {
        $this->register_hooks();
    }

    /**
     * Define front-end action and filter callbacks.
     *
     * @return void
     */
    public function register_hooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_scripts_and_styles']);

        add_shortcode('emailoctopus', [$this, 'render_shortcode']);

        add_filter('the_content', [$this, 'maybe_add_inline_forms_at_content_top']);
        add_filter('the_content', [$this, 'maybe_add_inline_forms_at_content_bottom']);

        add_action('wp_footer', [$this, 'maybe_add_non_inline_forms'], 999);
    }

    /**
     * Registers custom styles and scripts for use on frontend; enqueued only when needed from within the shortcode.
     */
    public function register_scripts_and_styles(): void
    {
        wp_register_script(
            'emailoctopus_frontend',
            Utils::get_plugin_url('public/build/legacy-frontend.js'),
            ['jquery'],
            EMAILOCTOPUS_VERSION,
            true
        );

        wp_localize_script(
            'emailoctopus_frontend',
            'emailoctopus',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'sending' => __('Sending', 'emailoctopus'),
            ]
        );

        wp_register_style(
            'emailoctopus_frontend',
            Utils::get_plugin_url('public/css/legacy-frontend.css'),
            [],
            EMAILOCTOPUS_VERSION
        );
    }

    /**
     * Render callback for the [emailoctopus] shortcode.
     */
    public function render_shortcode(array $atts = []): string
    {
        $args = shortcode_atts(['form_id' => 0], $atts);

        ob_start();

        $form = new Form($args['form_id']);

        $form->render_form();

        return ob_get_clean();
    }

    /**
     * Return all script tags that should be rendered for a $post_type and a
     * $automatic_display.
     *
     * @param string $rule
     *
     * @return array The script tags
     */
    public function get_script_tags_to_display(string $post_type, string $automatic_display): array
    {
        $form_posts = get_posts([
            'post_type' => 'emailoctopus_form',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_emailoctopus_form_automatic_display',
                    'value' => $automatic_display,
                ],
            ],
        ]);

        $script_tags = [];

        if ($form_posts) {
            $form_posts_matching_post_type = array_filter(
                $form_posts,
                function ($form_post) use ($post_type) {
                    $form_types = (array) maybe_unserialize($form_post->_emailoctopus_form_post_types);

                    return in_array($post_type, $form_types, true);
                }
            );

            foreach ($form_posts_matching_post_type as $form_post) {
                $form = new Form($form_post->_emailoctopus_form_id);
                $script_tags[] = $form->get_script_tag();
            }
        }

        return $script_tags;
    }

    /**
     * Find all script tags that are applicable to this post type and the 'top'
     * position. Place them above `the_content`.
     */
    public function maybe_add_inline_forms_at_content_top(string $content): string
    {
        $post_type = get_post_type(get_the_ID());
        $script_tags = $this->get_script_tags_to_display($post_type, 'top');

        return implode(PHP_EOL, $script_tags) . $content;
    }

    /**
     * Find all script tags that are applicable to this post type and the
     * 'bottom' position. Place them above `the_content`.
     */
    public function maybe_add_inline_forms_at_content_bottom(string $content): string
    {
        $post_type = get_post_type(get_the_ID());
        $script_tags = $this->get_script_tags_to_display($post_type, 'bottom');

        return $content . implode(PHP_EOL, $script_tags);
    }

    /**
     * Find all script tags that are applicable to this post type and the
     * 'non_inline' position. Echo them just before <body> close.
     */
    public function maybe_add_non_inline_forms(): void
    {
        $post_type = get_post_type(get_the_ID());
        $script_tags = $this->get_script_tags_to_display($post_type, 'non_inline');

        echo implode(PHP_EOL, $script_tags);
    }
}
