<?php
/**
 * Plugin Name: CHIP FluentForms Gateway Charges
 * Plugin URI: https://github.com/CHIPAsia/cff-gateway-charges
 * Description: Adds CHIP gateway charges to the Fluent Forms payment total.
 * Version: 1.0.0
 * Author: Chip In Sdn Bhd
 * Author URI: https://www.chip-in.asia
 * Requires PHP: 7.4
 * Requires at least: 6.1
 * Text Domain: cff_gc
 *
 * Copyright: © 2025 CHIP
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package CHIPGatewayCharges
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

define( 'CFFGC_SLUG', 'cff_gc' );
define( 'CFFGC_MODULE_VERSION', 'v1.0.0' );

require plugin_dir_path( __FILE__ ) . 'includes/class-chip-ff-settings.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-chip-ff-settings-page.php';
require plugin_dir_path( __FILE__ ) . 'includes/admin/global-settings.php';

/**
 * Builds the settings page once every settings file has registered its
 * sections.
 *
 * Building the page earlier would snapshot an empty section list and drop
 * every field.
 *
 * @return void
 */
function cff_gc_build_settings_page() {
	CHIP_FF_Settings::init_pages();
}

/**
 * Publishes the plugin version to the settings framework.
 *
 * Used to version the settings assets for cache busting.
 *
 * @return void
 */
function cff_gc_set_settings_asset_version() {
	CHIP_FF_Settings::$version = CFFGC_MODULE_VERSION;
}

add_action( 'init', 'cff_gc_build_settings_page', 100 );
add_action( 'init', 'cff_gc_set_settings_asset_version', 1 );

add_filter( 'ff_chip_create_purchase_params', 'cff_inject_gateway_charges', 10, 4 );
// Note: ff_chip_handle_paid_data is intentionally not registered; see cff_reverse_total_amount().
add_action( 'ff_chip_after_purchase_create', 'cff_inject_order_item', 10, 4 );

/**
 * Gets the minimum fee for a form, in cents.
 *
 * @param int|string $form_id The form id.
 * @return int Minimum fee in cents.
 */
function cff_minimum_fee( $form_id ) {
	$options = cff_get_settings( $form_id );
	if ( ! isset( $options['minimum_fee'] ) ) {
		// RM 1.00.
		return 100;
	}
	return $options['minimum_fee'];
}

/**
 * Gets the variable fee rate for a form, as a fraction.
 *
 * @param int|string $form_id The form id.
 * @return float Variable rate, e.g. 0.022 for 2.2%.
 */
function cff_variable_rate( $form_id ) {
	$options = cff_get_settings( $form_id );
	if ( ! isset( $options['variable_rate'] ) ) {
		// 2.2%.
		return 0.022;
	}
	return $options['variable_rate'] / 100000;
}

/**
 * Gets the fixed fee for a form, in cents.
 *
 * @param int|string $form_id The form id.
 * @return int Fixed fee in cents.
 */
function cff_fixed_rate( $form_id ) {
	$options = cff_get_settings( $form_id );
	if ( ! isset( $options['fixed_rate'] ) ) {
		// RM 1.00.
		return 100;
	}
	return $options['fixed_rate'];
}

/**
 * The name used for the gateway charge line item.
 *
 * @return string
 */
function cff_product_title() {
	return 'Processing Fee';
}

/**
 * Resolves the charge settings for a form.
 *
 * A form with customization enabled reads its own suffixed keys and falls back
 * to the global value for any key it does not set. Comparison against the empty
 * string is strict on purpose: a configured fee of 0 is a valid value ("set 0
 * for RM 0.00"), and loose comparison treats 0 as empty on PHP 7.4.
 *
 * @param int|string $form_id The form id.
 * @return array Resolved settings, keyed by minimum_fee|variable_rate|fixed_rate.
 */
