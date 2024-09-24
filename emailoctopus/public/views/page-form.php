<?php
/**
 * EmailOctopus admin page: Single form.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
{
    exit;
}

use EmailOctopus\Form;
use EmailOctopus\Utils;

$utils         = new Utils();
$api_key       = get_option( 'emailoctopus_api_key', false );
$form_id       = filter_input( INPUT_GET, 'form-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

wp_enqueue_script( 'emailoctopus_page_form' );

?>

<div class="wrap" id="emailoctopus-form-wrap">

    <?php if (get_transient('emailoctopus_form_settings_saved_status')) : ?>
        <div class="emailoctopus-notice notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Display settings saved.', 'emailoctopus' ); ?></p>
        </div>
        <?php delete_transient('emailoctopus_form_settings_saved_status') ?>
    <?php endif; ?>

    <h1 class="wp-heading-inline emailoctopus__logo">
        <img src="<?php echo esc_url( $utils->get_icon_url() ); ?>"
            alt="<?php esc_attr_e( 'EmailOctopus logo', 'emailoctopus' ); ?>"
            height="20"
        >
        <?php esc_html_e( 'Form Display Settings', 'emailoctopus' ); ?>
    </h1>

    <?php if ( empty( $api_key ) ) : ?>

        <div class="emailoctopus-notice notice notice-warning">
            <p><?php esc_html_e( 'You\'ll need to enter a valid API key first.', 'emailoctopus' ); ?></p>
            <p>
                <a href="<?php echo admin_url( 'admin.php?page=emailoctopus-settings' ); ?>"><?php esc_html_e( 'Enter EmailOctopus API Key', 'emailoctopus' ); ?>&rarr;</a>
            </p>
        </div>

    <?php elseif ( empty( $form_id ) ) : ?>

        <div class="emailoctopus-notice notice notice-warning">
            <p><?php esc_html_e( 'Invalid form ID.', 'emailoctopus' ); ?></p>
            <p>
                <a href="<?php echo admin_url( 'admin.php?page=emailoctopus-forms' ); ?>">&larr; <?php esc_html_e( 'Back to forms', 'emailoctopus' ); ?></a>
            </p>
        </div>

    <?php else : ?>

        <?php
            $form = new Form( $form_id );

            if ($form->has_errors()):
        ?>
            <div class="emailoctopus-notice notice notice-warning">
                <p><?php esc_html_e( 'Could not load form. Check this form hasn\'t been deleted and that your internet connection is working.', 'emailoctopus' ); ?></p>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=emailoctopus-forms' ); ?>">&larr; <?php esc_html_e( 'Back to forms', 'emailoctopus' ); ?></a>
                </p>
            </div>
        <?php elseif (!$form->get_script_url()): ?>
            <div class="emailoctopus-notice notice notice-warning">
                <p><?php echo __( '<a href="https://emailoctopus.com/forms/embedded/' . $form->get_id() . '/template" target="_blank" rel="nofollow">Finish designing your form</a> to configure its display settings.', 'emailoctopus' ); ?></p>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=emailoctopus-forms' ); ?>">&larr; <?php esc_html_e( 'Back to forms', 'emailoctopus' ); ?></a>
                </p>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

                <table class="form-table" id="emailoctopus-form">
                    <tbody>

                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Form', 'emailoctopus'); ?>
                            </th>
                            <td>
                                <p>
                                    <a href="https://emailoctopus.com/forms/embedded/<?php echo $form->get_id() ?>/design" target="_blank" rel="noopener">
                                        <?php !empty($form->get_name()) ? esc_html_e($form->get_name()) : esc_html_e('Untitled', 'emailoctopus'); ?>
                                        (<?php
                                            switch ($form->get_type()) {
                                                case 'bar' :
                                                    esc_html_e('hello bar', 'emailoctopus');
                                                    break;
                                                case 'inline' :
                                                    esc_html_e('inline', 'emailoctopus');
                                                    break;
                                                case 'modal':
                                                    esc_html_e('pop-up', 'emailoctopus');
                                                    break;
                                                case 'slide-in':
                                                    esc_html_e('slide-in', 'emailoctopus');
                                                    break;
                                                case null:
                                                    esc_html_e('unknown', 'emailoctopus');
                                                default:
                                                    esc_html_e($form->get_type(), 'emailoctopus');
                                            }
                                        ?>)
                                    </a>
                                </p><br>
                                <img src="<?php echo $form->get_screenshot_url(); ?>"
                                    alt="Screenshot of <?php esc_html_e($form->get_name());?>"
                                    tabindex="-1"
                                    width="120"
                                    height="120"
                                >
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'List', 'emailoctopus' ); ?></th>
                            <td>
                                <a href="https://emailoctopus.com/lists/<?php echo $form->get_list_id() ?>" target="_blank" rel="noopener">
                                    <?php !empty($form->get_list_name()) ? esc_html_e($form->get_list_name()) : esc_html_e('Untitled', 'emailoctopus'); ?>
                                </a>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><?php esc_html_e( 'Shortcode', 'emailoctopus' ); ?></th>
                            <td>
                                <p>
                                    <?php
                                    switch ($form->get_type()) {
                                        case 'bar':
                                        case 'modal':
                                        case 'slide-in':
                                            esc_html_e( 'Place this shortcode on any page or post where you want the form to appear:', 'emailoctopus' );
                                            break;
                                        case 'inline':
                                        default:
                                            esc_html_e( 'Place this shortcode anywhere you want the form to appear:', 'emailoctopus' );
                                    }
                                    ?>
                                </p>
                                <div class="emailoctopus-form-shortcode code">
                                    <?php echo sprintf('[emailoctopus form_id="%s"]', esc_html($form_id) ); ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="emailoctopus-form-automatic-display"><?php esc_html_e( 'Automatically Display', 'emailoctopus' ); ?></label>
                            </th>

                            <td>
                                <select id="emailoctopus-form-automatic-display" name="emailoctopus-form-automatic-display" autocomplete="off">
                                    <?php if ($form->get_type() === 'inline') : ?>
                                        <option value="none" <?php selected( 'none', $form->get_form_post()->_emailoctopus_form_automatic_display ); ?>><?php esc_attr_e( 'Nowhere (shortcode only)', 'emailoctopus' ); ?></option>
                                        <option value="top" <?php selected( 'top', $form->get_form_post()->_emailoctopus_form_automatic_display ); ?>><?php esc_attr_e( 'At top of selected post types', 'emailoctopus' ); ?></option>
                                        <option value="bottom" <?php selected( 'bottom', $form->get_form_post()->_emailoctopus_form_automatic_display ); ?>><?php esc_attr_e( 'At bottom of selected post types', 'emailoctopus' ); ?></option>
                                    <?php else: ?>
                                        <option value="none" <?php selected( 'none', $form->get_form_post()->_emailoctopus_form_automatic_display ); ?>><?php esc_attr_e( 'Nowhere (shortcode only)', 'emailoctopus' ); ?></option>
                                        <option value="non_inline" <?php selected( 'non_inline', $form->get_form_post()->_emailoctopus_form_automatic_display ); ?>><?php esc_attr_e( 'On selected post types', 'emailoctopus' ); ?></option>
                                    <?php endif; ?>
                                </select>

                                <div id="emailoctopus-form-post-types-container">
                                    <fieldset id="emailoctopus-form-post-types">
                                        <legend class="screen-reader-text"><?php esc_html_e( 'Post Types', 'emailoctopus' ); ?></legend>

                                        <?php $post_types = maybe_unserialize( $form->get_form_post()->_emailoctopus_form_post_types ); ?>

                                        <?php foreach ( Utils::get_displayable_post_types() as $i => $post_type ) : ?>
                                            <div>
                                                <input
                                                    type="checkbox"
                                                    id="<?php echo esc_attr( "emailoctopus-form-post-type--$i" ); ?>"
                                                    name="emailoctopus-form-post-types[]"
                                                    value="<?php echo esc_attr( $post_type->name ); ?>"
                                                    autocomplete="off"
                                                    <?php if ( ! empty( $post_types ) && in_array( $post_type->name, $post_types, true ) ) : ?>
                                                        checked="checked"
                                                    <?php endif; ?>
                                                >
                                                <label
                                                    for="<?php echo esc_attr( "emailoctopus-form-post-type--$i" ); ?>"><?php echo esc_html_e( $post_type->labels->singular_name ?? $post_type->name ); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </fieldset>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>

                <div id="emailoctopus-form-save">
                    <?php wp_nonce_field( 'emailoctopus_save_form', '_eo_nonce' ); ?>
                    <?php wp_referer_field(); ?>
                    <input type="hidden" name="emailoctopus-form-form-id" value="<?php echo esc_attr( $form_id ); ?>"/>
                    <input type="hidden" name="action" value="emailoctopus_save_form"/>
                    <input type="hidden" name="emailoctopus-form-post-id" value="<?php echo absint( $form->get_form_post()->ID ?? 0 ); ?>">
                    <?php submit_button(); ?>
                </div>
            </form>
        <?php endif; ?>

    <?php endif; ?>

</div>
