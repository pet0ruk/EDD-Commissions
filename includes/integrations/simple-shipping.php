<?php
/**
 * Simple Shipping integration
 *
 * This file holds all functions make commissions work with the Simple Shipping extension
 *
 * @copyright   Copyright (c) 2016, Easy Digital Downloads
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.2.11
 */

/**
 * Make the commission calulation include shipping fees from Simple Shipping (if the site owner chose that).
 *
 * @since    3.2.11
 * @param  	 int $commission_amount The amount already calculated for the commission
 * @param  	 array $args The args passed to the eddc_calc_commission_amount function
 * @return   int $amount The commission amount including shipping fee calculations
 */
function eddc_include_shipping_calc_in_commission( $commission_amount, $args ){
	
	$defaults = array(
		'price'         	=> NULL,
		'rate'         	 	=> NULL,
		'type' 				=> 'percentage',
		'download_id'   	=> 0,
		'cart_item' 		=> NULL,
		'recipient'     	=> NULL,
		'recipient_counter' => 0,
		'payment_id'    	=> NULL
	);

	$args = wp_parse_args( $args, $defaults );
	
	$commission_settings = get_post_meta( $args['download_id'], '_edd_commission_settings', true );
	
	$shipping = edd_get_option( 'edd_commissions_shipping', 'split_shipping' );

	//Reset shipping fee value because originally they didn't make sense and are kept that way for backwards compatibility only.
	if ( $shipping == 'ignored' ){
		$shipping = 'split_shipping';	
	}
	elseif( $shipping == 'include_shipping' ){
		$shipping = 'pay_to_first_user';
	}
	elseif( $shipping == 'exclude_shipping' ){
		$shipping = 'pay_to_store';
	}
	
	// Check if a special shipping setting has been applied to this product in particular and over-ride the site-default if so.
	if ( isset( $commission_settings['shipping_fee'] ) && $commission_settings['shipping_fee'] !== 'site_default' ){
		$shipping = $commission_settings['shipping_fee'];
	}
	
	// If there are fees
	if( !empty( $args['cart_item']['fees'] ) ) {
		
		//Loop through each fee
		foreach( $args['cart_item']['fees'] as $fee_id => $fee ) {
											
			if ( 'split_shipping' == $shipping ){
				$commission_amount += $fee['amount'] * ( $rate / 100 );
			}
			elseif( 'pay_to_first_user' == $shipping ) {
																					
				if ( eddc_get_recipient_position( $args['recipient'], $args['download_id'] ) == 0 ){
					$commission_amount += $fee['amount'];
				}
			}
		}
	}
	
	return $commission_amount;
	
}
add_filter( 'eddc_calc_commission_amount', 'eddc_include_shipping_calc_in_commission', 10, 2 );