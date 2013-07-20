<?php

// extends the default EDD REST API to provide an endpoint for commissions
class EDDC_REST_API {

	public function __construct() {

		add_filter( 'edd_api_valid_query_modes', array( $this, 'query_mode'  ) );
		add_filter( 'edd_api_output_data',       array( $this, 'output_data' ), 10, 3 );
	}

	public function query_mode( $query_modes ) {

		$query_modes[] = 'commissions';

		return $query_modes;
	}

	public function output_data( $data, $query_mode, $api_object ) {

		$user_id = $api_object->get_user();

		$data['unpaid'] = array();
		$data['paid']   = array();

		$unpaid = eddc_get_unpaid_commissions( $user_id, 30, $api_object->get_paged() );
		if( ! empty( $unpaid ) ) {
			foreach( $unpaid as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$data['unpaid'][] = array(
					'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( get_post_meta( $commission->ID, '_download_id', true ) ),
				);
			}
		}

		$paid = eddc_get_paid_commissions( $user_id, 30, $api_object->get_paged() );
		if( ! empty( $paid ) ) {
			foreach( $paid as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$data['paid'][] = array(
					'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( get_post_meta( $commission->ID, '_download_id', true ) ),
				);
			}
		}

		$data['totals'] = array(
			'unpaid'    => eddc_get_unpaid_totals( $user_id ),
			'paid'      => eddc_get_paid_totals( $user_id )
		);

		return $data;

	}

}
new EDDC_REST_API;