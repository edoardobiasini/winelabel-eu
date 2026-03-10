<?php
/**
 * QR Code PDF generation for WineLabel EU.
 *
 * Generates a downloadable PDF containing a vector QR code that links
 * to a wine's digital label page for a specific vintage.
 *
 * Pro feature only.
 */

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;
use Dompdf\Dompdf;
use Dompdf\Options as DompdfOptions;

/**
 * Render and serve a PDF with a vector QR code for a product vintage's digital label.
 *
 * @param string $product_slug The WooCommerce product slug.
 * @param string $year         Two-digit vintage year (e.g. '24').
 */
function wleu_render_elabel_qr_pdf( $product_slug, $year = '' ) {

	$products = get_posts( [
		'post_type'      => wleu_product_post_type(),
		'name'           => $product_slug,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	] );

	if ( empty( $products ) ) {
		status_header( 404 );
		echo 'Product not found.';
		return;
	}

	$product    = $products[0];
	$product_id = $product->ID;

	if ( ! carbon_get_post_meta( $product_id, 'elabel_enabled' ) ) {
		status_header( 404 );
		echo 'Label not available.';
		return;
	}

	if ( empty( $year ) ) {
		status_header( 404 );
		echo 'Vintage not specified.';
		return;
	}

	$vintage = wleu_find_vintage( $product_id, $year );
	if ( ! $vintage ) {
		status_header( 404 );
		echo 'Vintage not found.';
		return;
	}

	// Wine name from product title.
	$wine_name = get_the_title( $product_id );
	$wine_name .= ' ' . $year;

	// Use custom base URL if set (Pro setting), otherwise site URL.
	$base_url = get_option( 'wleu_base_url', '' );
	if ( empty( $base_url ) ) {
		$base_url = home_url();
	}
	$label_url = trailingslashit( $base_url ) . $product->post_name . '-winelabel-' . $year . '/';

	// ── Generate QR code as SVG ─────────────────────────────
	$qr_options = new QROptions();
	$qr_options->outputInterface = QRMarkupSVG::class;
	$qr_options->svgAddXmlHeader = false;
	$qr_options->addQuietzone    = true;
	$qr_options->quietzoneSize   = 2;
	$qr_options->scale           = 10;

	$qr_data_uri = ( new QRCode( $qr_options ) )->render( $label_url );

	// ── Build HTML for PDF ──────────────────────────────────
	$wine_name_esc = htmlspecialchars( $wine_name, ENT_QUOTES, 'UTF-8' );
	$label_url_esc = htmlspecialchars( $label_url, ENT_QUOTES, 'UTF-8' );

	$html = <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<style>
	@page {
		size: A5 portrait;
		margin: 20mm;
	}
	body {
		font-family: Helvetica, Arial, sans-serif;
		color: #1a1a1a;
		text-align: center;
		margin: 0;
		padding: 0;
	}
	.title {
		font-size: 18pt;
		font-weight: bold;
		margin-bottom: 6mm;
		margin-top: 10mm;
	}
	.qr-wrap {
		margin: 8mm auto;
		width: 55mm;
		height: 55mm;
	}
	.qr-wrap svg {
		width: 55mm;
		height: 55mm;
	}
	.url {
		font-size: 8pt;
		color: #555;
		word-break: break-all;
		margin-top: 4mm;
		margin-bottom: 10mm;
	}
	.divider {
		border: none;
		border-top: 1px solid #ccc;
		margin: 6mm 20mm;
	}
	.regulation {
		font-size: 7pt;
		color: #aaa;
		margin-top: 8mm;
	}
</style>
</head>
<body>
	<div class="title">{$wine_name_esc}</div>

	<div class="qr-wrap">
		<img src="{$qr_data_uri}" style="width:55mm;height:55mm;">
	</div>

	<div class="url">{$label_url_esc}</div>

	<hr class="divider">

	<div class="regulation">
		Reg. (EU) 2021/2117 &mdash; Art. 119 Reg. (EU) 1308/2013
	</div>
</body>
</html>
HTML;

	// ── Convert to PDF ──────────────────────────────────────
	$upload_dir = wp_upload_dir();
	$dompdf_tmp = $upload_dir['basedir'] . '/winelabel-eu-tmp';
	wp_mkdir_p( $dompdf_tmp );

	$dompdf_options = new DompdfOptions();
	$dompdf_options->set( 'tempDir', $dompdf_tmp );
	$dompdf_options->set( 'fontCache', $dompdf_tmp );
	$dompdf_options->set( 'isRemoteEnabled', true );
	$dompdf_options->set( 'isHtml5ParserEnabled', true );
	$dompdf_options->set( 'isSvgEnabled', true );
	$dompdf_options->set( 'defaultPaperSize', 'A5' );
	$dompdf_options->set( 'defaultPaperOrientation', 'portrait' );

	$dompdf = new Dompdf( $dompdf_options );
	$dompdf->loadHtml( $html );
	$dompdf->setPaper( 'A5', 'portrait' );
	$dompdf->render();

	$filename = sanitize_file_name( $product->post_name . '-' . $year . '-qr.pdf' );

	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Cache-Control: no-store' );

	echo $dompdf->output();
}
