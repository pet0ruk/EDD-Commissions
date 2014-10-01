<?php

/**
 * Shows upgrade notices
 *
 * @access      private
 * @since       2.8
 * @return      void
*/

function eddc_upgrade_notices() {

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if( ! empty( $_GET['page'] ) && 'edd-upgrades' == $_GET['page'] ) {
		return;
	}

	$version = get_option( 'eddc_version' );

	if ( ! $version || version_compare( $version, '2.8', '<' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'Easy Digital Downloads needs to upgrade the commission records, click %shere%s to start the upgrade.', 'eddc' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'index.php?page=edd-upgrades&edd-upgrade=upgrade_commission_statuses' ) ) . '">',
			'</a>'
		);
	}

}
add_action( 'admin_notices', 'eddc_upgrade_notices' );

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

/**
 * Upgrades all commission records to use a taxonomy for tracking the status of the commission
 *
 * @since 2.8
 * @return void
 */
function eddc_upgrade_commission_statuses() {

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	define( 'EDDC_DOING_UPGRADES', true );

	ignore_user_abort( true );

	if ( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	$step = isset( $_GET['step'] ) ? absint( $_GET['step'] )  : 1;

	$args = array(
		'posts_per_page' => 20,
		'paged'          => $step,
		'status'         => 'any',
		'order'          => 'ASC',
		'post_type'      => 'edd_commission',
		'fields'         => 'ids'
	);

	$commissions = get_posts( $args );

	if( $commissions ) {

		// Commissions found so upgrade them

		foreach( $commissions as $commission_id ) {
			
			$status = get_post_meta( $commission_id, '_commission_status', true );
			if( 'paid' !== $status ) {
				$status = 'unpaid';
			}
			eddc_set_commission_status( $commission_id, $status );

		}

		$step++;

		$redirect = add_query_arg( array(
			'page'        => 'edd-upgrades',
			'edd-upgrade' => 'upgrade_commission_statuses',
			'step'        => $step
		), admin_url( 'index.php' ) );

		wp_redirect( $redirect ); exit;

	} else {

		// No more commissions found, finish up

		update_option( 'eddc_version', EDD_COMMISSIONS_VERSION );

		// No more commissions found, finish up
		wp_redirect( admin_url() ); exit;
	}

}
add_action( 'edd_upgrade_commission_statuses', 'eddc_upgrade_commission_statuses' );
