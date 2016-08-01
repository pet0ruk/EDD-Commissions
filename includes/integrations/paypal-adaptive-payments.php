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
	
	$paypal_adaptive_receivers = array();
	
	$commissions_calculated = eddc_calculate_payment_commissions( $payment_id );
	
	$payment = new EDD_Payment( $payment_id );
	
	$total_cost = $payment->total;
	
	$counter = 0;
	
	// Loop through each commission and add all commission amounts together if they are for the same recipient
	foreach ( $commissions_calculated as $commission_calculated ) {
		
		extract( $commission_calculated );
		
		// If this recipient already has a commission listed from this payment, 
		if ( in_array( $recipient, $paypal_adaptive_receivers ) ){
			// Add the amount for this commission to the previous commission total for this recipient
			$paypal_adaptive_receivers[$recipient] += $commission_amount;
		} else{
			$paypal_adaptive_receivers[$recipient] = $commission_amount;
		}
	}
	
	// Rebuild the final PayPal Adaptive receivers string
	foreach( $paypal_adaptive_receivers as $recipient => $commission_amount ){	
		
		$wp_user = get_user_by( 'id', $recipient );
		
		$rate = ( $commission_amount / $total_cost ) * 100;
		
		if ( $counter === 0) {
			$return = $wp_user->user_email . "|" . $rate;
		} else {
			$return = $return . "\n" . $wp_user->user_email  . "|" . $rate;
		}
		
		$counter++;
	}

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