<?php

if( edd_get_option( 'edd_commissions_autopay_pa' ) ) {
	
	add_filter( 'epap_adaptive_receivers', 'eddc_paypal_adaptive_autopay', 8, 2 );
	add_action( 'eddc_insert_commission', 'eddc_override_commission_status', 8, 6 );
	
}

function eddc_paypal_adaptive_autopay( $receivers, $payment ) {

	$cart  = edd_get_payment_meta_cart_details( $payment );
	$total = edd_get_payment_amount( $payment );
	$items = edd_get_payment_meta_downloads( $payment );
	$final = array();

	foreach ( $items as $item ) {

		$recipients = eddc_get_recipients( $item['id'] );
		
		if ( 'subtotal' == edd_get_option( 'edd_commissions_calc_base', 'subtotal' ) ) {

			$price = $item['subtotal'];

		} else {
		
			$price = $item['price'];

		}

		foreach ( $recipients as $recipient ) {

			$type   = eddc_get_commission_type( $item['id'] );
			$rate   = eddc_get_recipient_rate( $item['id'], $recipient );

			$amount = eddc_calc_commission_amount( $price, $rate, $type )
		
			$paypal = get_user_meta( $recipient, 'eddc_user_paypal', true );

			if ( $amount !== 0 ) {
				if ( isset( $final[ $paypal ] ) ) {
					$final[ $paypal ] = $amount + $final[ $paypal ];
				} else {
					$final[ $paypal ] = $amount;
				}
			}
		}
	}

	$return  = '';
	$counter = 0;
	$taken   = 0;

	foreach ( $final as $person => $val ) {
		$taken = $taken + $val;
	}

	$remaining = 100 - $taken;
	$owner     = $receivers;
	$owner     = explode( "\n", $owner );

	foreach ( $owner as $key => $val ) {

		$val       = explode( '|', $val );
		$email     = $val[0];
		$pfg       = $val[1];
		$remainder = ( $pfg / 100) * $remaining;
		
		if ( isset( $final[ $email ] ) ) {
			$final[ $email ] = $final[ $email ] + $remainder;
		} else {
			$final[ $email ] = $remainder;
		}

	}

	foreach ( $final as $person => $val ) {

		if ( $counter === 0) {
			$return = $person . "|" . $val;
		} else {
			$return = $return . "\n" . $person . "|" . $val;
		}
		$counter++;

	}

	return $return;
}

function eddc_override_commission_status( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {
	update_post_meta( $commission_id, '_commission_status', 'paid' );
}