<?php
/**
 * Plugin Name: WineLabel EU
 * Plugin URI: https://winelabel.net
 * Description: EU Digital Wine Label — regulation-compliant digital labels (Reg. EU 2021/2117) with ingredients, nutritional values, and waste sorting. Works with or without WooCommerce.
 * Version: 1.0.1
 * Author: Edoardo Biasini
 * Author URI: https://winelabel.net
 * Text Domain: winelabel-eu
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WLEU_PATH', plugin_dir_path( __FILE__ ) );
define( 'WLEU_URL', plugin_dir_url( __FILE__ ) );
define( 'WLEU_VERSION', '1.0.1' );

/**
 * Load Composer autoloader.
 *
 * Full version: Carbon Fields + QR code + DomPDF + update checker.
 * Lite version (WordPress.org): Carbon Fields only.
 */
if ( file_exists( WLEU_PATH . 'vendor/autoload.php' ) ) {
	require_once WLEU_PATH . 'vendor/autoload.php';
}

/**
 * Check if the full version (with all Composer deps) is available.
 *
 * @return bool
 */
function wleu_is_full_version() {
	return class_exists( 'Dompdf\\Dompdf' );
}

/**
 * Boot Carbon Fields.
 */
add_action( 'after_setup_theme', function () {
	\Carbon_Fields\Carbon_Fields::boot();
} );

/**
 * Load plugin modules.
 */
require_once WLEU_PATH . 'includes/license.php';
require_once WLEU_PATH . 'includes/limits.php';
require_once WLEU_PATH . 'includes/settings.php';
require_once WLEU_PATH . 'includes/template.php';
require_once WLEU_PATH . 'includes/qr-pdf.php';

require_once WLEU_PATH . 'includes/fields.php';

// ── Auto-Updates (GitHub) ────────────────────────────────────

if ( class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
	$wleu_updater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/edoardobiasini/winelabel-eu/',
		__FILE__,
		'winelabel-eu'
	);
	// Use releases for stable versions.
	$wleu_updater->getVcsApi()->enableReleaseAssets();
}

// ── WooCommerce Soft Dependency ──────────────────────────────

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function wleu_is_woocommerce_active() {
	return class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', (array) get_option( 'active_plugins', [] ), true );
}

/**
 * Check if the plugin should use WooCommerce products.
 *
 * Returns true only when WooCommerce is active AND the user has opted in
 * (or hasn't explicitly opted out — defaults to 'yes' for backward compat).
 *
 * @return bool
 */
function wleu_uses_woocommerce() {
	return wleu_is_woocommerce_active() && get_option( 'wleu_use_woocommerce', 'no' ) === 'yes';
}

/**
 * Get the post type used for wines/products.
 *
 * Returns 'product' when WooCommerce integration is enabled, 'wleu_wine' otherwise.
 *
 * @return string
 */
function wleu_product_post_type() {
	return wleu_uses_woocommerce() ? 'product' : 'wleu_wine';
}

// ── Custom Post Type: elabel_vintage ─────────────────────────

/**
 * Register the elabel_vintage CPT and, when WooCommerce is absent, the wleu_wine CPT.
 */
