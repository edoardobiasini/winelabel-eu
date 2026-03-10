<?php
/**
 * Free tier limits for WineLabel EU.
 *
 * Free users can publish up to 5 vintages. Pro users have no limit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maximum number of published vintages allowed on the Free tier.
 */
define( 'WLEU_FREE_VINTAGE_LIMIT', 5 );

/**
 * Get the count of published vintages.
 *
 * @return int
 */
function wleu_vintage_count() {
	$counts = wp_count_posts( 'elabel_vintage' );
	return (int) ( $counts->publish ?? 0 );
}

/**
 * Check if a new vintage can be published.
 *
 * @return bool
 */
function wleu_can_publish_vintage() {
	if ( wleu_is_pro() ) {
		return true;
	}
	return wleu_vintage_count() < WLEU_FREE_VINTAGE_LIMIT;
}

/**
 * Prevent publishing vintages beyond the free limit.
 */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( $data['post_type'] !== 'elabel_vintage' ) {
		return $data;
	}

	// Only check when transitioning to publish.
	if ( $data['post_status'] !== 'publish' ) {
		return $data;
	}

	// If editing an already-published vintage, allow it.
	if ( ! empty( $postarr['ID'] ) ) {
		$existing = get_post( $postarr['ID'] );
		if ( $existing && $existing->post_status === 'publish' ) {
			return $data;
		}
	}

	// Block publish if at limit.
	if ( ! wleu_can_publish_vintage() ) {
		$data['post_status'] = 'draft';
	}

	return $data;
}, 20, 2 );

/**
 * Show admin notices for vintage limits.
 */
add_action( 'admin_notices', function () {
	if ( wleu_is_pro() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}

	// Show on vintage edit screens and product edit screens.
	$relevant = in_array( $screen->post_type, [ 'elabel_vintage', wleu_product_post_type() ], true );
	if ( ! $relevant ) {
		return;
	}

	$count = wleu_vintage_count();
	$limit = WLEU_FREE_VINTAGE_LIMIT;

	if ( $count >= $limit ) {
		?>
		<div class="notice notice-error">
			<p>
				<strong>WineLabel EU:</strong>
				<?php
				echo esc_html( sprintf(
					/* translators: %1$d: current count, %2$d: limit */
					__( 'You have reached the limit of %1$d/%2$d published vintages on the Free plan. Upgrade to Pro for unlimited vintages.', 'winelabel-eu' ),
					$count,
					$limit
				) );
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=winelabel-eu&tab=license' ) ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'winelabel-eu' ); ?></a>
			</p>
		</div>
		<?php
	} elseif ( $count >= $limit - 1 ) {
		?>
		<div class="notice notice-warning">
			<p>
				<strong>WineLabel EU:</strong>
				<?php
				echo esc_html( sprintf(
					/* translators: %1$d: current count, %2$d: limit */
					__( 'You have %1$d/%2$d published vintages on the Free plan. Upgrade to Pro for unlimited vintages.', 'winelabel-eu' ),
					$count,
					$limit
				) );
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=winelabel-eu&tab=license' ) ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'winelabel-eu' ); ?></a>
			</p>
		</div>
		<?php
	}
} );
