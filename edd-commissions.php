<?php
/*
Plugin Name: Easy Digital Downloads - Commissions
Plugin URI: http://easydigitaldownloads.com/extension/commissions
Description: Record commisions automatically for users in your site when downloads are sold
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
Version: 1.4.2
*/



/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

// plugin folder url
if(!defined('EDDC_PLUGIN_URL')) {
	define('EDDC_PLUGIN_URL', plugin_dir_url( __FILE__ ));
}
// plugin folder path
if(!defined('EDDC_PLUGIN_DIR')) {
	define('EDDC_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
}
// plugin root file
if(!defined('EDDC_PLUGIN_FILE')) {
	define('EDDC_PLUGIN_FILE', __FILE__ );
}

define( 'EDD_COMISSIONS_STORE_API_URL', 'http://easydigitaldownloads.com' );
define( 'EDD_COMISSIONS_PRODUCT_NAME', 'Comissions' );
define( 'EDD_COMISSIONS_VERSION', '1.4.2' );


/*
|--------------------------------------------------------------------------
| INTERNATIONALIZATION
|--------------------------------------------------------------------------
*/

function eddc_textdomain() {
	load_plugin_textdomain( 'eddc', false, dirname( plugin_basename( EDD_PLUGIN_FILE ) ) . '/languages/' );
}
add_action('init', 'eddc_textdomain');



/*
|--------------------------------------------------------------------------
| INCLUDES
|--------------------------------------------------------------------------
*/


include_once(EDDC_PLUGIN_DIR . 'includes/commission-functions.php');
include_once(EDDC_PLUGIN_DIR . 'includes/post-type.php');
include_once(EDDC_PLUGIN_DIR . 'includes/user-meta.php');

if( is_admin() ) {
	include_once(EDDC_PLUGIN_DIR . 'includes/reports.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/settings.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin-page.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/metabox.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/EDD_C_List_Table.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/upgrades.php');
} else {
	include_once(EDDC_PLUGIN_DIR . 'includes/short-codes.php');
}


function edd_commissions_updater() {

	if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
		// load our custom updater
		include( EDDC_PLUGIN_DIR . 'EDD_SL_Plugin_Updater.php' );
	}

	global $edd_options;

	// retrieve our license key from the DB
	$edd_commissions_license_key = isset( $edd_options['edd_commissions_license_key'] ) ? trim( $edd_options['edd_commissions_license_key'] ) : '';

	// setup the updater
	$edd_cr_updater = new EDD_SL_Plugin_Updater( EDD_COMISSIONS_STORE_API_URL, __FILE__, array(
			'version' 	=> EDD_COMISSIONS_VERSION, 			// current version number
			'license' 	=> $edd_commissions_license_key, 	// license key (used get_option above to retrieve from DB)
			'item_name' => EDD_COMISSIONS_PRODUCT_NAME, 	// name of this plugin
			'author' 	=> 'Pippin Williamson'  			// author of this plugin
		)
	);

}
add_action( 'admin_init', 'edd_commissions_updater' );