add_action( 'init', function () {
	register_post_type( 'elabel_vintage', [
		'labels' => [
			'name'               => __( 'Vintages', 'winelabel-eu' ),
			'singular_name'      => __( 'Vintage', 'winelabel-eu' ),
			'add_new'            => __( 'Add Vintage', 'winelabel-eu' ),
			'add_new_item'       => __( 'Add New Vintage', 'winelabel-eu' ),
			'edit_item'          => __( 'Edit Vintage', 'winelabel-eu' ),
			'new_item'           => __( 'New Vintage', 'winelabel-eu' ),
			'view_item'          => __( 'View Vintage', 'winelabel-eu' ),
			'search_items'       => __( 'Search Vintages', 'winelabel-eu' ),
			'not_found'          => __( 'No vintages found.', 'winelabel-eu' ),
			'not_found_in_trash' => __( 'No vintages found in Trash.', 'winelabel-eu' ),
		],
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => false,
		'supports'     => false,
		'has_archive'  => false,
		'rewrite'      => false,
	] );

	// Standalone wine CPT — when not using WooCommerce integration.
	if ( ! wleu_uses_woocommerce() ) {
		register_post_type( 'wleu_wine', [
			'labels' => [
				'name'               => __( 'Wines', 'winelabel-eu' ),
				'singular_name'      => __( 'Wine', 'winelabel-eu' ),
				'add_new'            => __( 'Add Wine', 'winelabel-eu' ),
				'add_new_item'       => __( 'Add New Wine', 'winelabel-eu' ),
				'edit_item'          => __( 'Edit Wine', 'winelabel-eu' ),
				'new_item'           => __( 'New Wine', 'winelabel-eu' ),
				'view_item'          => __( 'View Wine', 'winelabel-eu' ),
				'search_items'       => __( 'Search Wines', 'winelabel-eu' ),
				'not_found'          => __( 'No wines found.', 'winelabel-eu' ),
				'not_found_in_trash' => __( 'No wines found in Trash.', 'winelabel-eu' ),
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'winelabel-eu',
			'supports'            => [ 'title' ],
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-clipboard',
		] );
	}
} );

/**
 * Auto-set post_parent from URL parameter when creating a new vintage.
 */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( $data['post_type'] !== 'elabel_vintage' ) {
		return $data;
	}

	// Set post_parent from URL parameter on new posts.
	if ( empty( $postarr['ID'] ) && ! empty( $_GET['parent_product'] ) ) {
		$data['post_parent'] = absint( $_GET['parent_product'] );
	}

	return $data;
}, 10, 2 );

/**
 * Auto-generate vintage post title after Carbon Fields saves its values.
 */
add_action( 'carbon_fields_post_meta_container_saved', function ( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'elabel_vintage' ) {
		return;
	}

	$annata     = carbon_get_post_meta( $post_id, 'elabel_annata' );
	$product_id = $post->post_parent;

	if ( ! $product_id || ! $annata ) {
		return;
	}

	$new_title = get_the_title( $product_id ) . ' — ' . $annata;

	if ( $post->post_title !== $new_title ) {
		wp_update_post( [
			'ID'         => $post_id,
			'post_title' => $new_title,
		] );
	}
} );

// ── Vintage List: Injected into Carbon Fields Container ──────

/**
 * Inject the vintage list into the Carbon Fields container
 * via an admin footer script that moves the content into place.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'wleu_vintages',
		__( 'Vintages', 'winelabel-eu' ),
		'wleu_vintages_metabox',
		wleu_product_post_type(),
		'normal',
		'default'
	);
} );

/**
 * Move the vintage list into the Carbon Fields container via inline JS.
 */
add_action( 'admin_footer', function () {
	$screen = get_current_screen();
	if ( ! $screen || $screen->post_type !== wleu_product_post_type() ) {
		return;
	}
	?>
	<script>
	jQuery(function($) {
		var $cf = $('#carbon_fields_container_winelabel_eu .fields-container, #carbon_fields_container_winelabel_eu .inside');
		var $vintage = $('#wleu_vintages');
		if ($cf.length && $vintage.length) {
			var $content = $vintage.find('.inside').children();
			var $wrapper = $('<div class="elabel-vintages-inline"></div>').append($content);
			$cf.first().append($wrapper);
			$vintage.remove();
		}
	});
	</script>
	<?php
} );

/**
 * Render the vintages list (will be moved into Carbon Fields container via JS).
 *
 * @param WP_Post $post The product post object.
 */
