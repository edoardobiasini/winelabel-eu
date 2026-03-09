<?php
/**
 * Bare HTML template output for WineLabel EU.
 *
 * These functions output complete HTML documents and exit —
 * no WordPress theme, no wp_head/wp_footer, no scripts, no cookies.
 * Fully compliant with EU Reg. 2021/2117, Art. 119(5).
 *
 * Supports IT (default) and EN via ?lang=en query parameter.
 * Supports multiple vintages per product via the elabel_vintage CPT.
 */

/**
 * Return a translated UI string.
 *
 * Uses static arrays for bare HTML pages (must work outside WP theme context).
 *
 * @param string $key   Translation key.
 * @param string $lang  Language code ('it' or 'en').
 * @return string
 */
function wleu_t( $key, $lang = 'it' ) {
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
		'scarica_qr'         => 'Download QR (PDF)',
		'nessun_vino'        => 'No wines available at the moment.',
		'etichetta_digitale' => 'Digital Label',
		'non_trovato'        => 'Not found',
		'etichetta_non_trovata'      => 'Label not found.',
		'etichetta_non_disponibile'  => 'Label not available.',
		'annata_non_trovata'         => 'Vintage not found.',
		'reg_footer'         => 'Reg. (EU) 2021/2117 &mdash; Art. 119 Reg. (EU) 1308/2013',
		'powered_by'         => 'Powered by WineLabel EU',
	];

	static $it = [
		'ingredienti'        => 'Ingredienti',
		'materia_prima'      => 'Materia prima:',
		'correttori_acidita' => 'Correttori di acidit&agrave;:',
		'stabilizzanti'      => 'Stabilizzanti:',
		'antiossidanti'      => 'Antiossidanti:',
		'contiene_solfiti'   => 'Contiene solfiti',
		'info_nutrizionali'  => 'Informazioni Nutrizionali',
		'valori_per_100ml'   => 'valori nutrizionali per <strong>100 ml</strong> di prodotto',
		'calorie'            => 'Calorie',
		'grassi'             => 'Grassi',
		'grassi_saturi'      => 'di cui Acidi Grassi Saturi',
		'carboidrati'        => 'Carboidrati',
		'zuccheri'           => 'di cui Zuccheri',
		'proteine'           => 'Proteine',
		'sale'               => 'Sale',
		'acidita_totale'     => 'Acidit&agrave; totale',
		'grado_alcolico'     => 'Grado alcolico',
		'solforosa_totale'   => 'Anidride Solforosa Totale',
		'raccolta_diff'      => 'Raccolta Differenziata',
		'componente'         => 'Componente',
		'codice'             => 'Codice',
		'materiale'          => 'Materiale',
		'raccolta'           => 'Raccolta',
		'verifica_comune'    => 'Verifica il regolamento del tuo Comune.',
		'indice_vini'        => 'Indice vini',
		'reg_disclaimer'     => 'Informazioni obbligatorie ai sensi del Reg. (UE) 2021/2117 &mdash; Art. 119 Reg. (UE) 1308/2013',
		'scarica_qr'         => 'Scarica QR (PDF)',
		'nessun_vino'        => 'Nessun vino disponibile al momento.',
		'etichetta_digitale' => 'Etichetta Digitale',
		'non_trovato'        => 'Non trovato',
		'etichetta_non_trovata'      => 'Etichetta non trovata.',
		'etichetta_non_disponibile'  => 'Etichetta non disponibile.',
		'annata_non_trovata'         => 'Annata non trovata.',
		'reg_footer'         => 'Reg. (UE) 2021/2117 &mdash; Art. 119 Reg. (UE) 1308/2013',
		'powered_by'         => 'Powered by WineLabel EU',
	];

	$strings = ( $lang === 'en' ) ? $en : $it;

	/**
	 * Filter the translation strings for extensibility.
	 *
	 * @param array  $strings All translation strings for the given language.
	 * @param string $lang    Language code ('it' or 'en').
	 */
	$strings = apply_filters( 'wleu_translation_strings', $strings, $lang );

	return $strings[ $key ] ?? $key;
}

/**
 * Render a single product vintage's digital label.
 *
 * @param string $product_slug The WooCommerce product slug.
 * @param string $year         Two-digit vintage year (e.g. '24').
 * @param string $lang         Language code ('it' or 'en').
 */
