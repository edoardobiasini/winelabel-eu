<?php
/**
 * Bare HTML template output for WineLabel EU (Lite).
 *
 * These functions output complete HTML documents and exit —
 * no WordPress theme, no wp_head/wp_footer, no scripts, no cookies.
 * Fully compliant with EU Reg. 2021/2117, Art. 119(5).
 *
 * English-only — no bilingual support in the lite version.
 * Supports multiple vintages per product via the wleu_vintage CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a translated UI string.
 *
 * Uses static arrays for bare HTML pages (must work outside WP theme context).
 * Lite version: English only.
 *
 * @param string $key   Translation key.
 * @param string $lang  Language code (always 'en' in lite).
 * @return string
 */
function wleu_t( $key, $lang = 'en' ) {
	static $en = [
		'ingredienti'        => 'Ingredients',
		'materia_prima'      => 'Raw material:',
		'correttori_acidita' => 'Acidity regulators:',
		'stabilizzanti'      => 'Stabilizers:',
		'antiossidanti'      => 'Antioxidants:',
		'contiene_solfiti'   => 'Contains sulfites',
		'info_nutrizionali'  => 'Nutritional Information',
		'valori_per_100ml'   => 'nutritional values per <strong>100 ml</strong> of product',
		'calorie'            => 'Calories',
		'grassi'             => 'Fat',
		'grassi_saturi'      => 'of which Saturated Fat',
		'carboidrati'        => 'Carbohydrates',
		'zuccheri'           => 'of which Sugars',
		'proteine'           => 'Protein',
		'sale'               => 'Salt',
		'acidita_totale'     => 'Total acidity',
		'grado_alcolico'     => 'Alcohol content',
		'solforosa_totale'   => 'Total Sulfur Dioxide',
		'raccolta_diff'      => 'Waste Sorting',
		'componente'         => 'Component',
		'codice'             => 'Code',
		'materiale'          => 'Material',
		'raccolta'           => 'Collection',
		'verifica_comune'    => 'Check your local municipality regulations.',
		'indice_vini'        => 'Wine Index',
		'reg_disclaimer'     => 'Mandatory information pursuant to Reg. (EU) 2021/2117 &mdash; Art. 119 Reg. (EU) 1308/2013',
		'nessun_vino'        => 'No wines available at the moment.',
		'etichetta_digitale' => 'Digital Label',
		'non_trovato'        => 'Not found',
		'etichetta_non_trovata'      => 'Label not found.',
		'etichetta_non_disponibile'  => 'Label not available.',
		'annata_non_trovata'         => 'Vintage not found.',
		'reg_footer'         => 'Reg. (EU) 2021/2117 &mdash; Art. 119 Reg. (EU) 1308/2013',
		'powered_by'         => 'Powered by WineLabel EU',
	];

	$strings = $en;

	/**
	 * Filter the translation strings for extensibility.
	 *
	 * @param array  $strings All translation strings for the given language.
	 * @param string $lang    Language code.
	 */
	$strings = apply_filters( 'wleu_translation_strings', $strings, $lang );

	return $strings[ $key ] ?? $key;
}

/**
 * Render a single product vintage's digital label.
 *
 * @param string $product_slug The product slug.
 * @param string $year         Two-digit vintage year (e.g. '24').
 * @param string $lang         Language code (always 'en' in lite).
 */
