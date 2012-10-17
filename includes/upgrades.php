<?php

/**
 * Upgrade all commissions with user ID meta
 *
 * Prior to 1.3 it wasn't possible to query commissions by user ID (dumb)
 *
 * @access      private
 * @since       1.3
 * @return      void
*/

function eddc_upgrade_user_ids() {

	if( get_option( 'eddc_upgraded_user_ids' ) )
		return; // don't perform the upgrade if we have already done it

	$args = array(
		'post_type' => 'edd_commission',
		'posts_per_page' => -1
	);

	$commissions = get_posts( $args );

	if( $commissions ) {
		foreach( $commissions as $commission ) {
			$info = maybe_unserialize( get_post_meta( $commission->ID, '_edd_commission_info', true ) );

			update_post_meta( $commission->ID, '_user_id', $info['user_id'] );
		}
		add_option( 'eddc_upgraded_user_ids', '1' );
	}

}
add_action( 'admin_init', 'eddc_upgrade_user_ids' );