function cff_get_settings( $form_id ) {
	$options  = get_option( CFFGC_SLUG, array() );
	$postfix  = '';
	$form_cid = 'form_customize_' . $form_id;

	if ( array_key_exists( $form_cid, $options ) && $options[ $form_cid ] ) {
		$postfix = "_$form_id";
	}

	$array = array();

	if ( isset( $options[ 'minimum_fee' . $postfix ] ) && '' !== $options[ 'minimum_fee' . $postfix ] ) {
		$array['minimum_fee'] = $options[ 'minimum_fee' . $postfix ];
	}

	if ( isset( $options[ 'variable_rate' . $postfix ] ) && '' !== $options[ 'variable_rate' . $postfix ] ) {
		$array['variable_rate'] = $options[ 'variable_rate' . $postfix ];
	}

	if ( isset( $options[ 'fixed_rate' . $postfix ] ) && '' !== $options[ 'fixed_rate' . $postfix ] ) {
		$array['fixed_rate'] = $options[ 'fixed_rate' . $postfix ];
	}

	return $array;
}

/**
 * Adds the gateway charge as an extra product on the purchase.
 *
 * @param array  $params      The purchase params sent to CHIP.
 * @param object $transaction The Fluent Forms transaction.
 * @param object $submission  The Fluent Forms submission.
 * @param object $form        The Fluent Forms form.
 * @return array The purchase params with the charge appended.
 */
function cff_inject_gateway_charges( $params, $transaction, $submission, $form ) {
	$calculated_fee = round( $params['purchase']['products'][0]['price'] * cff_variable_rate( $form->id ) + cff_fixed_rate( $form->id ) );

	if ( $calculated_fee < cff_minimum_fee( $form->id ) ) {
		$calculated_fee = cff_minimum_fee( $form->id );
	}

	$params['purchase']['products'][] = array(
		'name'  => cff_product_title(),
		'price' => $calculated_fee,
	);

	return $params;
}

/**
 * Restores the payment total to the amount the payer entered.
 *
 * The gateway charge is added to what CHIP collects, so the recorded total has
 * to be reversed back to the product amount. Kept for the
 * ff_chip_handle_paid_data filter, which is currently not registered.
 *
 * @param array  $transaction_data   The transaction data to adjust.
 * @param object $submission         The Fluent Forms submission.
 * @param object $transaction        The Fluent Forms transaction.
 * @param array  $vendor_transaction The CHIP purchase payload.
 * @return array The adjusted transaction data.
 */
function cff_reverse_total_amount( $transaction_data, $submission, $transaction, $vendor_transaction ) {
	foreach ( $vendor_transaction['purchase']['products'] as $product ) {
		if ( cff_product_title() !== $product['name'] ) {
			$transaction_data['payment_total'] = intval( $product['price'] );
			break;
		}
	}

	return $transaction_data;
}

/**
 * Records the gateway charge as an order item and updates the totals.
 *
 * The charge is stored separately so the submission and transaction totals
 * reflect what the payer was actually charged.
 *
 * @param object $transaction The Fluent Forms transaction.
 * @param object $submission  The Fluent Forms submission.
 * @param object $form        The Fluent Forms form.
 * @param array  $payment     The CHIP purchase payload.
 * @return void
 */
function cff_inject_order_item( $transaction, $submission, $form, $payment ) {
	$item_price = 0;

	foreach ( $payment['purchase']['products'] as $product ) {
		if ( cff_product_title() === $product['name'] ) {
			$item_price = intval( $product['price'] );
			break;
		}
	}

	$item = array(
		array(
			'type'          => 'single',
			'form_id'       => $form->id,
			'quantity'      => '1',
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
			'parent_holder' => 'payment_input',
			'item_name'     => cff_product_title(),
			'item_price'    => $item_price,
			'line_total'    => $item_price,
			'submission_id' => $submission->id,
		),
	);

	wpFluent()->table( 'fluentform_order_items' )->insert( $item );

	wpFluent()->table( 'fluentform_submissions' )
			->where( 'id', $submission->id )
			->update( array( 'payment_total' => intval( $payment['purchase']['total'] ) ) );

	wpFluent()->table( 'fluentform_transactions' )
			->where( 'id', $transaction->id )
			->update( array( 'payment_total' => intval( $payment['purchase']['total'] ) ) );
}

/**
 * Registers the per-form settings once Fluent Forms has loaded.
 *
 * @return void
 */
function load_chip_gateway_charges_form_settings() {
	include plugin_dir_path( __FILE__ ) . 'includes/admin/form-settings.php';
}

add_action( 'plugins_loaded', 'load_chip_gateway_charges_form_settings' );
