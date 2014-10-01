<?php
/*
Plugin Name: Easy Digital Downloads - Commissions
Plugin URI: http://easydigitaldownloads.com/extension/commissions
Description: Record commisions automatically for users in your site when downloads are sold
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
Version: 2.9
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

define( 'EDD_COMMISSIONS_VERSION', '2.9' );


/*
|--------------------------------------------------------------------------
| INTERNATIONALIZATION
|--------------------------------------------------------------------------
*/

function eddc_textdomain() {
	load_plugin_textdomain( 'eddc', false, dirname( plugin_basename( EDDC_PLUGIN_FILE ) ) . '/languages/' );
}
add_action('init', 'eddc_textdomain');


function edd_commissions__install() {

	add_option( 'eddc_version', EDD_COMMISSIONS_VERSION, '', false );

}
register_activation_hook( __FILE__, 'edd_commissions__install' );


/*
|--------------------------------------------------------------------------
| INCLUDES
|--------------------------------------------------------------------------
*/


include_once(EDDC_PLUGIN_DIR . 'includes/commission-functions.php');
include_once(EDDC_PLUGIN_DIR . 'includes/post-type.php');
include_once(EDDC_PLUGIN_DIR . 'includes/user-meta.php');
include_once(EDDC_PLUGIN_DIR . 'includes/rest-api.php');
include_once(EDDC_PLUGIN_DIR . 'includes/short-codes.php');

if( is_admin() ) {

	if( class_exists( 'EDD_License' ) ) {
		$eddc_license = new EDD_License( __FILE__, 'Commissions', EDD_COMMISSIONS_VERSION, 'Pippin Williamson' );
	}
	//include_once(EDDC_PLUGIN_DIR . 'includes/scheduled-payouts.php');
	//include_once(EDDC_PLUGIN_DIR . 'includes/masspay/class-paypal-masspay.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/reports.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/settings.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin/list.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin/metabox.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin/widgets.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin/upgrades.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/EDD_C_List_Table.php');
} else {
	include_once(EDDC_PLUGIN_DIR . 'includes/adaptive-payments.php');
}