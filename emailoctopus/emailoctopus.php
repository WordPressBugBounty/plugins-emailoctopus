<?php
/**
 * Plugin Name:       EmailOctopus
 * Plugin URI:        https://emailoctopus.com
 * Description:       Use this official plugin to display EmailOctopus subscription forms on your WordPress site.
 * Version:           3.1.6
 * Author:            EmailOctopus
 * Author URI:        https://emailoctopus.com
 * Text Domain:       emailoctopus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * Requires PHP:      7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
{
    exit;
}

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( esc_html__( 'EmailOctopus requires PHP version 7.4 or higher.', 'emailoctopus' ) );
}

define( 'EMAILOCTOPUS_VERSION', '3.1.6' );
define( 'EMAILOCTOPUS_FILE', __FILE__ );
define( 'EMAILOCTOPUS_DIR', plugin_dir_path( EMAILOCTOPUS_FILE ) );
define( 'EMAILOCTOPUS_URL', plugin_dir_url( EMAILOCTOPUS_FILE ) );

$emailoctopus_autoloader = dirname( EMAILOCTOPUS_FILE ) . '/vendor/autoload.php';
$emailoctopus_functions  = dirname( EMAILOCTOPUS_FILE ) . '/functions.php';

if ( ! is_readable( $emailoctopus_autoloader ) )
{
    /* Translators: Placeholder is the current directory. */
    throw new Exception( sprintf( __( 'Please run `composer install` in the plugin folder "%s" and try activating this plugin again.', 'emailoctopus' ), dirname( EMAILOCTOPUS_FILE ) ) );
}

require_once $emailoctopus_autoloader;
require_once $emailoctopus_functions;

$emailoctopus_plugin = \EmailOctopus\Plugin::get_instance();

$emailoctopus_plugin->run();
