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

	$payment_data  	= edd_get_payment_meta( $payment_id );
	$user_info   	= maybe_unserialize( $payment_data['user_info'] );
	$cart_details  	= edd_get_payment_meta_cart_details( $payment_id );

	// loop through each purchased download and award commissions, if needed
	foreach ( $cart_details as $download ) {

		$download_id    		= absint( $download['id'] );
		$commissions_enabled  	= get_post_meta( $download_id, '_edd_commisions_enabled', true );

		if ( 'subtotal' == edd_get_option( 'edd_commissions_calc_base', 'subtotal' ) ) {

			$price = $download['subtotal'];

		} else {

			$price = $download['price'];

		}

		if( ! empty( $download['fees'] ) ) {
			foreach( $download['fees'] as $fee ) {
				$price += $fee['amount'];
			}
		}

		// if we need to award a commission, and the price is greater than zero
		if ( $commissions_enabled && floatval( $price ) > '0' ) {

			// set a flag so downloads with commissions awarded are easy to query
			update_post_meta( $download_id, '_edd_has_commission', true );

			$commission_settings = get_post_meta( $download_id, '_edd_commission_settings', true );

			if ( $commission_settings ) {

				$type = eddc_get_commission_type( $download_id );

				// but if we have price variations, then we need to get the name of the variation
				if ( edd_has_variable_prices( $download_id ) ) {
					$price_id = edd_get_cart_item_price_id ( $download );
					$variation = edd_get_price_option_name( $download_id, $price_id );
				}


				$recipients = eddc_get_recipients( $download_id );

				// Record a commission for each user
				foreach( $recipients as $recipient ) {

					$rate           	= eddc_get_recipient_rate( $download_id, $recipient );    // percentage amount of download price
					$args               = array(
						'price'         => $price,
						'rate'          => $rate,
						'type'          => $type,
						'download_id'   => $download_id,
						'recipient'     => $recipient,
						'payment_id'    => $payment_id
					);

					$commission_amount 	= eddc_calc_commission_amount( $args ); // calculate the commission amount to award
					$currency    		= $payment_data['currency'];

					$commission = array(
						'post_type'  	=> 'edd_commission',
						'post_title'  	=> $user_info['email'] . ' - ' . get_the_title( $download_id ),
						'post_status'  	=> 'publish'
					);

					$commission_id = wp_insert_post( apply_filters( 'edd_commission_post_data', $commission ) );

					$commission_info = apply_filters( 'edd_commission_info', array(
						'user_id'  	=> $recipient,
						'rate'   	=> $rate,
						'amount'  	=> $commission_amount,
						'currency'  => $currency
					), $commission_id, $payment_id, $download_id );

					eddc_set_commission_status( $commission_id, 'unpaid' );

					update_post_meta( $commission_id, '_edd_commission_info', $commission_info );
					update_post_meta( $commission_id, '_download_id', $download_id );
					update_post_meta( $commission_id, '_user_id', $recipient );
					update_post_meta( $commission_id, '_edd_commission_payment_id', $payment_id );
					//if we are dealing with a variation, then save variation info
					if ( isset( $variation ) ) {
						update_post_meta( $commission_id, '_edd_commission_download_variation', $variation );
					}

					do_action( 'eddc_insert_commission', $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id );
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


function eddc_get_recipients( $download_id = 0 ) {
	$settings = get_post_meta( $download_id, '_edd_commission_settings', true );
	$recipients = array_map( 'trim', explode( ',', $settings['user_id'] ) );
	return (array) apply_filters( 'eddc_get_recipients', $recipients, $download_id );
}


function eddc_get_recipient_rate( $download_id = 0, $user_id = 0 ) {

	// Check for a rate specified on a specific product
	if( ! empty( $download_id ) ) {

		$settings   = get_post_meta( $download_id, '_edd_commission_settings', true );
		$rates      = array_map( 'trim', explode( ',', $settings['amount'] ) );
		$recipients = array_map( 'trim', explode( ',', $settings['user_id'] ) );
		$rate_key   = array_search( $user_id, $recipients );

		if( ! empty( $rates[ $rate_key ] ) ) {
			$rate = $rates[ $rate_key ];
		} else {
			$rate = 0;
		}

	}

	// Check for a user specific global rate
	if( empty( $download_id ) || empty( $rate ) ) {

		$rate = get_user_meta( $user_id, 'eddc_user_rate', true );

		if( empty( $rate ) ) {
			$rate = 0;
		}

	}

	// Check for an overall global rate
	if( empty( $rate ) && eddc_get_default_rate() ) {
		$rate = eddc_get_default_rate();
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

function eddc_get_unpaid_commissions( $args = array() ) {

	$defaults = array(
		'user_id'    => false,
		'number'     => 30,
		'paged'      => 1,
		'query_args' => array()
	);

	$args = wp_parse_args( $args, $defaults );

	$query = array(
		'post_type'      => 'edd_commission',
		'posts_per_page' => $args['number'],
		'paged'          => $args['paged'],
		'tax_query'      => array(
			array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => 'unpaid',
				'field'    => 'slug'
			)
		)
	);

	if ( $args['user_id'] ) {

		$query['meta_query'] = array(
			array(
				'key'   => '_user_id',
				'value' => $args['user_id']
			)
		);

	}

	$query = array_merge( $query, $args['query_args'] );

	//echo '<pre>';print_r( $query ); echo '</pre>';exit;

	$commissions = get_posts( $query );

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
		'query_args' => array()
	);

	$args = wp_parse_args( $args, $defaults );

	$query = array(
		'post_type'      => 'edd_commission',
		'posts_per_page' => $args['number'],
		'paged'          => $args['paged'],
		'tax_query'      => array(
			array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => 'paid',
				'field'    => 'slug'
			)
		)
	);

	if ( $args['user_id'] ) {

		$query['meta_query'] = array(
			array(
				'key'   => '_user_id',
				'value' => $args['user_id']
			)
		);

	}

	$query = array_merge( $query, $args['query_args'] );

	$commissions = get_posts( $query );

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
		'query_args' => array()
	);

	$args = wp_parse_args( $args, $defaults );

	$query = array(
		'post_type'      => 'edd_commission',
		'posts_per_page' => $args['number'],
		'paged'          => $args['paged'],
		'tax_query'      => array(
			array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => 'revoked',
				'field'    => 'slug'
			)
		)
	);

	if ( $args['user_id'] ) {

		$query['meta_query'] = array(
			array(
				'key'   => '_user_id',
				'value' => $args['user_id']
			)
		);

	}

	$query = array_merge( $query, $args['query_args'] );

	$commissions = get_posts( $query );

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

	$revoked = eddc_get_paid_commissions( array( 'user_id' => $user_id, 'number' => -1 ) );
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
	if ( ! is_user_logged_in() || ! eddc_user_has_commissions() ) {
		return;
	}

	include_once EDDC_PLUGIN_DIR . 'includes/class-commissions-export.php';

	$export = new EDD_Commissions_Export();
	$export->user_id = get_current_user_id();
	$export->year    = $data['year'];
	$export->month   = $data['month'];
	$export->export();

}
add_action( 'edd_generate_commission_export', 'eddc_generate_user_export_file' );


/**
 * Email Sale Alert
 *
 * Email an alert about the sale to the user receiving a commission
 *
 * @access      private
 * @since       1.1.0
 * @return      void
 */

function eddc_email_alert( $user_id, $commission_amount, $rate, $download_id, $commission_id ) {
	global $edd_options;

	/* send an email alert of the sale */
	$from_name = isset( $edd_options['from_name'] ) ? $edd_options['from_name'] : get_bloginfo( 'name' );
	$user      = get_userdata( $user_id );
	$email     = $user->user_email; // set address here
	$subject   = __( 'New Sale!', 'eddc' );
	$message   = __( 'Hello', 'eddc' ) . "\n\n" . sprintf( __( 'You have made a new sale on %s!', 'eddc' ), stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) ) . ".\n\n";
	$variation = get_post_meta( $commission_id, '_edd_commission_download_variation', true );
	$message  .= __( 'Item sold: ', 'eddc' ) . get_the_title( $download_id ) . ( ! empty( $variation ) ? ' - ' . $variation : '' ) . "\n\n";
	$message  .= __( 'Amount: ', 'eddc' ) . " " . html_entity_decode( edd_currency_filter( edd_format_amount( $commission_amount ) ) ) . "\n\n";
	$message  .= __( 'Commission Rate: ', 'eddc' ) . $rate . "%\n\n";
	$message  .= __( 'Thank you', 'eddc' );
	$message   = apply_filters( 'eddc_sale_alert_email', $message, $user_id, $commission_amount, $rate, $download_id, $commission_id );

	if( class_exists( 'EDD_Emails' ) ) {

		EDD()->emails->__set( 'heading', $subject );
		EDD()->emails->send( $email, $subject, $message );

	} else {

		$from_name = apply_filters( 'eddc_email_from_name', $from_name, $user_id, $commission_amount, $rate, $download_id );

		$from_email = isset( $edd_options['from_email'] ) ? $edd_options['from_email'] : get_option( 'admin_email' );
		$from_email = apply_filters( 'eddc_email_from_email', $from_email, $user_id, $commission_amount, $rate, $download_id );

		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";

		wp_mail( $email, $subject, $message, $headers );

	}
}
add_action( 'eddc_insert_commission', 'eddc_email_alert', 10, 5 );


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
