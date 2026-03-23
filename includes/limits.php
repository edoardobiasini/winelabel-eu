<?php
/**
 * Vintage helpers and soft Pro upsell notice for WineLabel EU (Lite).
 *
 * No usage limits — all built-in features are fully functional
 * per WordPress.org Plugin Directory guidelines.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Threshold after which a soft informational notice is shown.
 * Does NOT block or limit any functionality.
 */
define( 'WLEU_SOFT_NOTICE_THRESHOLD', 5 );

/**
 * Get the count of published vintages.
 *
 * @return int
 */
function wleu_vintage_count() {
	$counts = wp_count_posts( 'wleu_vintage' );
	return (int) ( $counts->publish ?? 0 );
}

/**
 * Check if a new vintage can be published.
 *
 * Always true in the free version — no limits.
 *
 * @return bool
 */
function wleu_can_publish_vintage() {
	return true;
}

/**
 * Show a soft informational notice when the user has more than 5 published vintages.
 * Purely informational — does not block or restrict any functionality.
 */
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}

	$relevant = in_array( $screen->post_type, [ 'wleu_vintage', wleu_product_post_type() ], true );
	if ( ! $relevant ) {
		return;
	}

	if ( wleu_vintage_count() < WLEU_SOFT_NOTICE_THRESHOLD ) {
		return;
	}

	// Allow the user to dismiss the notice for 30 days.
	$dismissed = get_user_meta( get_current_user_id(), 'wleu_pro_notice_dismissed', true );
	if ( $dismissed && time() < (int) $dismissed ) {
		return;
	}

	$dismiss_url = wp_nonce_url(
		add_query_arg( 'wleu_dismiss_pro_notice', '1' ),
		'wleu_dismiss_pro_notice'
	);
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<?php
			printf(
				/* translators: %1$d: number of published vintages, %2$s: opening link tag, %3$s: closing link tag */
				esc_html__( 'You have %1$d published vintages. Need bilingual labels, downloadable QR code PDFs, custom branding, and more? %2$sCheck out WineLabel EU Pro%3$s.', 'winelabel-eu' ),
				wleu_vintage_count(),
				'<a href="https://winelabel.net" target="_blank">',
				'</a>'
			);
			?>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-left: 12px; font-size: 12px; color: #999;">
				<?php esc_html_e( 'Don\'t show again', 'winelabel-eu' ); ?>
			</a>
		</p>
	</div>
	<?php
} );

/**
 * Handle the "Don't show again" dismissal.
 */
add_action( 'admin_init', function () {
	if ( ! isset( $_GET['wleu_dismiss_pro_notice'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wleu_dismiss_pro_notice' ) ) {
		return;
	}
	// Dismiss for 30 days.
	update_user_meta( get_current_user_id(), 'wleu_pro_notice_dismissed', time() + ( 30 * DAY_IN_SECONDS ) );
	wp_safe_redirect( remove_query_arg( [ 'wleu_dismiss_pro_notice', '_wpnonce' ] ) );
	exit;
} );
