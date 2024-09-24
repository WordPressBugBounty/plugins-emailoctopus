<?php
/**
 * Widget class.
 */

namespace EmailOctopus;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use WP_Widget;

/**
 * Widget class for EmailOctopus.
 */
class Widget extends WP_Widget
{
    /**
     * Unique identifier for the widget, used as the text domain when
     * internationalizing strings of text.
     *
     * @var string
     */
    protected $widget_slug = 'emailoctopus_2';

    /**
     * Specifies the classname and description, instantiates the widget, loads
     * localization files, and includes necessary stylesheets and JavaScript.
     */
    public function __construct()
    {
        // Instantiate main class.
        parent::__construct(
            $this->get_widget_slug(),
            __('EmailOctopus Form', 'emailoctopus'),
            [
                'classname' => $this->get_widget_slug() . '-class',
                'description' => __(
                    'A widget to display your EmailOctopus forms.',
                    'emailoctopus'
                ),
            ]
        );
    }

    /**
     * Returns the widget slug.
     *
     * @return string Widget slug.
     */
    public function get_widget_slug()
    {
        return $this->widget_slug;
    }

    public function form($instance)
    {
        echo '<p class="no-options-widget">';
        echo __('Widgets have been deprecated in the EmailOctopus plugin. Please delete this widget and add forms to widget areas via shortcodes.', 'emailoctopus');
        echo '</p>';

        return 'noform';
    }

    /**
     * Outputs the content of the widget.
     *
     * @param array $args     The array of form elements.
     * @param array $instance The current instance of the widget.
     */
    public function widget($args, $instance)
    {
        // Check if there is a cached output.
        $cache = wp_cache_get($this->get_widget_slug(), 'emailoctopus_widget');

        if (!is_array($cache)) {
            $cache = [];
        }

        extract($args, EXTR_SKIP);

        if (isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }

        if (isset($cache[$args['widget_id']])) {
            return print $cache[$args['widget_id']];
        }

        $form_id = !empty($instance['form_id']) ? $instance['form_id'] : 0;
        $widget_output = isset($before_widget) ? $before_widget : false;
        $widget_output .= '<div class="emailoctopus-email-widget">';

        if ($form_id !== 0) {
            // Preload some variables.
            $form_data = Deprecated::get_form_data($form_id);

            if (!$form_data) {
                return;
            }

            $settings = [];
            $settings['appearance'] = Deprecated::get_form_meta($form_id, 'appearance');
            $settings['classname'] = Deprecated::get_form_meta($form_id, 'form_class');

            // Get class names and allow them to be filterable
            switch ($settings['appearance']) {
                case 'custom':
                    $settings['main_class_names'][] = 'emailoctopus-custom-colors';

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

            ob_start();
            $form = new Form($form_id);
            $form->render_form();
            $widget_output .= ob_get_clean();
        }

        $widget_output .= '</div>';
        $widget_output .= isset($after_widget) ? $after_widget : false;

        $cache[$args['widget_id']] = $widget_output;

        wp_cache_set($this->get_widget_slug(), $cache, 'emailoctopus_widget');

        echo $widget_output;
    }

    /**
     * Flushes the widget's cache.
     */
    public function flush_widget_cache()
    {
        wp_cache_delete($this->get_widget_slug(), 'emailoctopus_widget');
    }

    /**
     * Processes the widget's options to be saved.
     *
     * @param array $new_instance The new instance of values to be generated via the update.
     * @param array $old_instance The previous instance of values before the update.
     */
    public function update($new_instance, $old_instance)
    {
        return [
            'form_id' => sanitize_text_field($new_instance['emailoctopus-form-select']),
        ];
    }
}
