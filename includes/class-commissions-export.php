<?php
/**
 * Commissions Export Class
 *
 * This class handles exporting user's commissions
 *
 * @package     Easy Digital Downloads - Commissions
 * @subpackage  Export Class
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'EDD_Export' ) ) {
	require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';
}

class EDD_Commissions_Export extends EDD_Export {

	/**
	 * Our export type.
	 *
	 * @access      public
	 * @var         string
	 * @since       2.3
	 */
	public $export_type = 'commissions';

	/**
	 * Set the CSV columns
	 *
	 * @access      public
	 * @since       2.3
	 * @return      array
	 */
	public function csv_cols() {
		$cols = array(
			'download' => __( 'Product', 'eddc' ),
			'rate'     => __( 'Rate',    'eddc' ),
			'amount'   => __( 'Amount',  'eddc' ) . ' (' . html_entity_decode( edd_currency_filter( '' ) ) . ')',
			'date'     => __( 'Date',    'eddc' )
		);
		return $cols;
	}

	/**
	 * Get the data being exported
	 *
	 * @access      public
	 * @since       2.3
	 * @return      array
	 */
	public function get_data() {

		$data = array();

		$commissions = eddc_get_paid_commissions( $this->user_id,  )

		if ( $commissions ) {
			foreach ( $commissions as $commission ) {
				
				$data[]        = array(
					'download' => '',
					'rate'     => '',
					'amount'   => '',
					'date'     => ''

				);
			}
		}

		$data = apply_filters( 'edd_export_get_data', $data );
		$data = apply_filters( 'edd_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}