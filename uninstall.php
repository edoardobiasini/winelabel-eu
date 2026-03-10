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
$wleu_vintages = get_posts( [
	'post_type'      => 'elabel_vintage',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
] );

foreach ( $wleu_vintages as $wleu_vintage_id ) {
	wp_delete_post( $wleu_vintage_id, true );
}

// Delete wleu_wine posts (standalone mode).
$wleu_wines = get_posts( [
	'post_type'      => 'wleu_wine',
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'fields'         => 'ids',
] );

foreach ( $wleu_wines as $wleu_wine_id ) {
	wp_delete_post( $wleu_wine_id, true );
}

// Delete _elabel_enabled meta from all products/wines.
global $wpdb;
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => '_elabel_enabled' ] );

// Delete all plugin options.
$wleu_options = [
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

foreach ( $wleu_options as $wleu_option ) {
	delete_option( $wleu_option );
}

// Delete transients.
delete_transient( 'wleu_license_status' );

// Clean up temp directory.
if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}
WP_Filesystem();
global $wp_filesystem;

$wleu_upload_dir = wp_upload_dir();
$wleu_tmp_dir    = $wleu_upload_dir['basedir'] . '/winelabel-eu-tmp';
if ( is_dir( $wleu_tmp_dir ) ) {
	$wleu_files = glob( $wleu_tmp_dir . '/*' );
	if ( $wleu_files ) {
		foreach ( $wleu_files as $wleu_file ) {
			if ( is_file( $wleu_file ) ) {
				$wp_filesystem->delete( $wleu_file );
			}
		}
	}
	$wp_filesystem->rmdir( $wleu_tmp_dir );
}

// Flush rewrite rules.
flush_rewrite_rules();
