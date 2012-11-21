<?php

/**
 * Record Commissions
 *
 * @access      private
 * @since       1.0
 * @return      void
 */

function eddc_record_commission( $payment_id, $new_status, $old_status ) {

	if ( $old_status == 'publish' || $old_status == 'complete' )
		return;

	if ( ! edd_is_test_mode() ) {

		$payment_data  	= get_post_meta( $payment_id, '_edd_payment_meta', true );
		$downloads   	= maybe_unserialize( $payment_data['downloads'] );
		$user_info   	= maybe_unserialize( $payment_data['user_info'] );
		$cart_details  	= maybe_unserialize( $payment_data['cart_details'] );

		// loop through each purchased download and award commissions, if needed
		foreach ( $downloads as $download ) {

			$download_id    		= absint( $download['id'] );
			$commissions_enabled  	= get_post_meta( $download_id, '_edd_commisions_enabled', true );


			// if we need to award a commission
			if ( $commissions_enabled ) {
				// set a flag so downloads with commissions awarded are easy to query
				update_post_meta( $download_id, '_edd_has_commission', true );

				$commission_settings = get_post_meta( $download_id, '_edd_commission_settings', true );

				if ( $commission_settings ) {

					$download_price = edd_get_download_price( $download_id );

					if ( is_array( $cart_details ) ) {

						$cart_item_id = eddc_get_cart_item_id( $cart_details, $download_id );
						
						$download_price = isset( $cart_details[ $cart_item_id ]['price'] ) ? $cart_details[ $cart_item_id ]['price'] : edd_get_download_price( $download_id );

					}

					if ( $user_info['discount'] != 'none' ) {
						$price = edd_get_discounted_amount( $user_info['discount'], $download_price );
					} else {
						$price = $download_price;
					}

					$user_id    		= absint( $commission_settings['user_id'] );  // recipient of the commission
					$rate     			= $commission_settings['amount'];    // percentage amount of download price
					$commission_amount 	= eddc_calc_commission_amount( $price, $rate ); // calculate the commission amount to award
					$currency    		= $payment_data['currency'];

					$commission = array(
						'post_type'  	=> 'edd_commission',
						'post_title'  	=> $payment_data['date'],
						'post_status'  	=> 'publish'
					);

					$commission_id = wp_insert_post( apply_filters( 'edd_commission_post_data', $commission ) );

					$commission_info = apply_filters( 'edd_commission_info', array(
							'user_id'  	=> $user_id,
							'rate'   	=> $rate,
							'amount'  	=> $commission_amount,
							'currency'  => $currency
						), $commission_id );

					update_post_meta( $commission_id, '_edd_commission_info', $commission_info );
					update_post_meta( $commission_id, '_commission_status', 'unpaid' );
					update_post_meta( $commission_id, '_download_id', $download_id );
					update_post_meta( $commission_id, '_user_id', $user_id );

					do_action( 'eddc_insert_commission', $user_id, $commission_amount, $rate, $download_id, $commission_id );
				}

			}
		}
	}

}
add_action( 'edd_update_payment_status', 'eddc_record_commission', 10, 3 );


function eddc_get_cart_item_id( $cart_details, $download_id ) {

	foreach( (array) $cart_details as $postion => $item ) {
		if( $item['id'] == $download_id ) {
			return $postion;
		}
	}
	return null;
}

function eddc_calc_commission_amount( $price, $rate ) {

	if ( $price == false )
		$price = '0.00';

	if ( $rate >= 1 )
		$amount = $price * ( $rate / 100 ); // rate format = 10 for 10%
	else
		$amount = $price * $rate; // rate format set as 0.10 for 10%

	return $amount;
}

function eddc_get_unpaid_commissions( $user_id = false ) {

	$args = array(
		'post_type' => 'edd_commission',
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => '_commission_status',
				'value' => 'unpaid'
			)
		)
	);

	if ( $user_id ) {

		$args['meta_query'][] = array(
			'key' => '_user_id',
			'value' => $user_id
		);

	}


	$commissions = get_posts( $args );

	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions
}

function eddc_get_paid_commissions( $user_id = false ) {

	$args = array(
		'post_type' => 'edd_commission',
		'posts_per_page' => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => '_commission_status',
				'value' => 'paid'
			)
		)
	);

	if ( $user_id ) {

		$args['meta_query'][] = array(
			'key' => '_user_id',
			'value' => $user_id
		);

	}

	$commissions = get_posts( $args );
	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions
}