function wleu_render_elabel_single( $product_slug, $year, $lang = 'it' ) {

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
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . $t_title . '</title></head><body><p>' . $t_body . '</p></body></html>';
		return;
	}

	$product    = $products[0];
	$product_id = $product->ID;

	if ( ! carbon_get_post_meta( $product_id, 'elabel_enabled' ) ) {
		status_header( 404 );
		$t_title = wleu_t( 'non_trovato', $lang );
		$t_body  = wleu_t( 'etichetta_non_disponibile', $lang );
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . $t_title . '</title></head><body><p>' . $t_body . '</p></body></html>';
		return;
	}

	// ── Find the vintage post ───────────────────────────────
	$vintage = wleu_find_vintage( $product_id, $year );

	if ( ! $vintage ) {
		status_header( 404 );
		$t_title = wleu_t( 'non_trovato', $lang );
		$t_body  = wleu_t( 'annata_non_trovata', $lang );
		echo '<!DOCTYPE html><html lang="' . esc_attr( $lang ) . '"><head><meta charset="UTF-8"><title>' . $t_title . '</title></head><body><p>' . $t_body . '</p></body></html>';
		return;
	}

	$vintage_id = $vintage->ID;

	// ── Wine name (from product title) ──────────────────────
	$wine_name = get_the_title( $product_id );

	$wine_name_display = $wine_name . ' ' . $year;

	// ── Ingredients (from vintage) ──────────────────────────
	// _en fields = EN (primary), base fields = second language (Pro).
	$contiene_solfiti = ! empty( carbon_get_post_meta( $vintage_id, 'elabel_contiene_solfiti' ) );

	if ( $lang === 'en' ) {
		// EN: prefer _en field, fall back to base field.
		$materia_prima      = carbon_get_post_meta( $vintage_id, 'elabel_materia_prima_en' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_materia_prima' );
		$correttori_acidita = carbon_get_post_meta( $vintage_id, 'elabel_correttori_acidita_en' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_correttori_acidita' );
		$stabilizzanti      = carbon_get_post_meta( $vintage_id, 'elabel_stabilizzanti_en' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_stabilizzanti' );
		$antiossidanti      = carbon_get_post_meta( $vintage_id, 'elabel_antiossidanti_en' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_antiossidanti' );
		$altri_ingredienti  = carbon_get_post_meta( $vintage_id, 'elabel_altri_ingredienti_en' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_altri_ingredienti' );
	} else {
		// Second language (Pro): prefer base field, fall back to _en field.
		$materia_prima      = carbon_get_post_meta( $vintage_id, 'elabel_materia_prima' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_materia_prima_en' );
		$correttori_acidita = carbon_get_post_meta( $vintage_id, 'elabel_correttori_acidita' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_correttori_acidita_en' );
		$stabilizzanti      = carbon_get_post_meta( $vintage_id, 'elabel_stabilizzanti' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_stabilizzanti_en' );
		$antiossidanti      = carbon_get_post_meta( $vintage_id, 'elabel_antiossidanti' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_antiossidanti_en' );
		$altri_ingredienti  = carbon_get_post_meta( $vintage_id, 'elabel_altri_ingredienti' ) ?: carbon_get_post_meta( $vintage_id, 'elabel_altri_ingredienti_en' );
	}

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
		<div class="elabel-section-header">
			<h2><?php echo wleu_t( 'ingredienti', $lang ); ?></h2>
			<?php echo wleu_flag_svg(); ?>
		</div>
		<div class="elabel-ingredients">
			<?php if ( $materia_prima ) : ?>
				<p><strong><?php echo wleu_t( 'materia_prima', $lang ); ?></strong> <?php echo esc_html( $materia_prima ); ?></p>
			<?php endif; ?>
			<?php if ( $correttori_acidita ) : ?>
				<p><strong><?php echo wleu_t( 'correttori_acidita', $lang ); ?></strong><br><?php echo esc_html( $correttori_acidita ); ?></p>
			<?php endif; ?>
			<?php if ( $stabilizzanti ) : ?>
				<p><strong><?php echo wleu_t( 'stabilizzanti', $lang ); ?></strong> <?php echo esc_html( $stabilizzanti ); ?></p>
			<?php endif; ?>
			<?php if ( $antiossidanti ) : ?>
				<p><strong><?php echo wleu_t( 'antiossidanti', $lang ); ?></strong> <?php echo esc_html( $antiossidanti ); ?></p>
			<?php endif; ?>
			<?php if ( $altri_ingredienti ) : ?>
				<p><?php echo esc_html( $altri_ingredienti ); ?></p>
			<?php endif; ?>
			<?php if ( $contiene_solfiti ) : ?>
				<p class="elabel-allergen"><strong><?php echo wleu_t( 'contiene_solfiti', $lang ); ?></strong></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="elabel-card">
		<h2><?php echo wleu_t( 'info_nutrizionali', $lang ); ?></h2>
		<p class="elabel-per"><?php echo wleu_t( 'valori_per_100ml', $lang ); ?></p>

		<table class="elabel-table">
			<tbody>
				<tr>
					<td><?php echo wleu_t( 'calorie', $lang ); ?></td>
					<td class="elabel-val"><strong><?php echo esc_html( $energia_kj ); ?></strong> kj &ndash; <strong><?php echo esc_html( $energia_kcal ); ?></strong> kcal</td>
				</tr>
				<tr>
					<td><?php echo wleu_t( 'grassi', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grassi ); ?> g</td>
				</tr>
				<tr class="elabel-sub">
					<td><?php echo wleu_t( 'grassi_saturi', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grassi_saturi ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo wleu_t( 'carboidrati', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $carboidrati ); ?> g</td>
				</tr>
				<tr class="elabel-sub">
					<td><?php echo wleu_t( 'zuccheri', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $zuccheri ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo wleu_t( 'proteine', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $proteine ); ?> g</td>
				</tr>
				<tr>
					<td><?php echo wleu_t( 'sale', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $sale ); ?> g</td>
				</tr>
				<?php if ( $acidita_totale ) : ?>
				<tr class="elabel-extra">
					<td><?php echo wleu_t( 'acidita_totale', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $acidita_totale ); ?> g/l</td>
				</tr>
				<?php endif; ?>
				<?php if ( $grado_alcolico ) : ?>
				<tr class="elabel-extra">
					<td><?php echo wleu_t( 'grado_alcolico', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $grado_alcolico ); ?> %</td>
				</tr>
				<?php endif; ?>
				<?php if ( $solforosa_totale ) : ?>
				<tr class="elabel-extra">
					<td><?php echo wleu_t( 'solforosa_totale', $lang ); ?></td>
					<td class="elabel-val"><?php echo esc_html( $solforosa_totale ); ?> mg/l</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( ! empty( $raccolta ) ) : ?>
	<div class="elabel-card">
		<h2><?php echo wleu_t( 'raccolta_diff', $lang ); ?></h2>
		<table class="elabel-table elabel-table-raccolta">
			<thead>
				<tr>
					<th><?php echo wleu_t( 'componente', $lang ); ?></th>
					<th><?php echo wleu_t( 'codice', $lang ); ?></th>
					<th><?php echo wleu_t( 'materiale', $lang ); ?></th>
					<th><?php echo wleu_t( 'raccolta', $lang ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $raccolta as $item ) :
					if ( $lang === 'en' ) {
						$r_componente = ( $item['componente_en'] ?? '' ) ?: ( $item['componente'] ?? '' );
						$r_materiale  = ( $item['materiale_en'] ?? '' )  ?: ( $item['materiale'] ?? '' );
						$r_istruzioni = ( $item['istruzioni_en'] ?? '' ) ?: ( $item['istruzioni'] ?? '' );
					} else {
						$r_componente = ( $item['componente'] ?? '' ) ?: ( $item['componente_en'] ?? '' );
						$r_materiale  = ( $item['materiale'] ?? '' )  ?: ( $item['materiale_en'] ?? '' );
						$r_istruzioni = ( $item['istruzioni'] ?? '' ) ?: ( $item['istruzioni_en'] ?? '' );
					}
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
		<p class="elabel-note"><?php echo wleu_t( 'verifica_comune', $lang ); ?></p>
	</div>
	<?php endif; ?>

	<?php
	wleu_html_footer( $lang );
}

/**
 * Render the index page listing all products with digital labels.
 *
 * @param string $lang Language code ('it' or 'en').
 */
function wleu_render_elabel_index( $lang = 'it' ) {

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
				'url'  => home_url( '/' . $product->post_name . '-elabel-' . $year . '/' ),
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
		<h2><?php echo wleu_t( 'indice_vini', $lang ); ?></h2>
		<p><?php echo wleu_t( 'reg_disclaimer', $lang ); ?></p>

		<?php if ( empty( $entries ) ) : ?>
			<p><?php echo wleu_t( 'nessun_vino', $lang ); ?></p>
		<?php else : ?>
			<ul class="elabel-index">
				<?php foreach ( $entries as $entry ) : ?>
					<?php
					$lang_param = ( $lang !== 'en' ) ? '?lang=' . $lang : '';
					$qr_url     = $entry['url'] . '?qr=pdf';
					?>
					<li>
						<a href="<?php echo esc_url( $entry['url'] . $lang_param ); ?>"><?php echo esc_html( $entry['name'] ); ?></a>
						<a href="<?php echo esc_url( $qr_url ); ?>" class="elabel-qr-btn" download><?php echo wleu_t( 'scarica_qr', $lang ); ?></a>
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
 * @param string $title Page <title>.
 * @param string $lang  Language code ('it' or 'en').
 */
function wleu_html_header( $title, $lang = 'en' ) {
	$title    = esc_html( $title );
	$lang_attr = esc_attr( $lang );
	$t_label  = wleu_t( 'etichetta_digitale', $lang );

	// Build language toggle URLs (Pro only).
	$current_path = strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), '?' );
	$en_url  = $current_path;
	$alt_lang = get_option( 'wleu_second_language_code', 'it' );
	$alt_url  = $current_path . '?lang=' . $alt_lang;
	$show_lang_toggle = wleu_is_pro() && get_option( 'wleu_second_language_enabled', '' ) === 'yes';
	?>
<!DOCTYPE html>
<html lang="<?php echo $lang_attr; ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo $title; ?> &mdash; <?php echo $t_label; ?></title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

		html {
			scroll-behavior: smooth;
			-webkit-text-size-adjust: 100%;
		}

		body {
			font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			font-size: 16px;
			line-height: 1.5;
			color: #1a1a1a;
			background: #f5f5f5;
			padding: 16px;
		}

		.elabel-wrap {
			max-width: 480px;
			margin: 0 auto;
		}

		/* Language toggle */
		.elabel-lang {
			text-align: center;
			margin-bottom: 12px;
			font-size: 0.8rem;
		}

		.elabel-lang a {
			color: #888;
			text-decoration: none;
			padding: 2px 6px;
		}

		.elabel-lang a:hover {
			color: #1a1a1a;
		}

		.elabel-lang .active {
			color: #1a1a1a;
			font-weight: 700;
		}

		.elabel-lang .sep {
			color: #ccc;
		}

		.elabel-page-title {
			text-align: center;
			font-size: 1.15rem;
			font-weight: 700;
			margin-bottom: 4px;
		}

		.elabel-wine-name {
			text-align: center;
			font-size: 1rem;
			font-weight: 400;
			color: #555;
			margin-bottom: 16px;
		}

		/* Cards */
		.elabel-card {
			background: #fff;
			border: 1px solid #e0e0e0;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 16px;
		}

		.elabel-card h2 {
			font-size: 1.1rem;
			font-weight: 700;
			text-align: center;
			margin-bottom: 12px;
			border-bottom: 2px solid #e0e0e0;
			padding-bottom: 8px;
		}

		/* Ingredients section header with flag */
		.elabel-section-header {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			margin-bottom: 12px;
			border-bottom: 2px solid #e0e0e0;
			padding-bottom: 8px;
		}

		.elabel-section-header h2 {
			margin-bottom: 0;
			border-bottom: none;
			padding-bottom: 0;
		}

		.elabel-flag {
			width: 28px;
			height: 20px;
			flex-shrink: 0;
		}

		/* Ingredients */
		.elabel-ingredients p {
			margin-bottom: 6px;
			text-align: center;
			font-size: 0.95rem;
		}

		.elabel-allergen {
			margin-top: 10px;
			color: #b71c1c;
		}

		/* Nutritional table */
		.elabel-per {
			text-align: center;
			font-size: 0.85rem;
			color: #666;
			margin-bottom: 12px;
		}

		.elabel-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 0.95rem;
		}

		.elabel-table td,
		.elabel-table th {
			padding: 8px 10px;
			border-bottom: 1px solid #eee;
		}

		.elabel-table tr:last-child td {
			border-bottom: none;
		}

		.elabel-val {
			text-align: right;
			white-space: nowrap;
		}

		.elabel-sub td:first-child {
			padding-left: 24px;
			font-size: 0.9rem;
			color: #555;
		}

		.elabel-extra td {
			border-top: 1px solid #ccc;
		}

		/* Raccolta table */
		.elabel-table-raccolta th {
			text-align: left;
			font-size: 0.8rem;
			color: #888;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			border-bottom: 2px solid #e0e0e0;
		}

		.elabel-table-raccolta td {
			font-size: 0.9rem;
		}

		@media (max-width: 480px) {
			.elabel-table-raccolta thead { display: none; }

			.elabel-table-raccolta tr {
				display: block;
				padding: 10px 0;
				border-bottom: 1px solid #eee;
				text-align: center;
			}

			.elabel-table-raccolta tr:last-child { border-bottom: none; }

			.elabel-table-raccolta td {
				display: block;
				border-bottom: none;
				padding: 2px 0;
				text-align: center;
			}

			.elabel-table-raccolta td:first-child {
				font-weight: 700;
				font-size: 0.95rem;
			}

			.elabel-table-raccolta td:nth-child(2),
			.elabel-table-raccolta td:nth-child(3) {
				display: inline;
				font-size: 0.85rem;
				color: #555;
			}

			.elabel-table-raccolta td:nth-child(2)::after {
				content: " — ";
			}

			.elabel-table-raccolta td:last-child {
				font-size: 0.8rem;
				color: #888;
				font-style: italic;
			}
		}

		.elabel-note {
			text-align: center;
			font-size: 0.8rem;
			color: #888;
			margin-top: 12px;
			font-style: italic;
		}

		/* Index */
		.elabel-index {
			list-style: none;
			margin-top: 12px;
		}

		.elabel-index li {
			padding: 10px 0;
			border-bottom: 1px solid #eee;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
		}

		.elabel-index li:last-child {
			border-bottom: none;
		}

		.elabel-index a {
			color: #2563eb;
			text-decoration: underline;
			font-weight: 500;
		}

		.elabel-index a:hover {
			color: #1d4ed8;
		}

		.elabel-qr-btn {
			font-size: 0.75rem;
			color: #fff !important;
			background: #555;
			padding: 4px 10px;
			border-radius: 4px;
			text-decoration: none !important;
			white-space: nowrap;
			flex-shrink: 0;
		}

		.elabel-qr-btn:hover {
			background: #333;
		}

		/* Footer */
		.elabel-footer {
			text-align: center;
			font-size: 0.75rem;
			color: #999;
			margin-top: 24px;
			padding-bottom: 24px;
		}

		.elabel-powered {
			margin-top: 8px;
			font-size: 0.7rem;
		}

		.elabel-powered a {
			color: #999;
			text-decoration: none;
		}

		.elabel-powered a:hover {
			color: #666;
		}
	</style>
</head>
<body>
<div class="elabel-wrap">
	<?php if ( $show_lang_toggle ) : ?>
	<div class="elabel-lang">
		<a href="<?php echo esc_url( $en_url ); ?>"<?php echo $lang === 'en' ? ' class="active"' : ''; ?>>EN</a>
		<span class="sep">|</span>
		<a href="<?php echo esc_url( $alt_url ); ?>"<?php echo $lang !== 'en' ? ' class="active"' : ''; ?>><?php echo esc_html( strtoupper( $alt_lang ) ); ?></a>
	</div>
	<?php endif; ?>
	<h1 class="elabel-page-title"><?php echo $title; ?></h1>
	<?php
}

/**
 * Output the closing HTML tags and regulation footer.
 *
 * @param string $lang Language code ('it' or 'en').
 */
function wleu_html_footer( $lang = 'it' ) {
	$show_credit = true;

	// In Pro, respect the setting. In Free, always show.
	if ( wleu_is_pro() ) {
		$show_credit = get_option( 'wleu_show_footer_credit', 'yes' ) === 'yes';
	}
	?>
	<div class="elabel-footer">
		<?php echo wleu_t( 'reg_footer', $lang ); ?>
		<?php if ( $show_credit ) : ?>
			<div class="elabel-powered"><?php echo wleu_t( 'powered_by', $lang ); ?></div>
		<?php endif; ?>
	</div>
</div>
</body>
</html>
	<?php
}

/**
 * Return the inline SVG for the EU flag marker.
 *
 * @return string SVG markup.
 */
function wleu_flag_svg() {
	return '<svg class="elabel-flag" viewBox="0 0 3 2" xmlns="http://www.w3.org/2000/svg"><rect width="1" height="2" fill="#009246"/><rect x="1" width="1" height="2" fill="#fff"/><rect x="2" width="1" height="2" fill="#CE2B37"/></svg>';
}
