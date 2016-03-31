<?php

/**
 * Record Commissions
 *
 * @access      private
 * @since       1.0
 * @return      void
 */

function eddc_record_commission( $payment_id, $new_status, $old_status ) {

	// Check if the payment was already set to complete
	if( $old_status == 'publish' || $old_status == 'complete' )
		return; // Make sure that payments are only completed once

	// Make sure the commission is only recorded when new status is complete
	if( $new_status != 'publish' && $new_status != 'complete' )
		return;

	if( edd_get_payment_gateway( $payment_id ) == 'manual_purchases' && ! isset( $_POST['commission'] ) )
		return; // do not record commission on manual payments unless specified

	if( edd_get_payment_meta( $payment_id, '_edd_completed_date' ) ) {
		return;
	}

	$payment_data = edd_get_payment_meta( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$cart_details = edd_get_payment_meta_cart_details( $payment_id );
	$calc_base    = edd_get_option( 'edd_commissions_calc_base', 'subtotal' );
	$shipping     = edd_get_option( 'edd_commissions_shipping', 'split_shipping' );
	
	//Reset shipping fee value because originally they didn't make sense and are kept that way for backwards compatibility
	if ( $shipping == 'ignored' ){
		$shipping = 'split_shipping';	
	}
	elseif( $shipping == 'include_shipping' ){
		$shipping = 'pay_to_first_user';
	}
	elseif( $shipping == 'exclude_shipping' ){
		$shipping = 'pay_to_store';
	}
	
	$current_variable_price_number = array();
	
	// loop through each purchased download and award commissions, if needed
	foreach ( $cart_details as $download ) {
		
		if ( in_array( $download['id'], $already_purchased_ids ) ){
			$current_variable_price_number[$download['id']] = $current_variable_price_number[$download['id']] + 1;
		}
		$already_purchased_ids[] = $download['id'];
		$current_variable_price_number[$download['id']] = !isset( $current_variable_price_number[$download['id']] ) ? 0 : $current_variable_price_number[$download['id']];

		$download_id         = absint( $download['id'] );
		$commissions_enabled = get_post_meta( $download_id, '_edd_commisions_enabled', true );
		$commission_settings = get_post_meta( $download_id, '_edd_commission_settings', true );
			
		// Check if a special shipping setting has been applied to this product in particular and over-ride the site-default if so.
		if ( isset( $commission_settings['shipping_fee'] ) && $commission_settings['shipping_fee'] !== 'site_default' ){
			$shipping = $commission_settings['shipping_fee'];
		}

		if ( 'subtotal' == $calc_base ) {

			$price = $download['subtotal'];

		} else {

			if ( 'total_pre_tax' == $calc_base ) {

				$price = $download['price'] - $download['tax'];

			} else {

				$price = $download['price'];

			}


		}

		// if we need to award a commission, and the price is greater than zero
		if ( $commissions_enabled && floatval( $price ) > '0' ) {

			// set a flag so downloads with commissions awarded are easy to query
			update_post_meta( $download_id, '_edd_has_commission', true );

			if ( $commission_settings ) {

				$type = eddc_get_commission_type( $download_id );

				// but if we have price variations, then we need to get the name of the variation
				$has_variable_prices = edd_has_variable_prices( $download_id );

				if ( $has_variable_prices ) {
					$price_id  = edd_get_cart_item_price_id ( $download );
					$variation = edd_get_price_option_name( $download_id, $price_id );
				}
								
				$recipients = eddc_get_recipients( $download_id );
				
				$recipient_counter = 0;
				
				// Record a commission for each user
				foreach( $recipients as $recipient ) {

					$rate               = eddc_get_recipient_rate( $download_id, $recipient );    // percentage amount of download price
					$args               = array(
						'price'         => $price,
						'rate'          => $rate,
						'type'          => $type,
						'download_id'   => $download_id,
						'recipient'     => $recipient,
						'payment_id'    => $payment_id
					);

					$commission_amount = eddc_calc_commission_amount( $args ); // calculate the commission amount to award
					$currency          = $payment_data['currency'];

					// If there are fees
					if( !empty( $download['fees'] ) ) {
						
						//Loop through each fee
						foreach( $download['fees'] as $fee_id => $fee ) {
							
							//If this is a shipping fee AND we are dealing with the corresponding fee to the corresponding 
							if ( $fee_id == 'simple_shipping_' . $current_variable_price_number[$download_id] ){
								
								if ( 'split_shipping' == $shipping ){
									$commission_amount += $fee['amount'] * ( $rate / 100 );
								}
								elseif( 'pay_to_first_user' == $shipping ) {
									
									if ( $recipient_counter == 0 ){
										$commission_amount += $fee['amount'];
									}

								}
								else{
									$commission_amount += $fee['amount'];
								}

							}

						}

					}

					$commission = array(
						'post_type'   => 'edd_commission',
						'post_title'  => $user_info['email'] . ' - ' . get_the_title( $download_id ),
						'post_status' => 'publish'
					);

					$commission_id = wp_insert_post( apply_filters( 'edd_commission_post_data', $commission ) );

					$commission_info = apply_filters( 'edd_commission_info', array(
						'user_id'  => $recipient,
						'rate'     => $rate,
						'amount'   => $commission_amount,
						'currency' => $currency
					), $commission_id, $payment_id, $download_id );

					eddc_set_commission_status( $commission_id, 'unpaid' );

					update_post_meta( $commission_id, '_edd_commission_info', $commission_info );
					update_post_meta( $commission_id, '_download_id', $download_id );
					update_post_meta( $commission_id, '_user_id', $recipient );
					update_post_meta( $commission_id, '_edd_commission_payment_id', $payment_id );

					// If we are dealing with a variation, then save variation info
					if ( $has_variable_prices && isset( $variation ) ) {
						update_post_meta( $commission_id, '_edd_commission_download_variation', $variation );
					}

					// If it's a renewal, save that detail
					if ( ! empty( $download['item_number']['options']['is_renewal'] ) ) {
						update_post_meta( $commission_id, '_edd_commission_is_renewal', true );
					}

					do_action( 'eddc_insert_commission', $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id );
					
					$recipient_counter = $recipient_counter + 1;
				}
			}
		}
	}
}
add_action( 'edd_update_payment_status', 'eddc_record_commission', 10, 3 );


/**
 * Retrieve the paid status of a commissions
 *
 * @access      public
 * @since       2.8
 * @return      string
 */
function eddc_get_commission_status( $commission_id = 0 ) {

	$status = 'unpaid';
	$terms  = get_the_terms( $commission_id, 'edd_commission_status' );

	if( is_array( $terms ) ) {

		foreach( $terms as $term ) {

			$status = $term->slug;
			break;
		}

	}

	return apply_filters( 'eddc_get_commission_status', $status, $commission_id );
}

/**
 * Sets the status for a commission record
 *
 * @access      public
 * @since       2.8
 * @return      void
 */
function eddc_set_commission_status( $commission_id = 0, $new_status = 'unpaid' ) {

	$old_status = eddc_get_commission_status( $commission_id );

	do_action( 'eddc_pre_set_commission_status', $commission_id, $new_status, $old_status );

	wp_set_object_terms( $commission_id, $new_status, 'edd_commission_status', false );

	do_action( 'eddc_set_commission_status', $commission_id, $new_status, $old_status );

}

/**
 * Get if a commission was on a renewal
 *
 * @since  3.2
 * @param  integer $commission_id Commission ID
 * @return bool                   If the commission was for a renewal or not
 */
function eddc_commission_is_renewal( $commission_id = 0 ) {

	if ( empty( $commission_id ) ) {
		return false;
	}

	$is_renewal = get_post_meta( $commission_id, '_edd_commission_is_renewal', true );

	return apply_filters( 'eddc_commission_is_renewal', $is_renewal, $commission_id );
}


function eddc_get_recipients( $download_id = 0 ) {
	$settings = get_post_meta( $download_id, '_edd_commission_settings', true );
	$recipients = array_map( 'trim', explode( ',', $settings['user_id'] ) );
	return (array) apply_filters( 'eddc_get_recipients', $recipients, $download_id );
}

/**
 *
 * Retrieves the commission rate for a product and user
 *
 * If $download_id is empty, the default rate from the user account is retrieved.
 * If no default rate is set on the user account, the global default is used.
 *
 * This function requires very strict typecasting to ensure the proper rates are used at all times.
 *
 * 0 is a permitted rate so we cannot use empty(). We always use NULL to check for non-existent values.
 *
 * @param  $download_id INT The ID of the download product to retrieve the commission rate for
 * @param  $user_id     INT The user ID to retrieve commission rate for
 * @return $rate        INT|FLOAT The commission rate
 */
function eddc_get_recipient_rate( $download_id = 0, $user_id = 0 ) {

	$rate = null;

	// Check for a rate specified on a specific product
	if( ! empty( $download_id ) ) {

		$settings   = get_post_meta( $download_id, '_edd_commission_settings', true );
		$rates      = array_map( 'trim', explode( ',', $settings['amount'] ) );
		$recipients = array_map( 'trim', explode( ',', $settings['user_id'] ) );
		$rate_key   = array_search( $user_id, $recipients );

		if( isset( $rates[ $rate_key ] ) ) {
			$rate = $rates[ $rate_key ];
		}

	}

	// Check for a user specific global rate
	if( ! empty( $user_id ) && ( null === $rate || '' === $rate ) ) {

		$rate = get_user_meta( $user_id, 'eddc_user_rate', true );

		if( '' === $rate ) {
			$rate = null;
		}

	}

	// Check for an overall global rate
	if( null === $rate && eddc_get_default_rate() ) {
		$rate = eddc_get_default_rate();
	}

	// Set rate to 0 if no rate was found
	if( null === $rate || '' === $rate ) {
		$rate = 0;
	}

	return apply_filters( 'eddc_get_recipient_rate', $rate, $download_id, $user_id );
}


function eddc_get_commission_type( $download_id = 0 ) {
	$settings = get_post_meta( $download_id, '_edd_commission_settings', true );
	$type     = isset( $settings['type'] ) ? $settings['type'] : 'percentage';
	return apply_filters( 'eddc_get_commission_type', $type, $download_id );
}


function eddc_get_cart_item_id( $cart_details, $download_id ) {

	foreach( (array) $cart_details as $postion => $item ) {
		if( $item['id'] == $download_id ) {
			return $postion;
		}
	}
	return null;
}

/**
 * Retrieve the Download IDs a user receives commissions for
 *
 * @access      public
 * @since       2.1
 * @return      array
 */
function eddc_get_download_ids_of_user( $user_id = 0 ) {
	if( empty( $user_id ) )
		return false;

	global $wpdb;

	$downloads = $wpdb->get_results( "SELECT post_id, meta_value AS settings FROM $wpdb->postmeta WHERE meta_key='_edd_commission_settings' AND meta_value LIKE '%{$user_id}%';" );

	foreach( $downloads as $key => $download ) {
		$settings = maybe_unserialize( $download->settings );
		$user_ids = explode( ',', $settings['user_id'] );

		if( ! in_array( $user_id, $user_ids ) ) {
			unset( $downloads[ $key ] );
		}
	}

	return wp_list_pluck( $downloads, 'post_id' );
}

function eddc_calc_commission_amount( $args ) {

	$defaults = array(
		'type' => 'percentage'
	);

	$args = wp_parse_args( $args, $defaults );

	if( 'flat' == $args['type'] ) {
		return $args['rate'];
	}

	if ( ! isset( $args['price'] ) || $args['price'] == false ) {
		$args['price'] = '0.00';
	}

	if ( $args['rate'] >= 1 ) {
		$amount = $args['price'] * ( $args['rate'] / 100 ); // rate format = 10 for 10%
	} else {
		$amount = $args['price'] * $args['rate']; // rate format set as 0.10 for 10%
	}

	return apply_filters( 'eddc_calc_commission_amount', $amount, $args );
}

function eddc_user_has_commissions( $user_id = false ) {

	if ( empty( $user_id ) )
		$user_id = get_current_user_id();

	$return = false;

	$args = array(
		'post_type' => 'edd_commission',
		'posts_per_page' => 1,
		'meta_query' => array(
			array(
				'key' => '_user_id',
				'value' => $user_id
			)
		),
		'fields' => 'ids'
	);

	$commissions = get_posts( $args );

	if ( $commissions ) {
		$return = true;
	}
	return apply_filters( 'eddc_user_has_commissions', $return, $user_id );
}

function eddc_get_commissions( $args = array() ) {

	$defaults = array(
		'user_id'    => false,
		'number'     => 30,
		'paged'      => 1,
		'query_args' => array(),
		'status'     => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$query = array(
		'post_type'      => 'edd_commission',
		'posts_per_page' => $args['number'],
		'paged'          => $args['paged'],
	);

	if ( ! empty( $args['order'] ) ) {
		$query['order'] = $args['order'];
	}

	if ( ! empty( $args['orderby'] ) ) {
		$query['orderby'] = $args['orderby'];
	}

	if ( ! empty( $args['status'] ) ) {

		$tax_query = array();

		if ( is_array( $args['status'] ) ) {

			$tax_query['relation'] = 'OR';

			foreach( $args['status'] as $status ) {

				$tax_query[] = array(
					'taxonomy' => 'edd_commission_status',
					'terms'    => $status,
					'field'    => 'slug',
				);

			}

		} else {
			$tax_query[] = array(
					'taxonomy' => 'edd_commission_status',
					'terms'    => $args['status'],
					'field'    => 'slug',
			);
		}

		if ( ! empty( $tax_query ) ) {
			$query['tax_query'] = $tax_query;
		}

	}

	$meta_query = array();

	if ( $args['user_id'] ) {

		$meta_query[] = array(
			'key'   => '_user_id',
			'value' => $args['user_id']
		);

	}

	if ( isset( $args['renewal'] ) ) {

		switch( $args['renewal'] ) {

			case true:
				$meta_query[] = array(
					'key'   => '_edd_commission_is_renewal',
					'value' => '1'
				);
			break;
			case false:
				$meta_query[] = array(
					'key'     => '_edd_commission_is_renewal',
					'compare' => 'NOT EXISTS',
				);
			break;

		}

	}

	if ( ! empty( $meta_query ) ) {
		$query['meta_query'] = $meta_query;
	}

	$query = array_merge( $query, $args['query_args'] );

	$commissions = get_posts( $query );

	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions

}

function eddc_get_unpaid_commissions( $args = array() ) {

	$defaults = array(
		'user_id'    => false,
		'number'     => 30,
		'paged'      => 1,
		'query_args' => array(),
	);

	$args = wp_parse_args( $args, $defaults );
	$args['status'] = 'unpaid';

	$commissions = eddc_get_commissions( $args );

	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions

}

function eddc_get_paid_commissions( $args = array() ) {

	$defaults = array(
		'user_id'    => false,
		'number'     => 30,
		'paged'      => 1,
		'query_args' => array(),
	);

	$args = wp_parse_args( $args, $defaults );
	$args['status'] = 'paid';

	$commissions = eddc_get_commissions( $args );

	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions

}

function eddc_get_revoked_commissions( $args = array() ) {

	$defaults = array(
		'user_id'    => false,
		'number'     => 30,
		'paged'      => 1,
		'query_args' => array(),
	);

	$args = wp_parse_args( $args, $defaults );
	$args['status'] = 'revoked';

	$commissions = eddc_get_commissions( $args );

	if ( $commissions ) {
		return $commissions;
	}
	return false; // no commissions

}


function eddc_count_user_commissions( $user_id = false, $status = 'unpaid' ) {

	$args = array(
		'post_type'      => 'edd_commission',
		'nopaging'       => true,
		'tax_query'      => array(
			array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => $status,
				'field'    => 'slug'
			)
		)
	);

	if ( $user_id ) {

		$args['meta_query'] = array(
			array(
				'key'   => '_user_id',
				'value' => $user_id
			)
		);

	}

	$commissions = new WP_Query( $args );

	if ( $commissions ) {
		return $commissions->post_count;
	}
	return false; // no commissions
}

function eddc_get_unpaid_totals( $user_id = 0 ) {

	$unpaid = eddc_get_unpaid_commissions( array( 'user_id' => $user_id, 'number' => -1 ) );
	$total = (float) 0;
	if ( $unpaid ) {
		foreach ( $unpaid as $commission ) {
			$commission_info = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$total += $commission_info['amount'];
		}
	}
	return edd_sanitize_amount( $total );
}


function eddc_get_paid_totals( $user_id = 0 ) {

	$paid = eddc_get_paid_commissions( array( 'user_id' => $user_id, 'number' => -1 ) );
	$total = (float) 0;
	if ( $paid ) {
		foreach ( $paid as $commission ) {
			$commission_info = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$total += $commission_info['amount'];
		}
	}
	return edd_sanitize_amount( $total );
}

function eddc_get_revoked_totals( $user_id = 0 ) {

	$revoked = eddc_get_revoked_commissions( array( 'user_id' => $user_id, 'number' => -1 ) );
	$total = (float) 0;
	if ( $revoked ) {
		foreach ( $revoked as $commission ) {
			$commission_info = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$total += $commission_info['amount'];
		}
	}
	return edd_sanitize_amount( $total );
}

function edd_get_commissions_by_date( $day = null, $month = null, $year = null, $hour = null, $user = 0  ) {

	$args = array(
		'post_type'      => 'edd_commission',
		'posts_per_page' => -1,
		'year'           => $year,
		'monthnum'       => $month,
		'tax_query'      => array(
			array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => 'revoked',
				'field'    => 'slug',
				'operator' => 'NOT IN'
			)
		)
	);

	if ( ! empty( $day ) ) {
		$args['day'] = $day;
	}

	if ( ! empty( $hour ) ) {
		$args['hour'] = $hour;
	}

	if( ! empty( $user ) ) {
		$args['meta_key']   = '_user_id';
		$args['meta_value'] = absint( $user );
	}

	$args = apply_filters( 'edd_get_commissions_by_date', $args, $day, $month, $year, $user );

	$commissions = get_posts( $args );

	$total = 0;
	if ( $commissions ) {
		foreach ( $commissions as $commission ) {
			$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );
			$amount = $commission_meta['amount'];
			$total  = $total + $amount;
		}
	}
	return edd_sanitize_amount( $total );
}


