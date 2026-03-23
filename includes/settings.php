<?php
/**
 * Admin settings page for WineLabel EU (Lite).
 *
 * Top-level menu with 2 tabs: Settings, Usage.
 * Built with WordPress Settings API (no Carbon Fields dependency).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin menu.
 */
add_action( 'admin_menu', function () {
	add_menu_page(
		__( 'WineLabel EU', 'winelabel-eu' ),
		__( 'WineLabel EU', 'winelabel-eu' ),
		'manage_options',
		'winelabel-eu',
		'wleu_render_settings_page',
		'dashicons-clipboard',
		56
	);

	// Add Settings as explicit submenu so it isn't hidden by the Wines CPT.
	add_submenu_page(
		'winelabel-eu',
		__( 'Settings', 'winelabel-eu' ),
		__( 'Settings', 'winelabel-eu' ),
		'manage_options',
		'winelabel-eu',
		'wleu_render_settings_page'
	);
} );

/**
 * Register settings.
 */
add_action( 'admin_init', function () {
	register_setting( 'wleu_settings', 'wleu_use_woocommerce', [
		'type'              => 'string',
		'sanitize_callback' => function ( $val ) {
			$val = sanitize_text_field( $val );
			$val = in_array( $val, [ 'yes', 'no' ], true ) ? $val : 'no';
			// Flush rewrite rules when WC integration changes (CPT registration differs).
			if ( $val !== get_option( 'wleu_use_woocommerce', 'no' ) ) {
				add_action( 'shutdown', 'flush_rewrite_rules' );
			}
			return $val;
		},
		'default'           => 'no',
	] );

	register_setting( 'wleu_settings', 'wleu_delete_data_on_uninstall', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
} );

/**
 * Render the settings page.
 */
function wleu_render_settings_page() {
	$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? 'settings' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab navigation, no data is processed.

	$tabs = [];
	$tabs['settings'] = __( 'Settings', 'winelabel-eu' );
	$tabs['usage']    = __( 'Usage', 'winelabel-eu' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'WineLabel EU', 'winelabel-eu' ); ?></h1>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=winelabel-eu&tab=' . $tab_id ) ); ?>"
				   class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html( $tab_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="tab-content" style="margin-top: 20px;">
			<?php
			switch ( $active_tab ) {
				case 'settings':
					wleu_render_settings_tab();
					break;
				case 'usage':
					wleu_render_usage_tab();
					break;
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Render the Settings tab.
 */
function wleu_render_settings_tab() {
	?>
	<form method="post" action="options.php">
		<?php settings_fields( 'wleu_settings' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php esc_html_e( 'WooCommerce Integration', 'winelabel-eu' ); ?>
				</th>
				<td>
					<?php if ( wleu_is_woocommerce_active() ) : ?>
						<fieldset>
							<label>
								<input type="radio" name="wleu_use_woocommerce" value="no"
									   <?php checked( get_option( 'wleu_use_woocommerce', 'no' ), 'no' ); ?>>
								<?php esc_html_e( 'Use built-in Wines manager (separate from WooCommerce)', 'winelabel-eu' ); ?>
							</label>
							<br>
							<label>
								<input type="radio" name="wleu_use_woocommerce" value="yes"
									   <?php checked( get_option( 'wleu_use_woocommerce', 'no' ), 'yes' ); ?>>
								<?php esc_html_e( 'Attach digital labels to WooCommerce products', 'winelabel-eu' ); ?>
							</label>
						</fieldset>
						<p class="description">
							<?php esc_html_e( 'Choose where to manage your wines and digital labels.', 'winelabel-eu' ); ?>
						</p>
					<?php else : ?>
						<p class="description" style="margin: 0;">
							<?php esc_html_e( 'WooCommerce is not active. WineLabel EU is using its built-in Wines manager.', 'winelabel-eu' ); ?>
							<br>
							<?php esc_html_e( 'Install and activate WooCommerce to attach digital labels directly to your products.', 'winelabel-eu' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Data Removal', 'winelabel-eu' ); ?>
				</th>
				<td>
					<label>
						<input type="checkbox" name="wleu_delete_data_on_uninstall" value="yes"
							   <?php checked( get_option( 'wleu_delete_data_on_uninstall', '' ), 'yes' ); ?>>
						<?php esc_html_e( 'Delete all WineLabel EU data when the plugin is uninstalled', 'winelabel-eu' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'This will remove all vintage posts, product meta, and plugin options.', 'winelabel-eu' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="card" style="max-width: 600px; margin-top: 20px;">
		<h3><?php esc_html_e( 'WineLabel EU Pro', 'winelabel-eu' ); ?></h3>
		<p><?php esc_html_e( 'The Pro version adds powerful features for professional wineries:', 'winelabel-eu' ); ?></p>
		<ul style="list-style: disc; padding-left: 20px;">
			<li><?php esc_html_e( 'Bilingual labels (English + a second language)', 'winelabel-eu' ); ?></li>
			<li><?php esc_html_e( 'Downloadable vector QR code PDFs', 'winelabel-eu' ); ?></li>
			<li><?php esc_html_e( 'Custom base URL for QR codes', 'winelabel-eu' ); ?></li>
			<li><?php esc_html_e( 'Customizable label translations', 'winelabel-eu' ); ?></li>
			<li><?php esc_html_e( 'Remove footer branding', 'winelabel-eu' ); ?></li>
			<li><?php esc_html_e( 'Duplicate vintages with one click', 'winelabel-eu' ); ?></li>
		</ul>
		<p>
			<a href="https://winelabel.net" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'Learn more at winelabel.net', 'winelabel-eu' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Render the Usage tab.
 */
function wleu_render_usage_tab() {
	$count = wleu_vintage_count();
	/* translators: %d: number of published vintages */
	$progress_label = sprintf( __( '%d published vintages', 'winelabel-eu' ), $count );
	?>
	<div class="card" style="max-width: 700px;">
		<h2><?php esc_html_e( 'Vintage Usage', 'winelabel-eu' ); ?></h2>

		<p><strong><?php echo esc_html( $progress_label ); ?></strong></p>
	</div>

	<?php
	// List wines/products with enabled labels.
	$products = get_posts( [
		'post_type'      => wleu_product_post_type(),
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'   => '_elabel_enabled',
				'value' => 'yes',
			],
		],
		'orderby' => 'title',
		'order'   => 'ASC',
	] );

	if ( empty( $products ) ) {
		echo '<p>' . esc_html__( 'No wines with digital labels enabled.', 'winelabel-eu' ) . '</p>';
		return;
	}
	?>
	<table class="widefat fixed striped" style="max-width: 700px; margin-top: 20px;">
		<thead>
			<tr>
				<th style="width:40%"><?php esc_html_e( 'Wine', 'winelabel-eu' ); ?></th>
				<th><?php esc_html_e( 'Vintages', 'winelabel-eu' ); ?></th>
				<th><?php esc_html_e( 'Published', 'winelabel-eu' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $products as $product ) :
				$all_vintages = get_posts( [
					'post_type'      => 'wleu_vintage',
					'post_parent'    => $product->ID,
					'posts_per_page' => -1,
					'post_status'    => 'any',
				] );
				$pub_vintages = array_filter( $all_vintages, function ( $v ) {
					return $v->post_status === 'publish';
				} );
			?>
			<tr>
				<td>
					<a href="<?php echo esc_url( get_edit_post_link( $product->ID ) ); ?>">
						<?php echo esc_html( get_the_title( $product->ID ) ); ?>
					</a>
				</td>
				<td><?php echo count( $all_vintages ); ?></td>
				<td><?php echo count( $pub_vintages ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
