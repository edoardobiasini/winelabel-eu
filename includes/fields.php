<?php
/**
 * Carbon Fields definitions for WineLabel EU (Lite).
 *
 * Two containers:
 * 1. Product/Wine-level: master toggle (shared across vintages)
 * 2. Vintage-level (wleu_vintage CPT): ingredients, nutrition, recycling
 *
 * English-only fields — no bilingual support in the lite version.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', function () {

	// ── Product/Wine Container (shared across vintages) ──────
	Container::make( 'post_meta', __( 'WineLabel EU', 'winelabel-eu' ) )
		->where( 'post_type', '=', wleu_product_post_type() )
		->add_fields( [
			Field::make( 'checkbox', 'elabel_enabled', __( 'Enable digital label', 'winelabel-eu' ) )
				->set_option_value( 'yes' )
				->set_help_text( wleu_uses_woocommerce()
					? __( 'Activate to make the digital label page visible for this product.', 'winelabel-eu' )
					: __( 'Activate to make the digital label page visible for this wine.', 'winelabel-eu' )
				),
		] );

	// ── Ingredients fields ────────────────────────────────────
	$ingredients = [];

	// Raw material
	$ingredients[] = Field::make( 'text', 'elabel_materia_prima_en', __( 'Raw material', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. grapes', 'winelabel-eu' ) )
		->set_default_value( 'grapes' );

	// Acidity regulators
	$ingredients[] = Field::make( 'text', 'elabel_correttori_acidita_en', __( 'Acidity regulators', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. L-tartaric acid (E334), L-malic acid (E296)', 'winelabel-eu' ) );

	// Stabilizers
	$ingredients[] = Field::make( 'text', 'elabel_stabilizzanti_en', __( 'Stabilizers', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. citric acid (E330)', 'winelabel-eu' ) );

	// Antioxidants
	$ingredients[] = Field::make( 'text', 'elabel_antiossidanti_en', __( 'Antioxidants', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. sulfur dioxide (E220)', 'winelabel-eu' ) );

	// Other ingredients
	$ingredients[] = Field::make( 'text', 'elabel_altri_ingredienti_en', __( 'Other ingredients', 'winelabel-eu' ) );

	$ingredients[] = Field::make( 'checkbox', 'elabel_contiene_solfiti', __( 'Contains sulfites', 'winelabel-eu' ) )
		->set_option_value( 'yes' )
		->set_default_value( 'yes' );

	// ── Waste Sorting fields ──────────────────────────────────
	$raccolta_fields = [];

	$raccolta_fields[] = Field::make( 'text', 'componente_en', __( 'Component', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. Bottle, Cork, Capsule, Label', 'winelabel-eu' ) )
		->set_width( 25 );

	$raccolta_fields[] = Field::make( 'text', 'codice', __( 'Code', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. GL 71, FOR 51, ALU 41, PAP 22', 'winelabel-eu' ) )
		->set_width( 15 );

	$raccolta_fields[] = Field::make( 'text', 'materiale_en', __( 'Material', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. Glass, Cork, Aluminium, Paper', 'winelabel-eu' ) )
		->set_width( 25 );

	$raccolta_fields[] = Field::make( 'text', 'istruzioni_en', __( 'Collection', 'winelabel-eu' ) )
		->set_help_text( __( 'e.g. Glass recycling, General waste', 'winelabel-eu' ) )
		->set_width( 25 );

	// ── Vintage Container ────────────────────────────────────
	Container::make( 'post_meta', __( 'Vintage Data', 'winelabel-eu' ) )
		->where( 'post_type', '=', 'wleu_vintage' )

		->add_tab( __( 'General', 'winelabel-eu' ), [
			Field::make( 'text', 'elabel_annata', __( 'Year (YY)', 'winelabel-eu' ) )
				->set_required( true )
				->set_width( 30 )
				->set_help_text( __( 'Last 2 digits of the vintage year, e.g. 24, 25', 'winelabel-eu' ) ),
		] )

		->add_tab( __( 'Ingredients', 'winelabel-eu' ), $ingredients )

		->add_tab( __( 'Nutritional Values', 'winelabel-eu' ), [
			Field::make( 'text', 'elabel_energia_kj', __( 'Energy (kJ)', 'winelabel-eu' ) )
				->set_attribute( 'type', 'number' )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_energia_kcal', __( 'Energy (kcal)', 'winelabel-eu' ) )
				->set_attribute( 'type', 'number' )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_grassi', __( 'Fat (g)', 'winelabel-eu' ) )
				->set_default_value( '0' )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_grassi_saturi', __( 'of which Saturated Fat (g)', 'winelabel-eu' ) )
				->set_default_value( '0' )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_carboidrati', __( 'Carbohydrates (g)', 'winelabel-eu' ) )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_zuccheri', __( 'of which Sugars (g)', 'winelabel-eu' ) )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_proteine', __( 'Protein (g)', 'winelabel-eu' ) )
				->set_default_value( '0' )
				->set_width( 25 ),

			Field::make( 'text', 'elabel_sale', __( 'Salt (g)', 'winelabel-eu' ) )
				->set_default_value( '0' )
				->set_width( 25 ),

			Field::make( 'separator', 'elabel_sep_extra', __( 'Wine-specific values', 'winelabel-eu' ) ),

			Field::make( 'text', 'elabel_acidita_totale', __( 'Total acidity (g/l)', 'winelabel-eu' ) )
				->set_width( 33 ),

			Field::make( 'text', 'elabel_grado_alcolico', __( 'Alcohol content (%)', 'winelabel-eu' ) )
				->set_width( 33 ),

			Field::make( 'text', 'elabel_solforosa_totale', __( 'Total Sulfur Dioxide (mg/l)', 'winelabel-eu' ) )
				->set_width( 33 ),
		] )

		->add_tab( __( 'Waste Sorting', 'winelabel-eu' ), [
			Field::make( 'complex', 'elabel_raccolta', __( 'Packaging components', 'winelabel-eu' ) )
				->set_help_text( __( 'Add each packaging component with its code and material.', 'winelabel-eu' ) )
				->add_fields( $raccolta_fields )
				->set_header_template( '<%- componente_en %> — <%- codice %>' ),
		] );
} );
