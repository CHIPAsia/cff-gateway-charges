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

define( 'CFFGC_SLUG', 'cff_gc' );

include plugin_dir_path( __FILE__ ) . 'includes/codestar-framework/classes/setup.class.php';
include plugin_dir_path( __FILE__ ) . 'includes/admin/global-settings.php';

add_action( 'plugins_loaded', 'load_chip_gateway_charges_form_settings' );

add_filter( 'ff_chip_create_purchase_params', 'cff_inject_gateway_charges', 10, 4 );
// add_filter( 'ff_chip_handle_paid_data', 'cff_reverse_total_amount', 10, 4 );
add_action( 'ff_chip_after_purchase_create', 'cff_inject_order_item', 10, 4 );

function cff_minimum_fee( $form_id ) {
  $options  = cff_get_settings( $form_id );
  if ( !isset( $options['minimum_fee'] ) ) {
    return 100; // RM 1.00
  }
  return $options['minimum_fee'];
}

function cff_variable_rate( $form_id ) {
  $options  = cff_get_settings( $form_id );
  if ( !isset( $options['variable_rate'] ) ) {
    return 0.022; // 2.2%
  }
  return $options['variable_rate'] / 100000;
}

function cff_fixed_rate( $form_id ) {
  $options  = cff_get_settings( $form_id );
  if ( !isset( $options['fixed_rate'] ) ) {
    return 100; // RM 1.00
  }
  return $options['fixed_rate'];
}

function cff_product_title() {
  return 'Processing Fee';
}

function cff_get_settings( $form_id ) {
  $options  = get_option( CFFGC_SLUG, array() );
  $postfix  = '';
  $form_cid = 'form_customize_' . $form_id;

  if ( array_key_exists( $form_cid, $options ) AND $options[$form_cid] ) {
    $postfix = "_$form_id";
  }

  $array = [];

  if ( isset( $options['minimum_fee' . $postfix] ) AND $options['minimum_fee' . $postfix] != '' ) {
    $array['minimum_fee'] = $options['minimum_fee' . $postfix];
  }

  if ( isset( $options['variable_rate' . $postfix] ) AND $options['variable_rate' . $postfix] != '' ) {
    $array['variable_rate'] = $options['variable_rate' . $postfix];
  }

  if ( isset( $options['fixed_rate' . $postfix] ) AND $options['fixed_rate' . $postfix] != '' ) {
    $array['fixed_rate'] = $options['fixed_rate' . $postfix];
  }

  return $array;
}

function cff_inject_gateway_charges( $params, $transaction, $submission, $form ) {

  $calculated_fee = round( $params['purchase']['products'][0]['price'] * cff_variable_rate( $form->id ) + cff_fixed_rate( $form->id ) );

  if ( $calculated_fee < cff_minimum_fee( $form->id ) ) {
    $calculated_fee = cff_minimum_fee( $form->id );
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

function cff_inject_order_item( $transaction, $submission, $form, $payment ) {
  $item_price = 0;

  foreach( $payment['purchase']['products'] as $product ) {
    if ( $product['name'] == cff_product_title() ) {
      $item_price = intval( $product['price'] );
      break;
    }
  }

  $item = array([
    'type' => 'single',
    'form_id' => $form->id,
    'quantity' => '1',
    'created_at' => current_time('mysql'),
    'updated_at' => current_time('mysql'),
    'parent_holder' => 'payment_input',
    'item_name' => cff_product_title(),
    'item_price' => $item_price,
    'line_total' => $item_price,
    'submission_id' => $submission->id,
  ]);

  wpFluent()->table('fluentform_order_items')->insert($item);

  wpFluent()->table('fluentform_submissions')
            ->where('id', $submission->id)
            ->update(['payment_total' => intval($payment['purchase']['total'])]);

  wpFluent()->table('fluentform_transactions')
            ->where('id', $transaction->id)
            ->update(['payment_total' => intval($payment['purchase']['total'])]);
}

function load_chip_gateway_charges_form_settings() {
  include plugin_dir_path( __FILE__ ) . 'includes/admin/form-settings.php';
}
