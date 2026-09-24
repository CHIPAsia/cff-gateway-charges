<?php
/**
 * Per-form settings for the gateway charges.
 *
 * @package CHIPGatewayCharges
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

$slug = CFFGC_SLUG;

/**
 * Builds the per-form charge fields.
 *
 * @param object $form The Fluent Forms form.
 * @return array The field definitions.
 */
function ff_chip_gateway_charges_form_fields( $form ) {
	$form_fields = array(
		array(
			'id'    => 'form_customize_' . $form->id,
			'type'  => 'switcher',
			'title' => __( 'Customization', 'cff_gc' ),
			/* translators: 1: Form ID, 2: Form Title. */
			'desc'  => sprintf( __( 'Form ID: <strong>#%1$s</strong>. Form Title: <strong>%2$s</strong>', 'cff_gc' ), $form->id, $form->title ),
			/* translators: %s: Form ID. */
			'help'  => sprintf( __( 'This to enable customization per form-basis for form: #%s', 'cff_gc' ), $form->id ),
		),
		array(
			'type'       => 'subheading',
			'content'    => __( 'Charges', 'cff_gc' ),
			'dependency' => array( array( 'form_customize_' . $form->id, '==', 'true' ) ),
		),
		array(
			'id'         => 'minimum_fee_' . $form->id,
			'type'       => 'number',
			'title'      => __( 'Minimum Fee', 'cff_gc' ),
			'desc'       => __( 'Enter the minimum fee in sens. Set 100 for RM 1. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
			'help'       => __( 'This minimum fee will be applied if the combination of fixed and percentage charges does not exceed minimum fee. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
			'dependency' => array( array( 'form_customize_' . $form->id, '==', 'true' ) ),
		),
		array(
			'id'         => 'variable_rate_' . $form->id,
			'type'       => 'number',
			'title'      => __( 'Variable Fee', 'cff_gc' ),
			'desc'       => __( 'Enter the variable fee in percentage. Set 2200 for 2.2%. Leave blank for default 2.2% or set 0 for 0.00%.', 'cff_gc' ),
			'help'       => __( 'This variable rate fee will be applied on the total amount. Leave blank for default 2.2% or set 0 for 0.00%.', 'cff_gc' ),
			'dependency' => array( array( 'form_customize_' . $form->id, '==', 'true' ) ),
		),
		array(
			'id'         => 'fixed_rate_' . $form->id,
			'type'       => 'number',
			'title'      => __( 'Fixed Fee', 'cff_gc' ),
			'desc'       => __( 'Enter the fixed fee in sens. Set 100 for RM 1. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
			'help'       => __( 'This fixed rate fee will be applied on the total amount. Leave blank for default RM 1 or set 0 for RM 0.00.', 'cff_gc' ),
			'dependency' => array( array( 'form_customize_' . $form->id, '==', 'true' ) ),
		),
	);

	return $form_fields;
}

CHIP_FF_Settings::createSection(
	$slug,
	array(
		'id'    => 'form-configuration',
		'title' => __( 'Form Configuration', 'cff_gc' ),
		'icon'  => 'fa fa-gear',
	)
);

$all_forms_query = wpFluent()->table( 'fluentform_forms' )
	->select( array( 'id', 'title' ) )
	->orderBy( 'id' )
	->limit( 500 )
	->get();

foreach ( $all_forms_query as $form ) {

	CHIP_FF_Settings::createSection(
		$slug,
		array(
			'parent'      => 'form-configuration',
			'id'          => 'form-id-' . $form->id,
			/* translators: 1: Form ID, 2: Form Title truncated to 15 characters. */
			'title'       => sprintf( __( 'Form #%1$s - %2$s', 'cff_gc' ), $form->id, substr( $form->title, 0, 15 ) ),
			/* translators: 1: Form ID, 2: Form Title. */
			'description' => sprintf( __( 'Configuration for Form #%1$s - %2$s', 'cff_gc' ), $form->id, $form->title ),
			'fields'      => ff_chip_gateway_charges_form_fields( $form ),
		)
	);
}