function wleu_vintages_metabox( $post ) {
	// Wine must be saved (at least as draft) before vintages can be added.
	if ( $post->post_status === 'auto-draft' ) {
		?>
		<p style="color: #666; padding: 8px 16px;">
			<?php esc_html_e( 'Save this wine first (as Draft or Published) to start adding vintages.', 'winelabel-eu' ); ?>
		</p>
		<?php
		return;
	}

	$vintages = get_posts( [
		'post_type'      => 'elabel_vintage',
		'post_parent'    => $post->ID,
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'orderby'        => 'meta_value',
		'meta_key'       => '_elabel_annata',
		'order'          => 'ASC',
	] );

	$add_url  = admin_url( 'post-new.php?post_type=elabel_vintage&parent_product=' . $post->ID );
	$at_limit = ! wleu_can_publish_vintage();

	// Show the e-label URL if the label is enabled and there are published vintages.
	$elabel_enabled  = carbon_get_post_meta( $post->ID, 'elabel_enabled' );
	$has_published   = false;
	foreach ( $vintages as $v ) {
		if ( $v->post_status === 'publish' ) {
			$has_published = true;
			break;
		}
	}
	if ( $elabel_enabled && $has_published ) {
		$label_url = home_url( '/' . $post->post_name . '-winelabel/' );
		printf(
			'<p style="margin-bottom: 12px;"><strong>%s:</strong> <a href="%s" target="_blank">%s</a></p>',
			esc_html__( 'Label URL', 'winelabel-eu' ),
			esc_url( $label_url ),
			esc_html( $label_url )
		);
	}
	?>
	<style>
		.elabel-vintages-inline {
			border-top: 1px solid #e0e0e0;
			padding: 16px 16px 4px;
			margin-top: 12px;
		}
		.elabel-vintages-inline h3 {
			margin: 0 0 10px;
			font-size: 14px;
			font-weight: 600;
		}
		.elabel-vintage-table { width: 100%; border-collapse: collapse; }
		.elabel-vintage-table th,
		.elabel-vintage-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
		.elabel-vintage-table th { font-weight: 600; font-size: 13px; color: #555; }
		.elabel-vintage-table .elabel-actions { font-size: 12px; }
		.elabel-vintage-table .elabel-actions a { text-decoration: none; }
		.elabel-vintage-table .elabel-actions .sep { color: #ccc; padding: 0 4px; }
		.elabel-vintage-table .elabel-actions .delete a { color: #b32d2e; }
		.elabel-vintage-table .elabel-actions .delete a:hover { color: #a02020; }
		.elabel-vintage-add { margin-top: 12px; }
	</style>

	<h3><?php esc_html_e( 'Vintages', 'winelabel-eu' ); ?></h3>

	<?php if ( ! empty( $vintages ) ) : ?>
		<table class="elabel-vintage-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Year', 'winelabel-eu' ); ?></th>
					<th><?php esc_html_e( 'Title', 'winelabel-eu' ); ?></th>
					<th><?php esc_html_e( 'Status', 'winelabel-eu' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'winelabel-eu' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $vintages as $vintage ) :
					$annata   = carbon_get_post_meta( $vintage->ID, 'elabel_annata' );
					$edit_url = get_edit_post_link( $vintage->ID );
					$status   = get_post_status_object( $vintage->post_status );
				?>
				<tr>
					<td><strong><?php echo esc_html( $annata ?: '—' ); ?></strong></td>
					<td><?php echo esc_html( $vintage->post_title ); ?></td>
					<td><?php echo esc_html( $status->label ?? $vintage->post_status ); ?></td>
					<td class="elabel-actions">
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'winelabel-eu' ); ?></a>
						<?php if ( wleu_is_pro() ) : ?>
							<?php
							$dup_url = wp_nonce_url(
								admin_url( 'admin.php?action=wleu_duplicate_vintage&post=' . $vintage->ID ),
								'wleu_duplicate_vintage_' . $vintage->ID
							);
							?>
							<span class="sep">|</span>
							<a href="<?php echo esc_url( $dup_url ); ?>"><?php esc_html_e( 'Duplicate', 'winelabel-eu' ); ?></a>
						<?php endif; ?>
						<?php
						$del_url = wp_nonce_url(
							admin_url( 'admin.php?action=wleu_delete_vintage&post=' . $vintage->ID ),
							'wleu_delete_vintage_' . $vintage->ID
						);
						?>
						<span class="sep">|</span>
						<span class="delete"><a href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this vintage?', 'winelabel-eu' ) ); ?>');"><?php esc_html_e( 'Delete', 'winelabel-eu' ); ?></a></span>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No vintages created. Click the button to add one.', 'winelabel-eu' ); ?></p>
	<?php endif; ?>

	<p class="elabel-vintage-add">
		<?php if ( $at_limit ) : ?>
			<button class="button button-primary" disabled title="<?php esc_attr_e( 'Vintage limit reached. Upgrade to Pro for unlimited vintages.', 'winelabel-eu' ); ?>">
				<?php esc_html_e( '+ Add Vintage', 'winelabel-eu' ); ?>
			</button>
		<?php else : ?>
			<a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary">
				<?php esc_html_e( '+ Add Vintage', 'winelabel-eu' ); ?>
			</a>
		<?php endif; ?>
	</p>
	<?php
}

// ── Duplicate Vintage Action (Pro only) ──────────────────────

/**
 * Handle the "Duplicate Vintage" admin action.
 */
add_action( 'admin_action_wleu_duplicate_vintage', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'winelabel-eu' ) );
	}

	if ( ! wleu_is_pro() ) {
		wp_die( esc_html__( 'This feature requires WineLabel EU Pro.', 'winelabel-eu' ) );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint() handles sanitization.
	$post_id = absint( wp_unslash( $_GET['post'] ?? 0 ) );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_text_field handles sanitization.
	if ( ! $post_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wleu_duplicate_vintage_' . $post_id ) ) {
		wp_die( esc_html__( 'Unauthorized action.', 'winelabel-eu' ) );
	}

	$source = get_post( $post_id );
	if ( ! $source || $source->post_type !== 'elabel_vintage' ) {
		wp_die( esc_html__( 'Vintage not found.', 'winelabel-eu' ) );
	}

	$new_id = wp_insert_post( [
		'post_type'   => 'elabel_vintage',
		'post_status' => 'draft',
		'post_title'  => $source->post_title . ' (copy)',
		'post_parent' => $source->post_parent,
	] );

	if ( is_wp_error( $new_id ) ) {
		wp_die( esc_html( $new_id->get_error_message() ) );
	}

	// Copy all meta from source to new post.
	$meta = get_post_meta( $source->ID );
	foreach ( $meta as $key => $values ) {
		if ( in_array( $key, [ '_edit_lock', '_edit_last' ], true ) ) {
			continue;
		}
		foreach ( $values as $value ) {
			add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
		}
	}

	wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
	exit;
} );

