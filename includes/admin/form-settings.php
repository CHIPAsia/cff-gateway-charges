<?php

$slug = CFFGC_SLUG;

function ff_chip_gateway_charges_form_fields( $form ){
  $form_fields = array(
    array(
      'id'    => 'form_customize_' . $form->id,
      'type'  => 'switcher',
      'title' => sprintf( __( 'Customization', 'cff_gc' ) ),
      'desc'  => sprintf( __( 'Form ID: <strong>#%s</strong>. Form Title: <strong>%s</strong>', 'cff_gc' ), $form->id, $form->title),
      'help'  => sprintf( __( 'This to enable customization per form-basis for form: #%s', 'cff_gc' ), $form->id ),
    ),
    array(
      'type'    => 'subheading',
      'content' => 'Charges',
      'dependency'  => array( ['form_customize_' . $form->id, '==', 'true'] ),
    ),
    array(
      'id'    => 'minimum_fee_' . $form->id,
      'type'  => 'number',
      'title' => __( 'Minimum Fee', 'cff_gc' ),
      'desc'  => __( 'Enter the minimum fee in sens. Set 100 for RM 1. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
      'help'  => __( 'This minimum fee will be applied if the combination of fixed and percentage charges does not exceed minimum fee. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
      'dependency'  => array( ['form_customize_' . $form->id, '==', 'true'] ),
    ),
    array(
      'id'    => 'variable_rate_' . $form->id,
      'type'  => 'number',
      'title' => __( 'Variable Fee', 'cff_gc' ),
      'desc'  => __( 'Enter the variable fee in percentage. Set 2200 for 2.2%. Leave blank for default 2.2% or set 0 for 0.00%.', 'cff_gc' ),
      'help'  => __( 'This variable rate fee will be applied on the total amount. Leave blank for default 2.2% or set 0 for 0.00%.', 'cff_gc' ),
      'dependency'  => array( ['form_customize_' . $form->id, '==', 'true'] ),
    ),
    array(
      'id'    => 'fixed_rate_' . $form->id,
      'type'  => 'number',
      'title' => __( 'Fixed Fee', 'cff_gc' ),
      'desc'  => __( 'Enter the fixed fee in sens. Set 100 for RM 1. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
      'help'  => __( 'This fixed rate fee will be applied on the total amount. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
      'dependency'  => array( ['form_customize_' . $form->id, '==', 'true'] ),
    ));

    return $form_fields;
}

CSF_Setup::createSection( $slug, array(
  'id'    => 'form-configuration',
  'title' => __( 'Form Configuration', 'cff_gc' ),
  'icon'  => 'fa fa-gear'
));

$all_forms_query = wpFluent()->table('fluentform_forms')
  ->select(['id', 'title'])
  ->orderBy('id')
  ->limit(500)
  ->get();

foreach( $all_forms_query as $form ) {

  CSF_Setup::createSection( $slug, array(
    'parent'      => 'form-configuration',
    'id'          => 'form-id-' . $form->id,
    'title'       => sprintf( __( 'Form #%s - %s', 'cff_gc' ), $form->id, substr( $form->title, 0, 15 ) ),
    'description' => sprintf( __( 'Configuration for Form #%s - %s', 'cff_gc' ), $form->id, $form->title ),
    'fields'      => ff_chip_gateway_charges_form_fields( $form ),
  ));
}
