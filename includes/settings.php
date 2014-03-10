<?php

/**
 * Registers the new Commissions options in Extensions
 * *
 * @access      private
 * @since       1.2.1
 * @param 		$settings array the existing plugin settings
 * @return      array
*/

function eddc_settings( $settings ) {

	$commission_settings = array(
		array(
			'id'      => 'eddc_header',
			'name'    => '<strong>' . __( 'Commissions', 'eddc' ) . '</strong>',
			'desc'    => '',
			'type'    => 'header',
			'size'    => 'regular'
		),
		array(
			'id'      => 'edd_commissions_default_rate',
			'name'    => __( 'Default rate', 'eddc' ),
			'desc'    => __( 'Enter the default rate recipients should receive. This can be overwritten on a per-product basis. 10 = 10%', 'eddc' ),
			'type'    => 'text',
			'size'    => 'small'
		),
		array(
			'id'      => 'edd_commissions_calc_base',
			'name'    => __( 'Calculation Base', 'eddc' ),
			'desc'    => __( 'Should commissions be calculated from the subtotal (before taxes and discounts) or from the total purchase amount (after taxes and discounts)? ', 'eddc' ),
			'type'    => 'select',
			'options' => array(
				'subtotal' => __( 'Subtotal (default)', 'eddc' ),
				'total'    => __( 'Total', 'eddc' )
			)
		),
		array(
			'id' => 'edd_commissions_autopay_pa',
			'name' => __('Instant Pay Commmissions', 'eddc'),
			'desc' => sprintf( __('If checked and <a href="%s">PayPal Adaptive Payments</a> gateway is installed, EDD will automatically pay commissions at the time of purchase', 'eddc'), 'https://easydigitaldownloads.com/extensions/paypal-adaptive-payments/' ),
			'type' => 'checkbox'
		),
		/*
		array(
			'id' => 'edd_commissions_autopay_schedule',
			'name' => __( 'Payment schedule', 'eddc' ),
			'desc' => sprintf( __( 'Note: Schedule will only work if Instant Pay is unchecked, and <a href="%s">PayPal Adaptive Payments</a> is installed', 'eddc' ), 'https://easydigitaldownloads.com/extensions/paypal-adaptive-payments/' ),
			'type' => 'select',
			'options' => array(
				'weekly'   => __( 'Weekly', 'eddc' ),
				'biweekly' => __( 'Biweekly', 'eddc' ),
				'monthly'  => __( 'Monthly', 'eddc' ),
				'manual'   => __( 'Manual', 'eddc' ),
			),
			'std' => 'manual'
		)
		*/
	);

	return array_merge( $settings, $commission_settings );

}
add_filter( 'edd_settings_extensions', 'eddc_settings' );
