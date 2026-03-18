<?php
/**
 * License management for WineLabel EU.
 *
 * Validates Pro licenses via the LemonSqueezy API.
 * License keys are cached for 7 days to avoid repeated API calls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WLEU_LS_API_URL', 'https://api.lemonsqueezy.com/v1/licenses' );

/**
 * Check if the current site has an active Pro license.
 *
 * @return bool
 */
function wleu_is_pro() {
	// Allow a dev/test override via wp-config.php constant.
	if ( defined( 'WLEU_PRO_KEY' ) && WLEU_PRO_KEY ) {
		$key = get_option( 'wleu_license_key', '' );
		if ( $key === WLEU_PRO_KEY ) {
			return true;
		}
	}

	$key = get_option( 'wleu_license_key', '' );
	if ( empty( $key ) ) {
		return false;
	}

	// Check cached validation status (7-day transient).
	$status = get_transient( 'wleu_license_status' );
	if ( $status === 'valid' ) {
		return true;
	}
	if ( $status === 'invalid' ) {
		return false;
	}

	// No cache — re-validate.
	return wleu_validate_license( $key );
}

/**
 * Validate a license key against LemonSqueezy.
 *
 * @param string $key License key to validate.
 * @return bool Whether the key is valid.
 */
function wleu_validate_license( $key ) {
	if ( empty( $key ) ) {
		delete_transient( 'wleu_license_status' );
		return false;
	}

	$instance_id = wleu_instance_id();

	// Call LemonSqueezy /licenses/validate endpoint.
	$response = wp_remote_post( WLEU_LS_API_URL . '/validate', [
		'timeout' => 15,
		'headers' => [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded',
		],
		'body'    => [
			'license_key' => $key,
			'instance_id' => $instance_id,
		],
	] );

	// Network error — assume valid if previously validated (grace period).
	if ( is_wp_error( $response ) ) {
		wleu_license_debug( 'validate', [ 'error' => $response->get_error_message() ] );
		$previous = get_transient( 'wleu_license_status' );
		if ( $previous === false ) {
			// Never validated before, can't assume valid.
			set_transient( 'wleu_license_status', 'invalid', DAY_IN_SECONDS );
			return false;
		}
		// Extend grace period.
		set_transient( 'wleu_license_status', 'valid', 2 * DAY_IN_SECONDS );
		return true;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	wleu_license_debug( 'validate', $body );

	if ( ! empty( $body['valid'] ) ) {
		set_transient( 'wleu_license_status', 'valid', 7 * DAY_IN_SECONDS );
		update_option( 'wleu_license_instance', $body['instance']['id'] ?? $instance_id );
		return true;
	}

	// Key exists but not yet activated for this instance — try activation.
	if ( isset( $body['error'] ) && stripos( $body['error'], 'instance' ) !== false ) {
		return wleu_activate_license_instance( $key, $instance_id );
	}

	set_transient( 'wleu_license_status', 'invalid', DAY_IN_SECONDS );
	return false;
}

/**
 * Activate a license key for this site instance.
 *
 * @param string $key         License key.
 * @param string $instance_id Instance identifier.
 * @return bool
 */
function wleu_activate_license_instance( $key, $instance_id ) {
	$response = wp_remote_post( WLEU_LS_API_URL . '/activate', [
		'timeout' => 15,
		'headers' => [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/x-www-form-urlencoded',
		],
		'body'    => [
			'license_key'   => $key,
			'instance_name' => wleu_instance_name(),
		],
	] );

	if ( is_wp_error( $response ) ) {
		wleu_license_debug( 'activate', [ 'error' => $response->get_error_message() ] );
		set_transient( 'wleu_license_status', 'invalid', DAY_IN_SECONDS );
		return false;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	wleu_license_debug( 'activate', $body );

	if ( ! empty( $body['activated'] ) ) {
		$new_instance = $body['instance']['id'] ?? $instance_id;
		update_option( 'wleu_license_instance', $new_instance );
		set_transient( 'wleu_license_status', 'valid', 7 * DAY_IN_SECONDS );
		return true;
	}

	set_transient( 'wleu_license_status', 'invalid', DAY_IN_SECONDS );
	return false;
}

/**
 * Deactivate the current license.
 */
function wleu_deactivate_license() {
	$key         = get_option( 'wleu_license_key', '' );
	$instance_id = get_option( 'wleu_license_instance', '' );

	// Call LemonSqueezy deactivate endpoint.
	if ( ! empty( $key ) && ! empty( $instance_id ) ) {
		wp_remote_post( WLEU_LS_API_URL . '/deactivate', [
			'timeout' => 15,
			'body'    => [
				'license_key' => $key,
				'instance_id' => $instance_id,
			],
		] );
	}

	delete_option( 'wleu_license_key' );
	delete_option( 'wleu_license_instance' );
	delete_transient( 'wleu_license_status' );
}

/**
 * Get a human-readable license status label.
 *
 * @return string "Free" or "Pro".
 */
function wleu_license_status_label() {
	return wleu_is_pro() ? __( 'Pro', 'winelabel-eu' ) : __( 'Free', 'winelabel-eu' );
}

/**
 * Get a unique instance ID for this site.
 *
 * @return string
 */
function wleu_instance_id() {
	$instance = get_option( 'wleu_license_instance', '' );
	if ( ! empty( $instance ) ) {
		return $instance;
	}
	return wp_hash( home_url() );
}

/**
 * Get a human-readable instance name for LemonSqueezy.
 *
 * @return string
 */
function wleu_instance_name() {
	$url = wp_parse_url( home_url() );
	return $url['host'] ?? home_url();
}

/**
 * Log license API responses when WP_DEBUG is enabled.
 *
 * @param string $action  The API action (validate, activate).
 * @param mixed  $data    Response body or error data.
 */
function wleu_license_debug( $action, $data ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-only logging gated behind WP_DEBUG.
			'[WineLabel EU] License %s: %s',
			$action,
			wp_json_encode( $data )
		) );
	}
}

/**
 * Show admin notice when license is invalid/expired (full version only).
 */
add_action( 'admin_notices', function () {
	if ( ! wleu_is_full_version() ) {
		return;
	}
	$key = get_option( 'wleu_license_key', '' );
	if ( empty( $key ) ) {
		return;
	}

	$status = get_transient( 'wleu_license_status' );
	if ( $status === 'invalid' ) {
		$screen = get_current_screen();
		// Only show on WineLabel EU pages.
		if ( $screen && ( $screen->id === 'toplevel_page_winelabel-eu' || $screen->post_type === 'elabel_vintage' || $screen->post_type === wleu_product_post_type() ) ) {
			printf(
				'<div class="notice notice-error"><p><strong>WineLabel EU:</strong> %s <a href="%s">%s</a></p></div>',
				esc_html__( 'Your license key is invalid or expired.', 'winelabel-eu' ),
				esc_url( admin_url( 'admin.php?page=winelabel-eu&tab=license' ) ),
				esc_html__( 'Check your license', 'winelabel-eu' )
			);
		}
	}
} );