function eddc_generate_payout_file( $data ) {
	if ( wp_verify_nonce( $data['eddc-payout-nonce'], 'eddc-payout-nonce' ) ) {

		$from = ! empty( $data['from'] ) ? sanitize_text_field( $data['from'] ) : date( 'm/d/Y', strtotime( '-1 month' ) );
		$to   = ! empty( $data['to'] )   ? sanitize_text_field( $data['to'] )   : date( 'm/d/Y' );

		$from = explode( '/', $from );
		$to   = explode( '/', $to );

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
			)
		);

		$commissions = eddc_get_unpaid_commissions( $args );

		if ( $commissions ) {

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=edd-commission-payout-' . date( 'm-d-Y' ) . '.csv' );
			header( "Pragma: no-cache" );
			header( "Expires: 0" );

			$payouts = array();

			foreach ( $commissions as $commission ) {

				$commission_meta = get_post_meta( $commission->ID, '_edd_commission_info', true );

				$user_id       = $commission_meta['user_id'];
				$user          = get_userdata( $user_id );
				$custom_paypal = get_user_meta( $user_id, 'eddc_user_paypal', true );
				$email         = is_email( $custom_paypal ) ? $custom_paypal : $user->user_email;

				if ( array_key_exists( $email, $payouts ) ) {
					$payouts[$email]['amount'] += $commission_meta['amount'];
				} else {
					$payouts[$email] = array(
						'amount'     => $commission_meta['amount'],
						'currency'   => $commission_meta['currency']
					);
				}

				eddc_set_commission_status( $commission->ID, 'paid' );

			}

			if ( $payouts ) {
				foreach ( $payouts as $key => $payout ) {

					echo $key . ",";
					echo edd_sanitize_amount( number_format( $payout['amount'], 2 ) ) . ",";
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

function eddc_generate_user_export_file( $data ) {

	$user_id = ! empty( $data['user_id'] ) ? intval( $data['user_id'] ) : get_current_user_id();

	if ( ( empty( $user_id ) || ! eddc_user_has_commissions( $user_id ) ) ) {
		return;
	}

	include_once EDDC_PLUGIN_DIR . 'includes/class-commissions-export.php';
	$export = new EDD_Commissions_Export();
	$export->user_id = $user_id;
	$export->year    = $data['year'];
	$export->month   = $data['month'];
	$export->export();
}
add_action( 'edd_generate_commission_export', 'eddc_generate_user_export_file' );


/**
 * Store a payment note about this commission
 *
 * This makes it really easy to find commissions recorded for a specific payment.
 * Especially useful for when payments are refunded
 *
 * @access      private
 * @since       2.0
 * @return      void
 */
function eddc_record_commission_note( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {

	$note = sprintf(
		__( 'Commission of %s recorded for %s &ndash; <a href="%s">View</a>', 'eddc' ),
		edd_currency_filter( edd_format_amount( $commission_amount ) ),
		get_userdata( $recipient )->display_name,
		admin_url( 'edit.php?post_type=download&page=edd-commissions&payment=' . $payment_id )
	);

	edd_insert_payment_note( $payment_id, $note );
}
add_action( 'eddc_insert_commission', 'eddc_record_commission_note', 10, 6 );


/**
 * Gets the default commission rate
 *
 * @access      private
 * @since       2.1
 * @return      float
 */
function eddc_get_default_rate() {
	global $edd_options;
	$rate = isset( $edd_options['edd_commissions_default_rate'] ) ? $edd_options['edd_commissions_default_rate'] : false;
	return apply_filters( 'eddc_default_rate', $rate );
}


/**
 * Filters get_post_meta() to ensure old commission status checks don't fail
 *
 * The status for commission records used to be stored in postmeta, now it's stored in a taxonomy
 *
 * @access      private
 * @since       2.8
 * @return      mixed
 */
function eddc_filter_post_meta_for_status( $check, $object_id, $meta_key, $single ) {

	if( defined( 'EDDC_DOING_UPGRADES' ) ) {
		return $check;
	}

	if( '_commission_status' === $meta_key ) {

		if( has_term( 'paid', 'edd_commission_status', $object_id ) ) {

			return 'paid';

		} else {

			return 'unpaid';

		}

	}

	return $check;

}
add_filter( 'get_post_metadata', 'eddc_filter_post_meta_for_status', 10, 4 );
