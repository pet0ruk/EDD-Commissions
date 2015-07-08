<?php
/**
 * Batch Commissions Payout Generation Class
 *
 * This class handles payment export in batches
 *
 * @package     EDDC
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDD_Batch_Commissoins_Payout Class
 *
 * @since 3.2
 */
class EDD_Batch_Commissions_Payout extends EDD_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var string
	 * @since 3.2
	 */
	public $export_type = 'commissions_payout';

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 3.2
	 * @return array $cols All the columns
	 */
	public function csv_cols() {
		return array();
	}

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 3.2
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @return array $data The data for the CSV file
	 */
	public function get_data() {

		$from = explode( '/', $this->start );
		$to   = explode( '/', $this->end );

		$args = array(
			'number'         => 25,
			'paged'          => $this->step,
			'query_args'     => array(
				'date_query' => array(
					'after'       => array(
						'year'    => $from[2],
						'month'   => $from[0],
						'day'     => $from[1],
					),
					'before'      => array(
						'year'    => $to[2],
						'month'   => $to[0],
						'day'     => $to[1],
					),
					'inclusive' => true
				)
			)
		);

		$commissions = eddc_get_unpaid_commissions( $args );

		if ( $commissions ) {

			$payouts = array();

			foreach ( $commissions as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$user_id       = $commission_meta['user_id'];
				$user          = get_userdata( $user_id );
				$custom_paypal = get_user_meta( $user_id, 'eddc_user_paypal', true );
				$email         = is_email( $custom_paypal ) ? $custom_paypal : $user->user_email;
				$key           = md5( $email );

				if ( array_key_exists( $key, $payouts ) ) {
					$payouts[ $key ]['amount'] += $commission_meta['amount'];
					$payouts[ $key ]['ids'][]   = $commission->ID;
				} else {
					$payouts[ $key ] = array(
						'email'      => $email,
						'amount'     => $commission_meta['amount'],
						'currency'   => $commission_meta['currency'],
						'ids'        => array( $commission->ID ),
					);
				}

			}

			return $payouts;

		}

		return false;
	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 3.2
	 * @return int
	 */
	public function get_percentage_complete() {

		$from = explode( '/', $this->start );
		$to   = explode( '/', $this->end );

		$args = array(
			'number'         => -1,
			'query_args'     => array(
				'date_query' => array(
					'after'       => array(
						'year'    => $from[2],
						'month'   => $from[0],
						'day'     => $from[1],
					),
					'before'      => array(
						'year'    => $to[2],
						'month'   => $to[0],
						'day'     => $to[1],
					),
					'inclusive' => true
				)
			),
		);

		$commissions = eddc_get_unpaid_commissions( $args );
		$total       = count( $commissions );

		$percentage = 100;

		if( $total > 0 ) {
			$percentage = ( ( 25 * $this->step ) / $total ) * 100;
		}

		if( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Append data to export file
	 *
	 * @since 3.2
	 * @param $data string The data to add to the file
	 * @return void
	 */
	private function stash_temp_data( $data = array() ) {

		$this->get_temp_file();
		$current_file = @file_get_contents( $this->temp_file );
		$current_data = json_decode( $current_file, true );

		if ( empty( $current_data ) ) {
			$current_data = array();
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $entry ) {
				if ( array_key_exists( $key, $current_data ) ) {

					$current_data[ $key ]['amount'] += $entry['amount'];

					$current_ids = ! empty( $current_data[ $key ]['ids'] ) ? $current_data[ $key ]['ids'] : array();
					$new_ids     = $entry['ids'];
					$all_ids     = array_unique( array_merge( $current_ids, $new_ids ) );

					$current_data[ $key ]['ids'] = $all_ids;

				} else {

					$current_data[ $key ] = array(
						'email'    => $entry['email'],
						'amount'   => $entry['amount'],
						'currency' => $entry['currency'],
						'ids'      => $entry['ids'],
					);

				}
			}

			if ( ! empty( $current_data ) ) {
				$current_data = json_encode( $current_data );
				@file_put_contents( $this->temp_file, $current_data );
			}

			return $current_data;
		}

		return false;
	}

	/**
	 * Output the CSV columns
	 *
	 * @access public
	 * @since 3.2
	 * @uses EDD_Export::get_csv_cols()
	 * @return string
	 */
	public function print_csv_cols() {

		$this->get_temp_file();
		@unlink( $this->temp_file );
		return;

	}

	/**
	 * Print the CSV rows for the current step
	 *
	 * @access public
	 * @since 3.2
	 * @return string|false
	 */
	public function print_csv_rows() {

		$data = $this->get_data();
		if ( ! empty( $data ) ) {
			return $this->stash_temp_data( $data );
		} else {
			$this->get_temp_file();
			$temp_data = @file_get_contents( $this->temp_file );
			$data      = json_decode( $temp_data );
			$row_data  = '';

			if( $data ) {

				// Output each row
				foreach ( $data as $row ) {

					if ( ! empty( $this->minimum ) && $this->minimum > $row->amount ) {
						continue;
					}

					$i = 1;
					foreach ( $row as $col_id => $column ) {

						if ( 'ids' === $col_id ) {
							continue;
						}

						switch( $col_id ) {
							case 'amount':
								$column = edd_format_amount( $column, 2 );
								break;
						}

						$row_data .= '"' . addslashes( $column ) . '"';
						$row_data .= $i == 3 ? '' : ',';
						$i++;
					}
					$row_data .= "\r\n";

					foreach ( $row->ids as $id ) {
						eddc_set_commission_status( $id, 'paid' );
					}

				}

				$this->stash_step_data( $row_data );
				@unlink( $this->temp_file );
				return false;

			}
		}

		return false;
	}

	/**
	 * Setup the temporary file location data
	 *
	 * @since  3.2
	 * @return void
	 */
	private function get_temp_file() {

		$upload_dir            = wp_upload_dir();
		$this->temp_filetype   = '.json';
		$this->temp_filename   = 'edd-' . $this->export_type . $this->temp_filetype;
		$this->temp_file       = trailingslashit( $upload_dir['basedir'] ) . $this->temp_filename;

		$file = @file_get_contents( $this->temp_file );

		if( ! $file ) {
			@file_put_contents( $this->temp_file, '' );
		}

	}

	/**
	 * Set the parameters necessary for this request
	 *
	 * @since  3.2
	 * @param array $request The Form data sent in from the export request
	 */
	public function set_properties( $request ) {
		$this->start   = isset( $request['start'] )   ? sanitize_text_field( $request['start'] )   : '';
		$this->end     = isset( $request['end']  )    ? sanitize_text_field( $request['end']  )    : '';
		$this->minimum = isset( $request['minimum'] ) ? sanitize_text_field( $request['minimum'] ) : 0;
	}

}
