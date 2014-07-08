<?php
/**
 * PayPal Adaptive Payments integration
 *
 * This file holds all functions that take care of instant payouts using PayPal Adaptive Payments
 *
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
 */


/**
 * Setup PayPal receivers when a purchase is made
 *
 * @since 2.7
 * @param $receivers string The default receivers and their percentages as defined in the Payment Gateway settings
 * @param $payment_id int The payment ID of the purchase
 * @return receivers $string The modified receivers string
 */
function eddc_paypal_adaptive_autopay( $receivers, $payment_id ) {

	if( ! edd_get_option( 'edd_commissions_autopay_pa' ) ) {
		return $receivers;
	}

	$cart  = edd_get_payment_meta_cart_details( $payment_id );
	if ( 'subtotal' == edd_get_option( 'edd_commissions_calc_base', 'subtotal' ) ) {
		$total = edd_get_payment_subtotal( $payment_id );
	} else {
		$total = edd_get_payment_amount( $payment_id );
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

			$type          = eddc_get_commission_type( $item['id'] );
			$rate          = eddc_get_recipient_rate( $item['id'], $recipient );
			$args          = array(
				'price'       => $price,
				'rate'        => $rate,
				'type'        => $type,
				'download_id' => $item['id'],
				'recipient'   => $recipient,
				'payment_id'  => $payment_id
			);

			$amount        = eddc_calc_commission_amount( $args );
			$percentage    = round( ( 100 / $total ) * $amount, 2 );
			$user          = get_userdata( $recipient );
			$custom_paypal = get_user_meta( $recipient, 'eddc_user_paypal', true );
			$email         = is_email( $custom_paypal ) ? $custom_paypal : $user->user_email;

			if ( $percentage !== 0 ) {
				if ( isset( $final[ $email ] ) ) {
					$final[ $email ] = $percentage + $final[ $email ];
				} else {
					$final[ $email ] = $percentage;
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
		$remainder  = ( $percentage / 100 ) * $remaining;

		if ( isset( $final[ $email ] ) ) {
			$final[ $email ] = $final[ $email ] + $remainder;
		} else {
			$final[ $email ] = $remainder;
		}

	}

	// Rebuild the final PayPal receivers string
	foreach ( $final as $person => $val ) {

		if ( $counter === 0) {
			$return = $person . "|" . $val;
		} else {
			$return = $return . "\n" . $person . "|" . $val;
		}
		$counter++;

	}

	//echo '<pre>'; print_r( $return ); echo '</pre>'; exit;

	return $return;
}
add_filter( 'epap_adaptive_receivers', 'eddc_paypal_adaptive_autopay', 8, 2 );


/**
 * Mark commissions as paid immediately since they are paid at the time of purchase
 *
 * @since 2.7
 * @return void
 */
function eddc_override_commission_status( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {
	
	if( ! edd_get_option( 'edd_commissions_autopay_pa' ) || 'paypal_adaptive_payments' != edd_get_payment_gateway( $payment_id ) ) {
		return;
	}

	eddc_set_commission_status( $commission_id, 'paid' );
}
add_action( 'eddc_insert_commission', 'eddc_override_commission_status', 8, 6 );