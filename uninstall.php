<?php
/**
 * Uninstall routine for CHIP FluentForms Gateway Charges.
 *
 * @package CHIPGatewayCharges
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Deletes the plugin options for the current site.
 *
 * The plugin is not loaded during uninstall, so the option name is written out
 * literally rather than read from CFFGC_SLUG.
 *
 * @return void
 */
function cff_gc_uninstall_site() {
	delete_option( 'cff_gc' );
}

if ( is_multisite() ) {
	$cff_gc_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $cff_gc_site_ids as $cff_gc_site_id ) {
		switch_to_blog( $cff_gc_site_id );
		cff_gc_uninstall_site();
		restore_current_blog();
	}
} else {
	cff_gc_uninstall_site();
}
