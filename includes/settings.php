<?php

/**
 * Registers the subsection for EDD Settings
 *
 * @since   3.2.5
 * @param  array $sections The sections
 * @return array           Sections with commissions added
 */
function eddc_settings_section_extensions( $sections ) {
	$sections['commissions'] = __( 'Commissions', 'eddc' );
	return $sections;
}

add_filter( 'edd_settings_sections_extensions', 'eddc_settings_section_extensions' );

/**
 * Registers the new Commissions options in Extensions
 *
 * @access      private
 * @since       1.2.1
 * @param       $settings array the existing plugin settings
 * @return      array
*/
function eddc_settings_extensions( $settings ) {

	$calc_options = array(
		'subtotal'      => __( 'Subtotal (default)', 'eddc' ),
		'total'         => __( 'Total with Taxes', 'eddc' ),
		'total_pre_tax' => __( 'Total without Taxes', 'eddc' ),
	);

	$commission_settings = array(
		array(
			'id'      => 'eddc_header',
			'name'    => '<strong>' . __( 'Commissions Settings', 'eddc' ) . '</strong>',
			'desc'    => '',
			'type'    => 'header',
			'size'    => 'regular',
		),
		array(
			'id'      => 'edd_commissions_default_rate',
			'name'    => __( 'Default rate', 'eddc' ),
			'desc'    => __( 'Enter the default rate recipients should receive. This can be overwritten on a per-product basis. 10 = 10%', 'eddc' ),
			'type'    => 'text',
			'size'    => 'small',
		),
		array(
			'id'      => 'edd_commissions_calc_base',
			'name'    => __( 'Calculation Base', 'eddc' ),
			'desc'    => __( 'Should commissions be calculated from the subtotal (before taxes and discounts) or from the total purchase amount (after taxes and discounts)? ', 'eddc' ),
			'type'    => 'select',
			'options' => $calc_options,
		),
		array(
			'id' => 'edd_commissions_autopay_pa',
			'name' => __('Instant Pay Commmissions', 'eddc'),
			'desc' => sprintf( __('If checked and <a href="%s">PayPal Adaptive Payments</a> gateway is installed, EDD will automatically pay commissions at the time of purchase', 'eddc'), 'https://easydigitaldownloads.com/extensions/paypal-adaptive-payments/' ),
			'type' => 'checkbox',
		),
	);

	if ( class_exists( 'EDD_Simple_Shipping' ) ) {
		$commission_settings[] = array(
			'id'      => 'edd_commissions_shipping',
			'name'    => __( 'Shipping Fees', 'eddc' ),
			'desc'    => __( 'How should shipping fees affect commission calculations?', 'eddc' ),
			'type'    => 'select',
			'options' => array(
				'ignored'          => __( 'Split shipping fees equally.', 'eddc' ),
				'include_shipping' => __( 'Shipping fee paid to first user ID in product\'s Commission settings.', 'eddc' ),
				'exclude_shipping' => __( 'Shipping fee paid to Store.', 'eddc' ),
			),
		);
	}

	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
		$commission_settings = array( 'commissions' => $commission_settings );
	}

	return array_merge( $settings, $commission_settings );
}
add_filter( 'edd_settings_extensions', 'eddc_settings_extensions' );


/**
 * Registers the new Commissions options in Emails
 *
 * @access      private
 * @since       3.0
 * @param 		$settings array the existing plugin settings
 * @return      array
*/
function eddc_settings_emails( $settings ) {
	$commission_settings = array(
		array(
			'id'      => 'eddc_header',
			'name'    => '<strong>' . __( 'Commission Notifications', 'eddc' ) . '</strong>',
			'desc'    => '',
			'type'    => 'header',
			'size'    => 'regular'
		),
        array(
            'id'    => 'edd_commissions_email_subject',
            'name'  => __( 'Email Subject', 'eddc' ),
            'desc'  => __( 'Enter the subject for commission emails.', 'eddc' ),
            'type'  => 'text',
            'size'  => 'regular',
            'std'   => __( 'New Sale!', 'eddc' )
        ),
        array(
            'id'    => 'edd_commissions_email_message',
            'name'  => __( 'Email Body', 'eddc' ),
            'desc'  => __( 'Enter the content for commission emails. HTML is accepted. Available template tags:', 'eddc' ) . '<br />' . eddc_display_email_template_tags(),
            'type'  => 'rich_editor',
            'std'   => eddc_get_email_default_body()
        )
	);

	return array_merge( $settings, $commission_settings );

}
add_filter( 'edd_settings_emails', 'eddc_settings_emails' );