function eddc_get_unpaid_totals() {

	$unpaid = eddc_get_unpaid_commissions();
	$total = (float) 0;
	if ( $unpaid ) {
		foreach ( $unpaid as $commission ) {
			$commission_info = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$total += $commission_info['amount'];
		}
	}
	return $total;
}

function edd_get_commissions_by_date( $day = null, $month, $year ) {


	$args = apply_filters( 'edd_get_commissions_by_date', array(
			'post_type' => 'edd_commission',
			'posts_per_page' => -1,
			'meta_key' => '_commission_status',
			'meta_value' => 'paid',
			'year' => $year,
			'monthnum' => $month
		),
		$day,
		$month,
		$year
	);

	if ( $day )
		$args['day'] = $day;

	$commissions = get_posts( $args );

	$total = 0;
	if ( $commissions ) {
		foreach ( $commissions as $commission ) {
			$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$amount = $commission_meta['amount'];
			$total = $total + $amount;
		}
	}
	return $total;
}


function eddc_generate_payout_file( $data ) {
	if ( wp_verify_nonce( $data['eddc-payout-nonce'], 'eddc-payout-nonce' ) ) {

		$commissions = eddc_get_unpaid_commissions();

		if ( $commissions ) {

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=edd-commission-payout-' . date( 'm-d-Y' ) . '.csv' );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );

			$payouts = array();

			foreach ( $commissions as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$user_id = $commission_meta['user_id'];
				$user = get_userdata( $user_id );
				$custom_paypal = get_user_meta( $user_id, 'eddc_user_paypal', true );
				$email = is_email( $custom_paypal ) ? $custom_paypal : $user->user_email;

				if ( array_key_exists( $email, $payouts ) ) {
					$payouts[$email]['amount'] += $commission_meta['amount'];
				} else {
					$payouts[$email] = array(
						'amount' => $commission_meta['amount'],
						'currency' => $commission_meta['currency']
					);
				}
				update_post_meta( $commission->ID, '_commission_status', 'paid' );

			}

			if ( $payouts ) {
				foreach ( $payouts as $key => $payout ) {

					echo $key . ",";
					echo  number_format( $payout['amount'] ) . ",";
					echo $payout['currency'];

					echo "\r\n";

				}

			}

		} else {
			wp_die( __( 'No commissions to be paid', 'eddc' ), __( 'Error' ) );
		}
		die();
	}
}
add_action( 'edd_generate_payouts', 'eddc_generate_payout_file' );


/**
 * Update a Commission
 *
 * @access      private
 * @since       1.2.0
 * @return      void
 */

function eddc_update_commission( $data ) {
	if ( wp_verify_nonce( $data['edd_sl_edit_nonce'], 'edd_sl_edit_nonce' ) ) {

		$id = $data['commission'];

		$commission_data = get_post_meta( $id, '_edd_commission_info', true );

		$rate = str_replace( '%', '', $data['rate'] );
		if ( $rate < 1 )
			$rate = $rate * 100;

		$amount = str_replace( '%', '', $data['amount'] );

		$commission_data['rate'] = (float)$rate;
		$commission_data['amount'] = (float) $amount;

		update_post_meta( $id, '_edd_commission_info', $commission_data );

		wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-commissions' ) ); exit;

	}
}
add_action( 'edd_edit_commission', 'eddc_update_commission' );


/**
 * Email Sale Alert
 *
 * Email an alert about the sale to the user receiving a commission
 *
 * @access      private
 * @since       1.1.0
 * @return      void
 */

function eddc_email_alert( $user_id, $commission_amount, $rate, $download_id ) {
	global $edd_options;

	$from_name = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo( 'name' );
	$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option( 'admin_email' );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";

	/* send an email alert of the sale */

	$user = get_userdata( $user_id );

	$email = $user->user_email; // set address here

	$message = __( 'Hello', 'eddc' ) . "\n\n" . sprintf( __( 'You have made a new sale on %s!', 'eddc' ), stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) ) . ".\n\n";
	$message .= __( 'Item sold: ', 'eddc' ) . get_the_title( $download_id ) . "\n\n";
	$message .= __( 'Amount: ', 'eddc' ) . " " . html_entity_decode( edd_currency_filter( edd_format_amount( $commission_amount ) ) ) . "\n\n";
	$message .= __( 'Commission Rate: ', 'eddc' ) . $rate . "%\n\n";
	$message .= __( 'Thank you', 'eddc' );

	$message = apply_filters( 'eddc_sale_alert_email', $message, $download_id, $user_id, $commission_amount, $rate );

	wp_mail( $email, __( 'New Sale!', 'eddc' ), $message, $headers );
}
add_action( 'eddc_insert_commission', 'eddc_email_alert', 10, 4 );
