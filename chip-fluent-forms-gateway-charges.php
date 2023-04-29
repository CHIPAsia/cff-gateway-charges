<?php

/**
 *
 * Plugin Name: CHIP FluentForms Gateway Charges
 * Description: This to inject gateway charges to total amount
 * Version: 1.0.0
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

add_filter( 'ff_chip_create_purchase_params', 'cff_inject_gateway_charges', 10, 4 );
// add_filter( 'ff_chip_handle_paid_data', 'cff_reverse_total_amount', 10, 4 );

function cff_minimum_fee() {
  return 150; // RM 1.50
}

function cff_variable_rate() {
  return 0.022; // 2.2%
}

function cff_fixed_rate() {
  return 100; // RM 1.00
}

function cff_product_title() {
  return 'Processing Fee';
}

function cff_inject_gateway_charges( $params, $transaction, $submission, $form ) {

  $calculated_fee = round( $params['purchase']['products'][0]['price'] * cff_variable_rate() + cff_fixed_rate() );

  if ( $calculated_fee < cff_minimum_fee() ) {
    $calculated_fee = cff_minimum_fee();
  }

  $params['purchase']['products'][] = array(
    'name'     => cff_product_title(),
    'price'    => $calculated_fee,
  );

  return $params;
}

function cff_reverse_total_amount( $transaction_data, $submission, $transaction, $vendorTransaction ) {
  foreach( $vendorTransaction['purchase']['products'] as $product ) {
    if ( $product['name'] != cff_product_title() ) {
      $transaction_data['payment_total'] = intval( $product['price'] );
      break;
    }
  }
  
  return $transaction_data;
}
