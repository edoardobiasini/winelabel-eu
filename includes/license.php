<?php
/**
 * License stub for WineLabel EU (Lite).
 *
 * The lite version has no license system. This file provides
 * minimal stubs so any stray references don't cause fatal errors.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Always returns false — lite version has no Pro license.
 *
 * @return bool
 */
function wleu_is_pro() {
	return false;
}

/**
 * Get a human-readable license status label.
 *
 * @return string Always "Free".
 */
function wleu_license_status_label() {
	return __( 'Free', 'winelabel-eu' );
}
