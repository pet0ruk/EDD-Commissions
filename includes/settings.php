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
		)
	);

	return array_merge( $settings, $commission_settings );

}
add_filter( 'edd_settings_extensions', 'eddc_settings' );