<?php
/**
 * EmailOctopus admin page: Forms.
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

$api_refresh_url = add_query_arg(
    [
        'emailoctopus_api_refresh' => wp_create_nonce( 'emailoctopus-api-refresh' )
    ],
    admin_url( 'admin.php?page=emailoctopus-forms' )
);

wp_enqueue_script( 'emailoctopus_page_forms' );

wp_nonce_field( 'emailoctopus_load_forms', '_eo_nonce' );

?>

<?php
$api_refresh_status = get_transient('emailoctopus_api_refresh_status' );
if ( $api_refresh_status === '-1' ) :
?>
    <div class="emailoctopus-notice notice notice-error is-dismissible">
        <p>
            <?php
            echo __(
                sprintf(
                    'Could not refresh data. Please <a href="%s">try again</a>.',
                    esc_url( $api_refresh_url )
                ),
                'emailoctopus'
            );
            ?>
        </p>
    </div>
<?php
endif;
?>

<div class="wrap">

    <?php if ( empty( $api_key ) || $api_key_valid === false ) : ?>

        <?php ob_start(); ?>
            <?php include_once( 'component-welcome.php' ); ?>
        <?php echo ob_get_clean(); ?>

    <?php else : ?>

        <?php if ( isset( $_GET['refresh_api'] ) && wp_verify_nonce( $_GET['refresh_api'], 'emailoctopus-refresh-api' ) ) : ?>
            <?php delete_transient( 'emailoctopus_load_forms' ); ?>
        <?php endif; ?>

        <h1 class="wp-heading-inline emailoctopus__logo">
            <img src="<?php echo esc_url( $utils->get_icon_url() ); ?>"
                alt="<?php esc_attr_e( 'EmailOctopus logo', 'emailoctopus' ); ?>"
                height="20"
            >
            <?php echo get_admin_page_title(); ?>
        </h1>

        <a class="page-title-action" href="https://emailoctopus.com/forms/embedded/list" target="_blank" rel="noopener"><?php esc_html_e( 'Add New', 'emailoctopus' ); ?></a>

        <a class="page-title-action emailoctopus-forms-refresh" href="<?php echo esc_url( $api_refresh_url ); ?>"><?php esc_html_e( 'Refresh Data', 'emailoctopus' ); ?></a>

        <hr class="wp-header-end"/>

        <br>

        <table id="emailoctopus-forms" class="wp-list-table widefat table-view-list striped loading">
            <thead>
                <tr>
                    <th class="row-title"><?php esc_attr_e( 'Form', 'emailoctopus' ); ?></th>
                    <th class="row-title"><?php esc_attr_e( 'List', 'emailoctopus' ); ?></th>
                    <th class="row-title"><?php esc_attr_e( 'Display settings', 'emailoctopus' ); ?></th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td class="emailoctopus-forms-table-message" colspan="4">
                        <div class="emailoctopus-forms-loading">
                            <span class="spinner is-active" title=""></span>
                            <p>
                                <?php esc_attr_e($api_refresh_status === '1' ? 'Refreshing…' : 'Loading…', 'emailoctopus' ); ?>
                            </p>
                        </div>

                        <div class="emailoctopus-forms-none" style="display: none;">
                            <p>
                                <?php
                                    echo __(
                                        'You don\'t have any forms yet. <a href="https://emailoctopus.com/forms/embedded/list" target="_blank" rel="nofollow">Create one</a>.',
                                        'emailoctopus'
                                    );
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr>
                    <th class="row-title"><?php esc_attr_e( 'Form', 'emailoctopus' ); ?></th>
                    <th class="row-title"><?php esc_attr_e( 'List', 'emailoctopus' ); ?></th>
                    <th class="row-title"><?php esc_attr_e( 'Display settings', 'emailoctopus' ); ?></th>
                </tr>
            </tfoot>
        </table>

        <script>
        window.jQuery( window ).on( 'load', function () {
            window.jQuery( window ).trigger( 'emailoctopus-load-forms' );
        } );
        </script>

    <?php endif; ?>

</div>