// ── Delete Vintage Action ────────────────────────────────────

/**
 * Handle the "Delete Vintage" admin action.
 */
add_action( 'admin_action_wleu_delete_vintage', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'winelabel-eu' ) );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint() handles sanitization.
	$post_id = absint( wp_unslash( $_GET['post'] ?? 0 ) );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_text_field handles sanitization.
	if ( ! $post_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wleu_delete_vintage_' . $post_id ) ) {
		wp_die( esc_html__( 'Unauthorized action.', 'winelabel-eu' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== 'elabel_vintage' ) {
		wp_die( esc_html__( 'Vintage not found.', 'winelabel-eu' ) );
	}

	$product_id = $post->post_parent;
	wp_delete_post( $post_id, true );

	wp_safe_redirect( get_edit_post_link( $product_id, 'raw' ) );
	exit;
} );

/**
 * Add a "Back to product" link on the vintage edit screen.
 */
add_action( 'edit_form_top', function ( $post ) {
	if ( $post->post_type !== 'elabel_vintage' || ! $post->post_parent ) {
		return;
	}

	$product_url   = get_edit_post_link( $post->post_parent );
	$product_title = get_the_title( $post->post_parent );
	printf(
		'<div class="notice notice-info" style="padding:10px;margin:10px 0;"><strong>%s:</strong> <a href="%s">%s</a></div>',
		esc_html( wleu_uses_woocommerce() ? __( 'Product', 'winelabel-eu' ) : __( 'Wine', 'winelabel-eu' ) ),
		esc_url( $product_url ),
		esc_html( $product_title )
	);
} );

