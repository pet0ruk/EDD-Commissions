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
			'id' => 'eddc_header',
			'name' => '<strong>' . __('Commissions', 'eddc') . '</strong>',
			'desc' => '',
			'type' => 'header',
			'size' => 'regular'
		),
		array(
			'id' => 'edd_commissions_default_rate',
			'name' => __('Default rate', 'eddc'),
			'desc' => __('Enter the default rate recipients should receive. This can be overwritten on a per-product basis. 10 = 10%', 'eddc'),
			'type' => 'text',
			'size' => 'small'
		),
		array(
			'id' => 'edd_commissions_autopay_pa',
			'name' => __('Instant Pay Commmissions', 'eddc'),
			'desc' => __('If checked & PayPal Adaptive is gateway, EDD will automatically pay commissions on purchases', 'eddc'),
			'type' => 'checkbox',
			'std' => false
		),
		array(
			'id' => 'edd_commissions_autopay_schedule',
			'name' => __( 'Payment schedule', 'eddc' ),
			'desc' => __( 'Note: Schedule will only work if instant pay is unchecked, and requires PayPal Adaptive', 'eddc' ),
			'type' => 'select',
			'options' => array(
				'weekly'   => __( 'Weekly', 'eddc' ),
				'biweekly' => __( 'Biweekly', 'eddc' ),
				'monthly'  => __( 'Monthly', 'eddc' ),
				'manual'   => __( 'Manual', 'eddc' ),
			)
		)
	);

	return array_merge( $settings, $commission_settings );

}
add_filter('edd_settings_extensions', 'eddc_settings');