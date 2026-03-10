<?php
/**
 * WineLabel EU uninstall script.
 *
 * Only runs if the user has opted in via the "Delete all data on uninstall" setting.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if the user has opted in.
if ( get_option( 'wleu_delete_data_on_uninstall' ) !== 'yes' ) {
	return;
}

// Delete all elabel_vintage posts and their meta.
$vintages = get_posts( [
	'post_type'      => 'elabel_vintage',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
] );

foreach ( $vintages as $vintage_id ) {
	wp_delete_post( $vintage_id, true );
}

// Delete wleu_wine posts (standalone mode).
$wines = get_posts( [
	'post_type'      => 'wleu_wine',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
] );

foreach ( $wines as $wine_id ) {
	wp_delete_post( $wine_id, true );
}

// Delete _elabel_enabled meta from all products/wines.
global $wpdb;
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_elabel_enabled' ] );

// Delete all plugin options.
$options = [
	'wleu_license_key',
	'wleu_version',
	'wleu_base_url',
	'wleu_default_language',
	'wleu_show_footer_credit',
	'wleu_second_language_enabled',
	'wleu_second_language_code',
	'wleu_index_slug',
	'wleu_custom_strings',
	'wleu_license_instance',
	'wleu_use_woocommerce',
	'wleu_delete_data_on_uninstall',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete transients.
delete_transient( 'wleu_license_status' );

// Clean up temp directory.
$upload_dir = wp_upload_dir();
$tmp_dir    = $upload_dir['basedir'] . '/winelabel-eu-tmp';
if ( is_dir( $tmp_dir ) ) {
	$files = glob( $tmp_dir . '/*' );
	if ( $files ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}
	rmdir( $tmp_dir );
}

// Flush rewrite rules.
flush_rewrite_rules();
