<?php

if( edd_get_option( 'edd_commissions_autopay_pa' ) ) {
	
	add_filter( 'epap_adaptive_receivers', 'eddc_paypal_adaptive_autopay', 8, 2 );
	add_action( 'eddc_insert_commission', 'eddc_override_commission_status', 8, 6 );
	
}

function eddc_paypal_adaptive_autopay( $receivers, $payment ) {

	$cart  = edd_get_payment_meta_cart_details( $payment );
	if ( 'subtotal' == edd_get_option( 'edd_commissions_calc_base', 'subtotal' ) ) {
		$total = edd_get_payment_subtotal( $payment );
	} else {
		$total = edd_get_payment_amount( $payment );
	}

	$final = array();

	foreach ( $cart as $item ) {

		$recipients = eddc_get_recipients( $item['id'] );
		
		if ( 'subtotal' == edd_get_option( 'edd_commissions_calc_base', 'subtotal' ) ) {

			$price = $item['subtotal'];

		} else {
		
			$price = $item['price'];

		}

		foreach ( $recipients as $recipient ) {

			$type   = eddc_get_commission_type( $item['id'] );
			$rate   = eddc_get_recipient_rate( $item['id'], $recipient );

			if( 'percentage' == $type ) {

				$percentage = $rate;

			} else {

				$amount     = eddc_calc_commission_amount( $price, $rate, $type );
				$percentage = ( ( 100 / $total ) * $amount );

			}
		
			$user          = get_userdata( $recipient );
			$custom_paypal = get_user_meta( $recipient, 'eddc_user_paypal', true );
			$paypal        = is_email( $custom_paypal ) ? $custom_paypal : $user->user_email;

			if ( $percentage !== 0 ) {
				if ( isset( $final[ $paypal ] ) ) {
					$final[ $paypal ] = $percentage + $final[ $paypal ];
				} else {
					$final[ $paypal ] = $percentage;
				}
			}
		}
	}

	$return  = '';
	$counter = 0;
	$taken   = 0;

	// Add up the total commissions
	foreach ( $final as $person => $val ) {
		$taken = $taken + $val;
	}


	// Calculate the final percentage the store owners should receive

	$remaining = 100 - $taken;
	$owners    = $receivers;
	$owners    = explode( "\n", $owners );

	foreach ( $owners as $key => $val ) {

		$val        = explode( '|', $val );
		$email      = $val[0];
		$percentage = $val[1];
		$remainder  = ( 100 / $percentage ) * $remaining;

		if ( isset( $final[ $email ] ) ) {
			$final[ $email ] = $final[ $email ] + $remainder;
		} else {
			$final[ $email ] = $remainder;
		}

	}

	// rebuild the final PayPal receivers string
	foreach ( $final as $person => $val ) {

		if ( $counter === 0) {
			$return = $person . "|" . $val;
		} else {
			$return = $return . "\n" . $person . "|" . $val;
		}
		$counter++;

	}
	echo $return; exit;

	return $return;
}

function eddc_override_commission_status( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {
	update_post_meta( $commission_id, '_commission_status', 'paid' );
}