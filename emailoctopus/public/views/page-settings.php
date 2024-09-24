<?php
/**
 * EmailOctopus admin page: Settings.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
{
    exit;
}

use EmailOctopus\Utils;

$utils         = new Utils();
$api_key       = get_option( 'emailoctopus_api_key', false );
$api_key_valid = $utils->is_valid_api_key( $api_key );

// Only available to the user if the API key is actually connected.
$api_disconnect_url = add_query_arg(
    [
        'emailoctopus_api_disconnect' => wp_create_nonce( 'emailoctopus-api-disconnect' )
    ],
    admin_url( 'admin.php?page=emailoctopus-settings' )
);

wp_enqueue_script( 'emailoctopus_page_api_key' );

?>

<div class="emailoctopus-api-key wrap">
    <h1 class="wp-heading-inline emailoctopus__logo">
        <img src="<?php echo esc_url( $utils->get_icon_url() ); ?>"
            alt="<?php esc_attr_e( 'EmailOctopus logo', 'emailoctopus' ); ?>"
            height="20"
        >
        <?php esc_html_e( 'EmailOctopus Settings', 'emailoctopus' ); ?>
    </h1>
</div>

<?php
$api_disconnect_status = get_transient('emailoctopus_api_disconnect_status' );
if ($api_disconnect_status === '-1'): ?>
    <div class="emailoctopus-notice notice notice-error is-dismissible">
    <p>
            <?php
            echo __(
                sprintf(
                    'Could not disconnect from the API. <a href="%s">Try again</a>.',
                    esc_url( $api_disconnect_url )
                ),
                'emailoctopus'
            );
            ?>
        </p>
    </div>
<?php
endif;
?>

<form id="api_key_form" method="POST" action="#">
    <?php wp_nonce_field( 'emailoctopus_save_api_key', '_eo_nonce' ); ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'Status', 'emailoctopus' ); ?></th>
                <td class="emailoctopus-api-key-status-container-connected"<?php echo $api_key_valid ? '' : ' style="display: none;"'; ?>>
                    <div class="emailoctopus-api-key-status emailoctopus-api-key-connected">
                        <?php esc_html_e( 'Connected', 'emailoctopus' ); ?>
                    </div>

                    <a href="<?php echo esc_url( $api_disconnect_url ); ?>" class="emailoctopus-api-key-disconnect"><?php esc_html_e( 'Disconnect', 'emailoctopus' ); ?></a>
                </td>
                <td class="emailoctopus-api-key-status-container-not-connected"<?php echo $api_key_valid ? ' style="display: none;"' : ''; ?>>
                    <div class="emailoctopus-api-key-status emailoctopus-api-key-not-connected">
                        <?php esc_html_e( 'Not connected', 'emailoctopus' ); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="emailoctopus-api-key-input"><?php esc_html_e( 'API Key', 'emailoctopus' ); ?></label></th>
                <td>
                    <input
                        class="regular-text <?php echo ( $api_key && ! $api_key_valid ) ? 'emailoctopus-invalid' : ''; ?>"
                        type="text"
                        value="<?php echo $api_key ? esc_attr( Utils::mask_api_key( $api_key ) ) : ''; ?>"
                        name="emailoctopus-api-key-input"
                        id="emailoctopus-api-key-input"
                        autocomplete="off"
                        <?php echo empty( $api_key ) ? 'autofocus' : ''; ?>
                    />
                    <p>
                        <?php // Translators: %s: <a> tags for EmailOctopus API documentation page link. ?>
                        <?php printf( __( 'Your %1$sEmailOctopus API key%2$s, used to connect to your account.', 'emailoctopus' ), '<a href="https://emailoctopus.com/api-documentation" target="_blank" rel="noopener">', '</a>' ); ?>
                    </p>
                </td>
            </tr>
        </tbody>
    </table>

    <?php submit_button( esc_html__( 'Save Changes', 'emailoctopus' ), 'primary', 'emailoctopus-settings-save' ); ?>
</form>
