<?php
/**
 * Uninstall script for EmailOctopus.
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
{
    exit;
}

global $wpdb;

// Delete options
$plugin_options = $wpdb->get_results(
    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'emailoctopus_%' OR option_name LIKE 'widget_emailoctopus_%'"
);
foreach( $plugin_options as $option ) {
    delete_option( $option->option_name );
}

// Delete custom tables
$tables = [
    $wpdb->prefix . 'emailoctopus_forms',
    $wpdb->prefix . 'emailoctopus_forms_meta',
    $wpdb->prefix . 'emailoctopus_custom_fields',
];
foreach ( $tables as $table )
{
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete cache (should match logic in `Utils::clear_transients()`)
$transient_options = $wpdb->get_results(
    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_emailoctopus_%'"
);
foreach( $transient_options as $option ) {
    // Remove `_transient_` from the beginning of the string to
    // determine the transient name
    $transient_name = preg_replace('/^_transient_/', '', $option->option_name);
    delete_transient( $transient_name );
}
