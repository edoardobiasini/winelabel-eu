<?php
/**
 * Admin settings page for WineLabel EU.
 *
 * Top-level menu with 3 tabs: License, Settings, Usage.
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
	// License settings.
	register_setting( 'wleu_license', 'wleu_license_key', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );

	// General settings.
	register_setting( 'wleu_settings', 'wleu_base_url', [
		'type'              => 'string',
		'sanitize_callback' => 'esc_url_raw',
		'default'           => '',
	] );

	register_setting( 'wleu_settings', 'wleu_second_language_enabled', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );

	register_setting( 'wleu_settings', 'wleu_second_language_code', [
		'type'              => 'string',
		'sanitize_callback' => function ( $val ) {
			return strtolower( substr( sanitize_text_field( $val ), 0, 2 ) );
		},
		'default'           => 'it',
	] );

	register_setting( 'wleu_settings', 'wleu_show_footer_credit', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => 'yes',
	] );

	register_setting( 'wleu_settings', 'wleu_index_slug', [
		'type'              => 'string',
		'sanitize_callback' => function ( $val ) {
			$val = sanitize_title( $val );
			$val = ! empty( $val ) ? $val : 'winelabel';

			// Always flush rewrite rules when settings are saved.
			add_action( 'shutdown', 'flush_rewrite_rules' );

			return $val;
		},
		'default'           => 'winelabel',
	] );

	register_setting( 'wleu_settings', 'wleu_use_woocommerce', [
		'type'              => 'string',
		'sanitize_callback' => function ( $val ) {
			$val = sanitize_text_field( $val );
			// Flush rewrite rules when WC integration changes (CPT registration differs).
			if ( $val !== get_option( 'wleu_use_woocommerce', 'yes' ) ) {
				add_action( 'shutdown', 'flush_rewrite_rules' );
			}
			return $val;
		},
		'default'           => 'yes',
	] );

	register_setting( 'wleu_settings', 'wleu_delete_data_on_uninstall', [
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default'           => '',
	] );
} );

/**
 * Handle translations save/reset actions.
 */
add_action( 'admin_init', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Save custom strings.
	if ( isset( $_POST['wleu_save_translations'] ) ) {
		check_admin_referer( 'wleu_translations_nonce', 'wleu_translations_nonce_field' );

		$strings = [];
		if ( ! empty( $_POST['wleu_strings'] ) && is_array( $_POST['wleu_strings'] ) ) {
			foreach ( $_POST['wleu_strings'] as $key => $value ) {
				$key = sanitize_key( $key );
				$value = wp_kses( $value, [ 'strong' => [], 'em' => [], 'a' => [ 'href' => [] ] ] );
				if ( $value !== '' ) {
					$strings[ $key ] = $value;
				}
			}
		}
		update_option( 'wleu_custom_strings', $strings );
		add_settings_error( 'wleu_translations', 'saved', __( 'Translations saved.', 'winelabel-eu' ), 'success' );

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_redirect( admin_url( 'admin.php?page=winelabel-eu&tab=translations&settings-updated=true' ) );
		exit;
	}

	// Reset to defaults.
	if ( isset( $_POST['wleu_reset_translations'] ) ) {
		check_admin_referer( 'wleu_translations_nonce', 'wleu_translations_nonce_field' );
		delete_option( 'wleu_custom_strings' );
		add_settings_error( 'wleu_translations', 'reset', __( 'Translations reset to defaults.', 'winelabel-eu' ), 'success' );

		set_transient( 'settings_errors', get_settings_errors(), 30 );
		wp_redirect( admin_url( 'admin.php?page=winelabel-eu&tab=translations&settings-updated=true' ) );
		exit;
	}
} );

/**
 * Handle license activation/deactivation actions.
 */