/**
 * Get the index page slug.
 *
 * @return string
 */
function wleu_index_slug() {
	return get_option( 'wleu_index_slug', 'winelabel' );
}

// ── Elabel Request Detection ─────────────────────────────────

/**
 * Check if the current request is an elabel page.
 *
 * @return bool
 */
function wleu_is_elabel_request() {
	$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
	$uri = trim( $uri, '/' );
	// Strip query string.
	$uri = strtok( $uri, '?' );
	$index_slug = wleu_index_slug();
	return (bool) preg_match( '/^(.+)-winelabel(?:-\d{2})?\/?$/', $uri )
		|| $uri === $index_slug;
}

/**
 * Disable cookie consent and other tracking plugins on elabel pages.
 * Fires early to prevent cookie-setting behaviour.
 */
add_action( 'plugins_loaded', function () {
	if ( ! wleu_is_elabel_request() ) {
		return;
	}

	// Prevent cookie-consent plugins from loading.
	remove_all_actions( 'wp_enqueue_scripts', 10 );

	// Prevent WP from sending cache/cookie headers on these pages.
	add_action( 'send_headers', function () {
		header_remove( 'Set-Cookie' );
		header( 'Cache-Control: public, max-age=86400' );
		header( 'X-Robots-Tag: noindex, nofollow' );
	}, 999 );
}, 1 );

// ── Rewrite Rules ────────────────────────────────────────────

/**
 * Register rewrite rules.
 */
add_action( 'init', function () {
	// Vintage-specific label: {product-slug}-winelabel-{YY}/
	add_rewrite_rule(
		'^([^/]+)-winelabel-(\d{2})/?$',
		'index.php?elabel_product=$matches[1]&elabel_year=$matches[2]',
		'top'
	);

	// Bare label (no year): {product-slug}-winelabel/ -> redirect to latest vintage
	add_rewrite_rule(
		'^([^/]+)-winelabel/?$',
		'index.php?elabel_product=$matches[1]',
		'top'
	);

	// Index page: /e-labels/ (slug configurable in settings).
	$index_slug = wleu_index_slug();
	add_rewrite_rule(
		'^' . preg_quote( $index_slug, '/' ) . '/?$',
		'index.php?elabel_index=1',
		'top'
	);
}, 20 );

/**
 * Register custom query vars.
 */
add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'elabel_product';
	$vars[] = 'elabel_year';
	$vars[] = 'elabel_index';
	return $vars;
} );

// ── Template Redirect ────────────────────────────────────────

/**
 * Intercept requests and render bare HTML templates.
 */
add_action( 'template_redirect', function () {
	$product_slug = get_query_var( 'elabel_product' );
	$year         = get_query_var( 'elabel_year' );
	$is_index     = get_query_var( 'elabel_index' );

	// EN is the default language. Second language (via ?lang=xx) requires Pro.
	$lang = 'en';
	if ( wleu_is_pro() && isset( $_GET['lang'] ) ) {
		$alt_lang = get_option( 'wleu_second_language_code', 'it' );
		$requested = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
		if ( $requested === $alt_lang ) {
			$lang = $alt_lang;
		}
	}

	if ( $product_slug ) {
		header_remove( 'Set-Cookie' );
		$product_slug = sanitize_title( $product_slug );

		// QR code PDF download (Pro only).
		if ( isset( $_GET['qr'] ) && sanitize_text_field( wp_unslash( $_GET['qr'] ) ) === 'pdf' ) {
			if ( ! wleu_is_pro() ) {
				status_header( 403 );
				echo 'QR PDF download requires WineLabel EU Pro.';
				exit;
			}
			wleu_render_elabel_qr_pdf( $product_slug, $year );
			exit;
		}

		// No year specified -> 301 redirect to latest vintage.
		if ( empty( $year ) ) {
			$latest_year = wleu_get_latest_year( $product_slug );
			if ( $latest_year ) {
				$lang_param = ( $lang !== 'en' ) ? '?lang=' . $lang : '';
				wp_safe_redirect(
					home_url( '/' . $product_slug . '-winelabel-' . $latest_year . '/' . $lang_param ),
					301
				);
				exit;
			}
		}

		wleu_render_elabel_single( $product_slug, $year, $lang );
		exit;
	}

	if ( $is_index ) {
		header_remove( 'Set-Cookie' );
		wleu_render_elabel_index( $lang );
		exit;
	}
} );

