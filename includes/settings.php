<?php

/**
 * Registers the new Commissions license options in Misc
 * *
 * @access      private
 * @since       1.2.1
 * @param 		$settings array the existing plugin settings
 * @return      array
*/

function eddc_license_settings( $settings ) {

	$license_settings = array(
		array(
			'id' => 'eddc_license_header',
			'name' => '<strong>' . __('Commissions', 'eddc') . '</strong>',
			'desc' => '',
			'type' => 'header',
			'size' => 'regular'
		),
		array(
			'id' => 'edd_commissions_license_key',
			'name' => __('License Key', 'eddc'),
			'desc' => __('Enter your license for Commissions to receive automatic upgrades', 'eddc'),
			'type' => 'text',
			'size' => 'regular'
		)
	);

	return array_merge( $settings, $license_settings );

}
add_filter('edd_settings_misc', 'eddc_license_settings');

function eddc_activate_license() {
	global $edd_options;
	if( ! isset( $_POST['edd_settings_misc'] ) )
		return;
	if( ! isset( $_POST['edd_settings_misc']['edd_commissions_license_key'] ) )
		return;

	if( get_option( 'eddc_license_active' ) == 'active' )
		return;

	$license = sanitize_text_field( $_POST['edd_settings_misc']['edd_commissions_license_key'] );

	// data to send in our API request
	$api_params = array( 
		'edd_action'=> 'activate_license', 
		'license' 	=> $license, 
		'item_name' => urlencode( EDD_COMISSIONS_PRODUCT_NAME ) // the name of our product in EDD
	);
	
	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, EDD_COMISSIONS_STORE_API_URL ) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) )
		return false;

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	update_option( 'eddc_license_active', $license_data->license );

}
add_action( 'admin_init', 'eddc_activate_license' );