add_action( 'admin_init', function () {
	if ( ! isset( $_POST['wleu_license_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'wleu_license_nonce', 'wleu_license_nonce_field' );

	$action = sanitize_text_field( $_POST['wleu_license_action'] );

	if ( $action === 'activate' ) {
		$key = sanitize_text_field( $_POST['wleu_license_key'] ?? '' );
		if ( ! empty( $key ) ) {
			update_option( 'wleu_license_key', $key );
			$valid = wleu_validate_license( $key );
			if ( $valid ) {
				add_settings_error( 'wleu_license', 'activated', __( 'License activated successfully.', 'winelabel-eu' ), 'success' );
			} else {
				add_settings_error( 'wleu_license', 'invalid', __( 'Invalid license key.', 'winelabel-eu' ), 'error' );
			}
		}
	} elseif ( $action === 'deactivate' ) {
		wleu_deactivate_license();
		add_settings_error( 'wleu_license', 'deactivated', __( 'License deactivated.', 'winelabel-eu' ), 'success' );
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 );
	wp_redirect( admin_url( 'admin.php?page=winelabel-eu&tab=license&settings-updated=true' ) );
	exit;
} );

/**
 * Render the settings page.
 */
function wleu_render_settings_page() {
	$active_tab = sanitize_text_field( $_GET['tab'] ?? 'license' );
	$tabs = [
		'license'      => __( 'License', 'winelabel-eu' ),
		'settings'     => __( 'Settings', 'winelabel-eu' ),
		'translations' => __( 'Translations', 'winelabel-eu' ),
		'usage'        => __( 'Usage', 'winelabel-eu' ),
	];
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
				case 'license':
					wleu_render_license_tab();
					break;
				case 'settings':
					wleu_render_settings_tab();
					break;
				case 'translations':
					wleu_render_translations_tab();
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
 * Render the License tab.
 */
function wleu_render_license_tab() {
	$key    = get_option( 'wleu_license_key', '' );
	$is_pro = wleu_is_pro();
	?>
	<?php settings_errors( 'wleu_license' ); ?>

	<div class="card" style="max-width: 600px;">
		<h2><?php esc_html_e( 'License Status', 'winelabel-eu' ); ?></h2>
		<p>
			<?php esc_html_e( 'Plan:', 'winelabel-eu' ); ?>
			<strong style="color: <?php echo $is_pro ? '#46b450' : '#666'; ?>;">
				<?php echo esc_html( wleu_license_status_label() ); ?>
			</strong>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'wleu_license_nonce', 'wleu_license_nonce_field' ); ?>

			<?php if ( $is_pro ) : ?>
				<p><?php esc_html_e( 'Your Pro license is active.', 'winelabel-eu' ); ?></p>
				<p>
					<input type="hidden" name="wleu_license_action" value="deactivate">
					<?php submit_button( __( 'Deactivate License', 'winelabel-eu' ), 'secondary', 'submit', false ); ?>
				</p>
			<?php else : ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wleu_license_key"><?php esc_html_e( 'License Key', 'winelabel-eu' ); ?></label>
						</th>
						<td>
							<input type="text" id="wleu_license_key" name="wleu_license_key"
								   value="<?php echo esc_attr( $key ); ?>"
								   class="regular-text" placeholder="WLEU-XXXX-XXXX-XXXX">
						</td>
					</tr>
				</table>
				<input type="hidden" name="wleu_license_action" value="activate">
				<?php submit_button( __( 'Activate License', 'winelabel-eu' ) ); ?>
			<?php endif; ?>
		</form>
	</div>
	<?php
}

/**
 * Render the Settings tab.
 */
function wleu_render_settings_tab() {
	$is_pro = wleu_is_pro();
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
						<label>
							<input type="hidden" name="wleu_use_woocommerce" value="no">
							<input type="checkbox" name="wleu_use_woocommerce" value="yes"
								   <?php checked( get_option( 'wleu_use_woocommerce', 'yes' ), 'yes' ); ?>>
							<?php esc_html_e( 'Attach digital labels to WooCommerce products', 'winelabel-eu' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, the label fields appear on your WooCommerce product editor. When disabled, a separate "Wines" menu is used instead.', 'winelabel-eu' ); ?>
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
					<label for="wleu_base_url"><?php esc_html_e( 'Base URL for QR Codes', 'winelabel-eu' ); ?></label>
				</th>
				<td>
					<?php if ( $is_pro ) : ?>
						<input type="url" id="wleu_base_url" name="wleu_base_url"
							   value="<?php echo esc_attr( get_option( 'wleu_base_url', '' ) ); ?>"
							   class="regular-text"
							   placeholder="<?php echo esc_attr( home_url() ); ?>">
						<p class="description">
							<?php esc_html_e( 'Override the base URL used in QR codes. Leave blank to use your site URL.', 'winelabel-eu' ); ?>
						</p>
					<?php else : ?>
						<input type="url" class="regular-text" disabled placeholder="<?php echo esc_attr( home_url() ); ?>">
						<p class="description"><?php esc_html_e( 'Pro feature. Upgrade to customize the base URL for QR codes.', 'winelabel-eu' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wleu_index_slug"><?php esc_html_e( 'Index Page URL', 'winelabel-eu' ); ?></label>
				</th>
				<td>
					<?php if ( $is_pro ) : ?>
						<code><?php echo esc_html( home_url( '/' ) ); ?></code>
						<input type="text" id="wleu_index_slug" name="wleu_index_slug"
							   value="<?php echo esc_attr( get_option( 'wleu_index_slug', 'winelabel' ) ); ?>"
							   style="width: 120px;" placeholder="winelabel">
						<code>/</code>
						<p class="description">
							<?php esc_html_e( 'URL slug for the label index page.', 'winelabel-eu' ); ?>
						</p>
					<?php else : ?>
						<code><?php echo esc_html( home_url( '/winelabel/' ) ); ?></code>
						<p class="description"><?php esc_html_e( 'Upgrade to Pro to customize the index page URL.', 'winelabel-eu' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Second Language', 'winelabel-eu' ); ?>
				</th>
				<td>
					<?php if ( $is_pro ) : ?>
						<label>
							<input type="checkbox" name="wleu_second_language_enabled" value="yes"
								   <?php checked( get_option( 'wleu_second_language_enabled', '' ), 'yes' ); ?>>
							<?php esc_html_e( 'Enable bilingual labels', 'winelabel-eu' ); ?>
						</label>
						<br><br>
						<label>
							<?php esc_html_e( 'Language code:', 'winelabel-eu' ); ?>
							<input type="text" name="wleu_second_language_code"
								   value="<?php echo esc_attr( get_option( 'wleu_second_language_code', 'it' ) ); ?>"
								   style="width: 60px; text-transform: uppercase;"
								   maxlength="2" placeholder="IT">
						</label>
						<p class="description"><?php esc_html_e( 'Two-letter code for the second language (e.g. IT, DE, FR, ES).', 'winelabel-eu' ); ?></p>
					<?php else : ?>
						<label>
							<input type="checkbox" disabled>
							<?php esc_html_e( 'Enable bilingual labels', 'winelabel-eu' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Pro feature. Free labels are English-only.', 'winelabel-eu' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php esc_html_e( 'Footer Credit', 'winelabel-eu' ); ?>
				</th>
				<td>
					<?php if ( $is_pro ) : ?>
						<label>
							<input type="checkbox" name="wleu_show_footer_credit" value="yes"
								   <?php checked( get_option( 'wleu_show_footer_credit', 'yes' ), 'yes' ); ?>>
							<?php esc_html_e( 'Show "Powered by WineLabel EU" in label footer', 'winelabel-eu' ); ?>
						</label>
					<?php else : ?>
						<label>
							<input type="checkbox" checked disabled>
							<?php esc_html_e( 'Show "Powered by WineLabel EU" in label footer', 'winelabel-eu' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Pro feature. Upgrade to remove the footer credit.', 'winelabel-eu' ); ?></p>
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
	<?php
}

/**
 * Render the Usage tab.
 */
function wleu_render_usage_tab() {
	$is_pro = wleu_is_pro();
	$count  = wleu_vintage_count();
	$limit  = WLEU_FREE_VINTAGE_LIMIT;

	// Progress bar values.
	if ( $is_pro ) {
		$progress_pct   = 0;
		$progress_label = sprintf( __( '%d published vintages (unlimited)', 'winelabel-eu' ), $count );
	} else {
		$progress_pct   = $limit > 0 ? min( 100, round( ( $count / $limit ) * 100 ) ) : 0;
		$progress_label = sprintf( __( '%1$d / %2$d published vintages', 'winelabel-eu' ), $count, $limit );
	}

	$bar_color = '#46b450';
	if ( ! $is_pro && $count >= $limit ) {
		$bar_color = '#dc3232';
	} elseif ( ! $is_pro && $count >= $limit - 1 ) {
		$bar_color = '#ffb900';
	}
	?>
	<div class="card" style="max-width: 700px;">
		<h2><?php esc_html_e( 'Vintage Usage', 'winelabel-eu' ); ?></h2>

		<p><strong><?php echo esc_html( $progress_label ); ?></strong></p>

		<?php if ( ! $is_pro ) : ?>
			<div style="background: #e0e0e0; border-radius: 4px; height: 24px; margin: 8px 0 16px;">
				<div style="background: <?php echo esc_attr( $bar_color ); ?>; height: 24px; border-radius: 4px; width: <?php echo esc_attr( $progress_pct ); ?>%; transition: width 0.3s;"></div>
			</div>
		<?php endif; ?>
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
					'post_type'      => 'elabel_vintage',
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

/**
 * Render the Translations tab.
 */
function wleu_render_translations_tab() {
	$is_pro   = wleu_is_pro();
	$alt_code = strtoupper( get_option( 'wleu_second_language_code', 'it' ) );
	$custom   = get_option( 'wleu_custom_strings', [] );
	if ( ! is_array( $custom ) ) {
		$custom = [];
	}

	if ( ! $is_pro ) {
		echo '<p>' . esc_html__( 'Upgrade to Pro to customize label translations.', 'winelabel-eu' ) . '</p>';
		return;
	}

	if ( get_option( 'wleu_second_language_enabled', '' ) !== 'yes' ) {
		echo '<p>' . esc_html__( 'Enable a second language in the Settings tab first.', 'winelabel-eu' ) . '</p>';
		return;
	}

	// EN defaults (used as reference and fallback).
	$en_defaults = [
		'ingredienti'               => 'Ingredients',
		'materia_prima'             => 'Raw material:',
		'correttori_acidita'        => 'Acidity regulators:',
		'stabilizzanti'             => 'Stabilizers:',
		'antiossidanti'             => 'Antioxidants:',
		'contiene_solfiti'          => 'Contains sulfites',
		'info_nutrizionali'         => 'Nutritional Information',
		'valori_per_100ml'          => 'nutritional values per <strong>100 ml</strong> of product',
		'calorie'                   => 'Calories',
		'grassi'                    => 'Fat',
		'grassi_saturi'             => 'of which Saturated Fat',
		'carboidrati'               => 'Carbohydrates',
		'zuccheri'                  => 'of which Sugars',
		'proteine'                  => 'Protein',
		'sale'                      => 'Salt',
		'acidita_totale'            => 'Total acidity',
		'grado_alcolico'            => 'Alcohol content',
		'solforosa_totale'          => 'Total Sulfur Dioxide',
		'raccolta_diff'             => 'Waste Sorting',
		'componente'                => 'Component',
		'codice'                    => 'Code',
		'materiale'                 => 'Material',
		'raccolta'                  => 'Collection',
		'verifica_comune'           => 'Check your local municipality regulations.',
		'indice_vini'               => 'Wine Index',
		'etichetta_digitale'        => 'Digital Label',
		'reg_disclaimer'            => 'Mandatory information pursuant to Reg. (EU) 2021/2117 — Art. 119 Reg. (EU) 1308/2013',
		'reg_footer'                => 'Reg. (EU) 2021/2117 — Art. 119 Reg. (EU) 1308/2013',
	];

	// IT defaults for pre-filling when language is IT.
	$it_defaults = [
		'ingredienti'               => 'Ingredienti',
		'materia_prima'             => 'Materia prima:',
		'correttori_acidita'        => 'Correttori di acidità:',
		'stabilizzanti'             => 'Stabilizzanti:',
		'antiossidanti'             => 'Antiossidanti:',
		'contiene_solfiti'          => 'Contiene solfiti',
		'info_nutrizionali'         => 'Informazioni Nutrizionali',
		'valori_per_100ml'          => 'valori nutrizionali per <strong>100 ml</strong> di prodotto',
		'calorie'                   => 'Calorie',
		'grassi'                    => 'Grassi',
		'grassi_saturi'             => 'di cui Acidi Grassi Saturi',
		'carboidrati'               => 'Carboidrati',
		'zuccheri'                  => 'di cui Zuccheri',
		'proteine'                  => 'Proteine',
		'sale'                      => 'Sale',
		'acidita_totale'            => 'Acidità totale',
		'grado_alcolico'            => 'Grado alcolico',
		'solforosa_totale'          => 'Anidride Solforosa Totale',
		'raccolta_diff'             => 'Raccolta Differenziata',
		'componente'                => 'Componente',
		'codice'                    => 'Codice',
		'materiale'                 => 'Materiale',
		'raccolta'                  => 'Raccolta',
		'verifica_comune'           => 'Verifica il regolamento del tuo Comune.',
		'indice_vini'               => 'Indice vini',
		'etichetta_digitale'        => 'Etichetta Digitale',
		'reg_disclaimer'            => 'Informazioni obbligatorie ai sensi del Reg. (UE) 2021/2117 — Art. 119 Reg. (UE) 1308/2013',
		'reg_footer'                => 'Reg. (UE) 2021/2117 — Art. 119 Reg. (UE) 1308/2013',
	];

	// Placeholders: use IT defaults if language is IT, otherwise EN.
	$placeholders = strtolower( $alt_code ) === 'it' ? $it_defaults : $en_defaults;

	// Group keys by section for cleaner UI.
	$sections = [
		__( 'Ingredients', 'winelabel-eu' ) => [
			'ingredienti', 'materia_prima', 'correttori_acidita',
			'stabilizzanti', 'antiossidanti', 'contiene_solfiti',
		],
		__( 'Nutritional Values', 'winelabel-eu' ) => [
			'info_nutrizionali', 'valori_per_100ml', 'calorie',
			'grassi', 'grassi_saturi', 'carboidrati', 'zuccheri',
			'proteine', 'sale', 'acidita_totale', 'grado_alcolico',
			'solforosa_totale',
		],
		__( 'Waste Sorting', 'winelabel-eu' ) => [
			'raccolta_diff', 'componente', 'codice', 'materiale',
			'raccolta', 'verifica_comune',
		],
		__( 'Page Layout', 'winelabel-eu' ) => [
			'etichetta_digitale', 'indice_vini', 'reg_disclaimer', 'reg_footer',
		],
	];

	settings_errors( 'wleu_translations' );
	?>
	<div style="max-width: 700px;">
		<p>
			<?php printf(
				esc_html__( 'Customize the %s label strings. Leave blank to use the default.', 'winelabel-eu' ),
				'<strong>' . esc_html( $alt_code ) . '</strong>'
			); ?>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'wleu_translations_nonce', 'wleu_translations_nonce_field' ); ?>

			<?php foreach ( $sections as $section_label => $keys ) : ?>
				<h3 style="margin-top: 24px; margin-bottom: 8px;"><?php echo esc_html( $section_label ); ?></h3>
				<table class="form-table">
					<?php foreach ( $keys as $key ) : ?>
						<tr>
							<th scope="row" style="width: 40%;">
								<label for="wleu_str_<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $en_defaults[ $key ] ?? $key ); ?>
								</label>
							</th>
							<td>
								<input type="text" id="wleu_str_<?php echo esc_attr( $key ); ?>"
									   name="wleu_strings[<?php echo esc_attr( $key ); ?>]"
									   value="<?php echo esc_attr( $custom[ $key ] ?? '' ); ?>"
									   class="regular-text"
									   placeholder="<?php echo esc_attr( wp_strip_all_tags( $placeholders[ $key ] ?? '' ) ); ?>">
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>

			<p class="submit" style="display: flex; gap: 8px; align-items: center;">
				<input type="submit" name="wleu_save_translations" class="button button-primary"
					   value="<?php esc_attr_e( 'Save Translations', 'winelabel-eu' ); ?>">
				<input type="submit" name="wleu_reset_translations" class="button button-secondary"
					   value="<?php esc_attr_e( 'Reset to Defaults', 'winelabel-eu' ); ?>"
					   onclick="return confirm('<?php echo esc_js( __( 'Reset all translations to defaults?', 'winelabel-eu' ) ); ?>');">
			</p>
		</form>
	</div>
	<?php
}
