<?php

$slug = CFFGC_SLUG;

CSF_Setup::createOptions( $slug, array(
  'framework_title' => __( 'Fluent Form Gateway Charges', 'cff_gc' ),

  'menu_title'  => __( 'Fluent Form Gateway Charges Settings', 'cff_gc' ),
  'menu_slug'   => 'cff_gc',
  'menu_type'   => 'submenu',
  'menu_parent' => 'fluent_forms',
  'footer_text' => sprintf( __( 'Fluent Form Gateway Charges %s', 'cff_gc' ) , '1.0.0' ),
  'theme'       => 'light',
) );

$credentials_global_fields = array(
  array(
    'type'    => 'subheading',
    'content' => 'Charges',
  ),
  array(
    'id'    => 'minimum_fee',
    'type'  => 'number',
    'title' => __( 'Minimum Fee', 'cff_gc' ),
    'desc'  => __( 'Enter the minimum fee in sens.', 'cff_gc' ),
    'help'  => __( 'This minimum fee will be applied if the combination of fixed and percentage charges does not exceed minimum fee.', 'cff_gc' ),
  ),
  array(
    'id'    => 'variable_rate',
    'type'  => 'number',
    'title' => __( 'Variable Fee', 'cff_gc' ),
    'desc'  => __( 'Enter the variable fee in percentage. Set 2200 for 2.2%.', 'cff_gc' ),
    'help'  => __( 'This variable rate fee will be applied on the total amount.', 'cff_gc' ),
  ),
  array(
    'id'    => 'fixed_rate',
    'type'  => 'number',
    'title' => __( 'Fixed Fee', 'cff_gc' ),
    'desc'  => __( 'Enter the fixed fee in sens.', 'cff_gc' ),
    'help'  => __( 'This fixed rate fee will be applied on the total amount.', 'cff_gc' ),
  ));

CSF_Setup::createSection( $slug, array(
  'id'    => 'global-configuration',
  'title' => __( 'Global Configuration', 'cff_gc' ),
  'icon'  => 'fa fa-home',
) );

CSF_Setup::createSection( $slug, array(
  'parent'      => 'global-configuration',
  'id'          => 'cffgc',
  'title'       => __( 'Fluent Form', 'cff_gc' ),
  'description' => __( 'Configure gateway charges.', 'cff_gc' ),
  'fields'      => $credentials_global_fields,
) );