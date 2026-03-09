<?php
/**
 * License management for WineLabel EU.
 *
 * Provides the wleu_is_pro() check and license validation stubs.
 * Phase 2 will integrate LemonSqueezy API for real validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the current site has an active Pro license.
 *
 * @return bool
 */
function wleu_is_pro() {
	$key = get_option( 'wleu_license_key', '' );
	if ( empty( $key ) ) {
		return false;
	}

	// Check cached validation status (7-day transient).
	$status = get_transient( 'wleu_license_status' );
	if ( $status === 'valid' ) {
		return true;
	}

	// If no cached status, re-validate.
	if ( $status === false ) {
		$valid = wleu_validate_license( $key );
		return $valid;
	}

	return false;
}

/**
 * Validate a license key.
 *
 * Phase 1: Returns true if key is non-empty (stub).
 * Phase 2: Will call LemonSqueezy API for real validation.
 *
 * @param string $key License key to validate.
 * @return bool Whether the key is valid.
 */
function wleu_validate_license( $key ) {
	if ( empty( $key ) ) {
		delete_transient( 'wleu_license_status' );
		return false;
	}

	// TODO Phase 2: Replace with LemonSqueezy API call.
	// For now, any non-empty key is considered valid.
	$valid = true;

	if ( $valid ) {
		set_transient( 'wleu_license_status', 'valid', 7 * DAY_IN_SECONDS );
	} else {
		set_transient( 'wleu_license_status', 'invalid', DAY_IN_SECONDS );
	}

	return $valid;
}

/**
 * Deactivate the current license.
 */
function wleu_deactivate_license() {
	delete_option( 'wleu_license_key' );
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