function wleu_render_elabel_single( $product_slug, $year, $lang = 'en' ) {

	$products = get_posts( [
		'post_type'      => wleu_product_post_type(),
		'name'           => $product_slug,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	] );

	if ( empty( $products ) ) {
		status_header( 404 );
		$t_title = wleu_t( 'non_trovato', $lang );
		$t_body  = wleu_t( 'etichetta_non_trovata', $lang );
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . esc_html( $t_title ) . '</title></head><body><p>' . esc_html( $t_body ) . '</p></body></html>';
		return;
	}

	$product    = $products[0];
	$product_id = $product->ID;

	if ( ! carbon_get_post_meta( $product_id, 'elabel_enabled' ) ) {
		status_header( 404 );
		$t_title = wleu_t( 'non_trovato', $lang );
		$t_body  = wleu_t( 'etichetta_non_disponibile', $lang );
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . esc_html( $t_title ) . '</title></head><body><p>' . esc_html( $t_body ) . '</p></body></html>';
		return;
	}

	// ── Find the vintage post ───────────────────────────────
	$vintage = wleu_find_vintage( $product_id, $year );

	if ( ! $vintage ) {
		status_header( 404 );
		$t_title = wleu_t( 'non_trovato', $lang );
		$t_body  = wleu_t( 'annata_non_trovata', $lang );
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . esc_html( $t_title ) . '</title></head><body><p>' . esc_html( $t_body ) . '</p></body></html>';
		return;
	}

	$vintage_id = $vintage->ID;

	// ── Wine name (from product title) ──────────────────────
	$wine_name = get_the_title( $product_id );

	$wine_name_display = $wine_name . ' ' . $year;

	// ── Ingredients (from vintage) — always use _en fields ──
	$contiene_solfiti = ! empty( carbon_get_post_meta( $vintage_id, 'elabel_contiene_solfiti' ) );

	$materia_prima      = carbon_get_post_meta( $vintage_id, 'elabel_materia_prima_en' );
	$correttori_acidita = carbon_get_post_meta( $vintage_id, 'elabel_correttori_acidita_en' );
	$stabilizzanti      = carbon_get_post_meta( $vintage_id, 'elabel_stabilizzanti_en' );
	$antiossidanti      = carbon_get_post_meta( $vintage_id, 'elabel_antiossidanti_en' );
	$altri_ingredienti  = carbon_get_post_meta( $vintage_id, 'elabel_altri_ingredienti_en' );

	// ── Nutrition (from vintage) ────────────────────────────
	$energia_kj       = carbon_get_post_meta( $vintage_id, 'elabel_energia_kj' );
	$energia_kcal     = carbon_get_post_meta( $vintage_id, 'elabel_energia_kcal' );
	$grassi           = carbon_get_post_meta( $vintage_id, 'elabel_grassi' );
	$grassi_saturi    = carbon_get_post_meta( $vintage_id, 'elabel_grassi_saturi' );
	$carboidrati      = carbon_get_post_meta( $vintage_id, 'elabel_carboidrati' );
	$zuccheri         = carbon_get_post_meta( $vintage_id, 'elabel_zuccheri' );
	$proteine         = carbon_get_post_meta( $vintage_id, 'elabel_proteine' );
	$sale             = carbon_get_post_meta( $vintage_id, 'elabel_sale' );
	$acidita_totale   = carbon_get_post_meta( $vintage_id, 'elabel_acidita_totale' );
	$grado_alcolico   = carbon_get_post_meta( $vintage_id, 'elabel_grado_alcolico' );
	$solforosa_totale = carbon_get_post_meta( $vintage_id, 'elabel_solforosa_totale' );

	// ── Recycling (from vintage) ────────────────────────────
	$raccolta = carbon_get_post_meta( $vintage_id, 'elabel_raccolta' );

	// ── Output ──────────────────────────────────────────────
	wleu_html_header( $wine_name_display, $lang );
	?>

	<p class="elabel-wine-name"><?php echo esc_html( $wine_name ); ?></p>

	<div class="elabel-card">
		<h2><?php echo esc_html( wleu_t( 'ingredienti', $lang ) ); ?></h2>
		<div class="elabel-ingredients">
			<?php if ( $materia_prima ) : ?>
				<p><strong><?php echo wp_kses_post( wleu_t( 'materia_prima', $lang ) ); ?></strong> <?php echo esc_html( $materia_prima ); ?></p>
			<?php endif; ?>
			<?php if ( $correttori_acidita ) : ?>
				<p><strong><?php echo wp_kses_post( wleu_t( 'correttori_acidita', $lang ) ); ?></strong><br><?php echo esc_html( $correttori_acidita ); ?></p>
			<?php endif; ?>
			<?php if ( $stabilizzanti ) : ?>
				<p><strong><?php echo esc_html( wleu_t( 'stabilizzanti', $lang ) ); ?></strong> <?php echo esc_html( $stabilizzanti ); ?></p>
			<?php endif; ?>
			<?php if ( $antiossidanti ) : ?>
				<p><strong><?php echo esc_html( wleu_t( 'antiossidanti', $lang ) ); ?></strong> <?php echo esc_html( $antiossidanti ); ?></p>
			<?php endif; ?>
			<?php if ( $altri_ingredienti ) : ?>
				<p><?php echo esc_html( $altri_ingredienti ); ?></p>
			<?php endif; ?>
			<?php if ( $contiene_solfiti ) : ?>
				<p class="elabel-allergen"><strong><?php echo esc_html( wleu_t( 'contiene_solfiti', $lang ) ); ?></strong></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="elabel-card">
		<h2><?php echo esc_html( wleu_t( 'info_nutrizionali', $lang ) ); ?></h2>
		<p class="elabel-per"><?php echo wp_kses_post( wleu_t( 'valori_per_100ml', $lang ) ); ?></p>

		<table class="elabel-table">
			<tbody>
				<tr>
					<td><?php echo esc_html( wleu_t( 'calorie', $lang ) ); ?></td>
					<td class="elabel-val"><strong><?php echo esc_html( $energia_kj ); ?></strong> kj &ndash; <strong><?php echo esc_html( $energia_kcal ); ?></strong> kcal</td>
				</tr>
				<tr>
					<td><?php echo esc_html( wleu_t( 'grassi', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grassi ); ?> g</td>
				</tr>
				<tr class="elabel-sub">
					<td><?php echo esc_html( wleu_t( 'grassi_saturi', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grassi_saturi ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo esc_html( wleu_t( 'carboidrati', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $carboidrati ); ?> g</td>
				</tr>
				<tr class="elabel-sub">
					<td><?php echo esc_html( wleu_t( 'zuccheri', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $zuccheri ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo esc_html( wleu_t( 'proteine', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $proteine ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo esc_html( wleu_t( 'sale', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $sale ); ?> g</td>
				</tr>
				<?php if ( $acidita_totale ) : ?>
				<tr class="elabel-extra">
					<td><?php echo wp_kses_post( wleu_t( 'acidita_totale', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $acidita_totale ); ?> g/l</td>
				</tr>
				<?php endif; ?>
				<?php if ( $grado_alcolico ) : ?>
				<tr class="elabel-extra">
					<td><?php echo esc_html( wleu_t( 'grado_alcolico', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grado_alcolico ); ?> %</td>
				</tr>
				<?php endif; ?>
				<?php if ( $solforosa_totale ) : ?>
				<tr class="elabel-extra">
					<td><?php echo esc_html( wleu_t( 'solforosa_totale', $lang ) ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $solforosa_totale ); ?> mg/l</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( ! empty( $raccolta ) ) : ?>
	<div class="elabel-card">
		<h2><?php echo esc_html( wleu_t( 'raccolta_diff', $lang ) ); ?></h2>
		<table class="elabel-table elabel-table-raccolta">
			<thead>
				<tr>
					<th><?php echo esc_html( wleu_t( 'componente', $lang ) ); ?></th>
					<th><?php echo esc_html( wleu_t( 'codice', $lang ) ); ?></th>
					<th><?php echo esc_html( wleu_t( 'materiale', $lang ) ); ?></th>
					<th><?php echo esc_html( wleu_t( 'raccolta', $lang ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $raccolta as $item ) :
					$r_componente = $item['componente_en'] ?? '';
					$r_materiale  = $item['materiale_en'] ?? '';
					$r_istruzioni = $item['istruzioni_en'] ?? '';
				?>
				<tr>
					<td><?php echo esc_html( $r_componente ); ?></td>
					<td><strong><?php echo esc_html( $item['codice'] ?? '' ); ?></strong></td>
					<td><?php echo esc_html( $r_materiale ); ?></td>
					<td><?php echo esc_html( $r_istruzioni ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="elabel-note"><?php echo esc_html( wleu_t( 'verifica_comune', $lang ) ); ?></p>
	</div>
	<?php endif; ?>

	<?php
	wleu_html_footer( $lang );
}

/**
 * Render the index page listing all products with digital labels.
 *
 * @param string $lang Language code (always 'en' in lite).
 */
function wleu_render_elabel_index( $lang = 'en' ) {

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
		'orderby'        => 'title',
		'order'          => 'ASC',
	] );

	// Build flat list of product x vintage entries.
	$entries = [];

	foreach ( $products as $product ) {
		$vintages = wleu_get_vintages( $product->ID );

		if ( empty( $vintages ) ) {
			continue;
		}

		$wine_name = get_the_title( $product->ID );

		foreach ( $vintages as $vintage ) {
			$year = carbon_get_post_meta( $vintage->ID, 'elabel_annata' );
			if ( empty( $year ) ) {
				continue;
			}

			$entries[] = [
				'name' => $wine_name . ' ' . $year,
				'url'  => wleu_label_url( $product->post_name, $year ),
				'year' => $year,
			];
		}
	}

	// Sort alphabetically by display name, then by year.
	usort( $entries, function ( $a, $b ) {
		$cmp = strcasecmp( $a['name'], $b['name'] );
		return $cmp !== 0 ? $cmp : strcmp( $a['year'], $b['year'] );
	} );

	wleu_html_header( wleu_t( 'etichetta_digitale', $lang ), $lang );
	?>

	<div class="elabel-card">
		<h2><?php echo esc_html( wleu_t( 'indice_vini', $lang ) ); ?></h2>
		<p><?php echo wp_kses_post( wleu_t( 'reg_disclaimer', $lang ) ); ?></p>

		<?php if ( empty( $entries ) ) : ?>
			<p><?php echo esc_html( wleu_t( 'nessun_vino', $lang ) ); ?></p>
		<?php else : ?>
			<ul class="elabel-index">
				<?php foreach ( $entries as $entry ) : ?>
					<li>
						<a href="<?php echo esc_url( $entry['url'] ); ?>"><?php echo esc_html( $entry['name'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php
	wleu_html_footer( $lang );
}

/**
 * Output the opening HTML, <head>, and opening <body> tags.
 *
 * CSS is loaded via wp_register_style + wp_enqueue_style + wp_print_styles
 * to comply with WordPress.org requirements (no inline styles).
 *
 * @param string $title Page <title>.
 * @param string $lang  Language code.
 */
function wleu_html_header( $title, $lang = 'en' ) {
	$title    = esc_html( $title );
	$t_label  = wleu_t( 'etichetta_digitale', $lang );

	// Register and enqueue the label stylesheet.
	wp_register_style( 'wleu-label', WLEU_URL . 'assets/label.css', [], WLEU_VERSION );
	wp_enqueue_style( 'wleu-label' );
	?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title ); ?> &mdash; <?php echo esc_html( $t_label ); ?></title>
	<?php wp_print_styles( 'wleu-label' ); ?>
</head>
<body>
<div class="elabel-wrap">
	<h1 class="elabel-page-title"><?php echo esc_html( $title ); ?></h1>
	<?php
}

/**
 * Output the closing HTML tags and regulation footer.
 *
 * @param string $lang Language code.
 */
function wleu_html_footer( $lang = 'en' ) {
	?>
	<div class="elabel-footer">
		<?php echo wp_kses_post( wleu_t( 'reg_footer', $lang ) ); ?>
		<div class="elabel-powered"><?php echo esc_html( wleu_t( 'powered_by', $lang ) ); ?></div>
	</div>
</div>
</body>
</html>
	<?php
}