// ── Helper Functions ─────────────────────────────────────────

/**
 * Get the latest (highest) vintage year for a product slug.
 *
 * @param string $product_slug Product slug.
 * @return string|false Two-digit year string, or false if none found.
 */
function wleu_get_latest_year( $product_slug ) {
	$products = get_posts( [
		'post_type'      => wleu_product_post_type(),
		'name'           => $product_slug,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	] );

	if ( empty( $products ) ) {
		return false;
	}

	$vintages = get_posts( [
		'post_type'      => 'elabel_vintage',
		'post_parent'    => $products[0]->ID,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_elabel_annata',
		'order'          => 'DESC',
	] );

	if ( empty( $vintages ) ) {
		return false;
	}

	return carbon_get_post_meta( $vintages[0]->ID, 'elabel_annata' ) ?: false;
}

/**
 * Find a vintage post for a given product ID and year.
 *
 * @param int    $product_id Product post ID.
 * @param string $year       Two-digit vintage year.
 * @return WP_Post|null
 */
function wleu_find_vintage( $product_id, $year ) {
	$vintages = get_posts( [
		'post_type'      => 'elabel_vintage',
		'post_parent'    => $product_id,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'   => '_elabel_annata',
				'value' => $year,
			],
		],
	] );

	return $vintages[0] ?? null;
}

/**
 * Get all published vintage posts for a product, sorted by year.
 *
 * @param int    $product_id Product post ID.
 * @param string $order      Sort order ('ASC' or 'DESC').
 * @return WP_Post[]
 */
function wleu_get_vintages( $product_id, $order = 'ASC' ) {
	return get_posts( [
		'post_type'      => 'elabel_vintage',
		'post_parent'    => $product_id,
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_elabel_annata',
		'order'          => $order,
	] );
}

// ── Activation / Deactivation ────────────────────────────────

/**
 * Plugin activation.
 */
register_activation_hook( __FILE__, function () {
	// Register CPT before flushing.
	register_post_type( 'elabel_vintage', [
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => false,
		'supports'     => false,
		'has_archive'  => false,
		'rewrite'      => false,
	] );

	add_rewrite_rule(
		'^([^/]+)-winelabel-(\d{2})/?$',
		'index.php?elabel_product=$matches[1]&elabel_year=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^([^/]+)-winelabel/?$',
		'index.php?elabel_product=$matches[1]',
		'top'
	);
	$index_slug = get_option( 'wleu_index_slug', 'winelabel' );
	add_rewrite_rule(
		'^' . preg_quote( $index_slug, '/' ) . '/?$',
		'index.php?elabel_index=1',
		'top'
	);
	flush_rewrite_rules();

	// Store version.
	update_option( 'wleu_version', WLEU_VERSION );

	// Create temp dir for DomPDF.
	$upload_dir = wp_upload_dir();
	$tmp_dir    = $upload_dir['basedir'] . '/winelabel-eu-tmp';
	wp_mkdir_p( $tmp_dir );
} );

/**
 * Plugin deactivation.
 */
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();

	// Clean transients only (preserve data).
	delete_transient( 'wleu_license_status' );
} );
