<?php

// extends the default EDD REST API to provide an endpoint for commissions
class EDDC_REST_API {

	public function __construct() {

		add_filter( 'edd_api_valid_query_modes', array( $this, 'query_modes'  ) );
		add_filter( 'edd_api_output_data',       array( $this, 'user_commission_data' ), 10, 3 );
		add_filter( 'edd_api_output_data',       array( $this, 'store_commission_data' ), 10, 3 );
	}

	public function query_modes( $query_modes ) {

		$query_modes[] = 'commissions';
		$query_modes[] = 'store-commissions';

		return $query_modes;
	}

	public function store_commission_data( $data, $query_mode, $api_object ) {

		if( 'store-commissions' != $query_mode ) {
			return $data;
		}

		$user_id = $api_object->get_user();

		if( ! user_can( $user_id, 'view_shop_reports' ) ) {
			return $data;
		}

		$data   = array( 'commissions' => array() );
		$paged  = $api_object->get_paged();
		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : 'unpaid';

        $commission_args = array(
            'post_type'      => 'edd_commission',
            'post_status'    => 'publish',
            'posts_per_page' => $api_object->per_page(),
            'paged'          => $paged
        );

        if( $status ) {
        	$commission_args['tax_query'] = array( array(
                'taxonomy' => 'edd_commission_status',
                'terms'    => $status,
                'field'    => 'slug'
            ) );
        }

        $commissions = get_posts( $commission_args );

        if ( $commissions ) {

            foreach( $commissions as $commission ) {

                $commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );
                $download_id     = get_post_meta( $commission->ID, '_download_id', true );
               
                $data['commissions'][] = array(
                    'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( $download_id ),
					'status'   => eddc_get_commission_status( $commission->ID ),
					'date'     => $commission->post_date
                );
            }

            wp_reset_postdata();
        }

		$data['total_unpaid'] = eddc_get_unpaid_totals();

		return $data;

	}

	public function user_commission_data( $data, $query_mode, $api_object ) {

		if( 'commissions' != $query_mode )
			return $data;

		$user_id = $api_object->get_user();

		$data['unpaid']  = array();
		$data['paid']    = array();
		$data['revoked'] = array();

		$unpaid = eddc_get_unpaid_commissions( array( 'user_id' => $user_id, 'number' => 30, 'paged' => $api_object->get_paged() ) );
		if( ! empty( $unpaid ) ) {
			foreach( $unpaid as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$data['unpaid'][] = array(
					'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( get_post_meta( $commission->ID, '_download_id', true ) ),
					'date'     => $commission->post_date
				);
			}
		}

		$paid = eddc_get_paid_commissions( array( 'user_id' => $user_id, 'number' => 30, 'paged' => $api_object->get_paged() ) );
		if( ! empty( $paid ) ) {
			foreach( $paid as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$data['paid'][] = array(
					'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( get_post_meta( $commission->ID, '_download_id', true ) ),
					'date'     => $commission->post_date
				);
			}
		}

		$revoked = eddc_get_revoked_commissions( array( 'user_id' => $user_id, 'number' => 30, 'paged' => $api_object->get_paged() ) );
		if( ! empty( $revoked ) ) {
			foreach( $revoked as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$data['revoked'][] = array(
					'amount'   => edd_sanitize_amount( $commission_meta['amount'] ),
					'rate'     => $commission_meta['rate'],
					'currency' => $commission_meta['currency'],
					'item'     => get_the_title( get_post_meta( $commission->ID, '_download_id', true ) ),
					'date'     => $commission->post_date
				);
			}
		}

		$data['totals'] = array(
			'unpaid'  => eddc_get_unpaid_totals( $user_id ),
			'paid'    => eddc_get_paid_totals( $user_id ),
			'revoked' => eddc_get_revoked_totals( $user_id )
		);

		return $data;

	}	

}
new EDDC_REST_API;