<?php
/*
Plugin Name: Easy Digital Downloads - Commissions
Plugin URI: http://easydigitaldownloads.com/extension/commissions
Description: Record commisions automatically for users in your site when downloads are sold
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
Version: 2.5
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

define( 'EDD_COMMISSIONS_VERSION', '2.5' );


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
include_once(EDDC_PLUGIN_DIR . 'includes/rest-api.php');
include_once(EDDC_PLUGIN_DIR . 'includes/automated-payouts.php');

if( is_admin() ) {
	include_once(EDDC_PLUGIN_DIR . 'includes/reports.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/settings.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/admin-page.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/metabox.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/EDD_C_List_Table.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/upgrades.php');
	include_once(EDDC_PLUGIN_DIR . 'includes/widgets.php');
} else {
	include_once(EDDC_PLUGIN_DIR . 'includes/short-codes.php');
}

if( ! class_exists( 'EDD_License' ) ) {
	include( EDDC_PLUGIN_DIR . 'includes/EDD_License_Handler.php' );
}
$eddc_license = new EDD_License( __FILE__, 'Commissions', EDD_COMMISSIONS_VERSION, 'Pippin Williamson' );
