<?php
/**
 * Email functions
 *
 * @since 3.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


/**
 * Retrieve default email body
 *
 * @since       3.0
 * @return      string $body The default email
 */
function eddc_get_email_default_body() {
    $from_name = edd_get_option( 'from_name', get_bloginfo( 'name' ) );
	$message   = __( 'Hello {name},', 'eddc' ) . "\n\n" . sprintf( __( 'You have made a new sale on %s!', 'eddc' ), stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) ) . "\n\n";
	$message  .= __( 'Item sold: ', 'eddc' ) . "{download}\n\n";
	$message  .= __( 'Amount: ', 'eddc' ) . "{amount}\n\n";
	$message  .= __( 'Commission Rate: ', 'eddc' ) . "{rate}%\n\n";
    $message  .= __( 'Thank you', 'eddc' );

    return apply_filters( 'eddc_email_default_body', $message );
}


/**
 * Parse template tags for display
 *
 * @since       3.0
 * @return      string $tags The parsed template tags
 */
function eddc_display_email_template_tags() {
    $template_tags = eddc_get_email_template_tags();
    $tags = '';

    foreach( $template_tags as $template_tag ) {
        $tags .= '{' . $template_tag['tag'] . '} - ' . $template_tag['description'] . '<br />';
    }

    return $tags;
}


/**
 * Retrieve email template tags
 *
 * @since       3.0
 * @return      array $tags The email template tags
 */
function eddc_get_email_template_tags() {
    $tags = array(
        array(
            'tag'           => 'download',
            'description'   => sprintf( __( 'The name of the purchased %s', 'eddc' ), edd_get_label_singular() ),
        ),
        array(
            'tag'           => 'amount',
            'description'   => sprintf( __( 'The value of the purchased %s', 'eddc' ), edd_get_label_singular() ),
        ),
        array(
            'tag'           => 'date',
            'description'   => __( 'The date of the purchase', 'eddc' ),
        ),
        array(
            'tag'           => 'rate',
            'description'   => __( 'The commission rate of the user', 'eddc' ),
        ),
        array(
            'tag'           => 'name',
            'description'   => __( 'The first name of the user', 'eddc' ),
        ),
        array(
            'tag'           => 'fullname',
            'description'   => __( 'The full name of the user', 'eddc' ),
        )
    );

    return apply_filters( 'eddc_email_template_tags', $tags );
}


/**
 * Parse email template tags
 *
 * @since       3.0
 * @param       string $message The email body
 * @param       int $download_id The ID for a given download
 * @param       int $commission_id The ID of this commission
 * @param       int $commission_amount The amount of the commission
 * @param       int $rate The commission rate of the user
 * @return      string $message The email body
 */
function eddc_parse_template_tags( $message, $download_id, $commission_id, $commission_amount, $rate ) {
    $meta      = get_post_meta( $commission_id, '_edd_commission_info', true );
    $variation = get_post_meta( $commission_id, '_edd_commission_download_variation', true );
    $download  = get_the_title( $download_id ) . ( ! empty( $variation ) ? ' - ' . $variation : '' );
    $amount    = html_entity_decode( edd_currency_filter( edd_format_amount( $commission_amount ) ) );
    $date      = date_i18n( get_option( 'date_format' ), strtotime( get_post_field( 'post_date', $commission_id ) ) );
    $user      = get_userdata( $meta['user_id'] );

    if( ! empty( $user->first_name ) ) {
        $name = $user->first_name;

        if( ! empty( $user->last_name ) ) {
            $fullname = $name . ' ' . $user->last_name;
        } else {
            $fullname = $name;
        }
    } else {
        $name = $user->display_name;
        $fullname = $name;
    }
        
    $message   = str_replace( '{download}', $download, $message );
    $message   = str_replace( '{amount}', $amount, $message );
    $message   = str_replace( '{date}', $date, $message );
    $message   = str_replace( '{rate}', $rate, $message );
    $message   = str_replace( '{name}', $name, $message );
    $message   = str_replace( '{fullname}', $fullname, $message );

    return $message;
}


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
	$user      = get_userdata( $user_id );
    $email     = $user->user_email; // set address here
    $subject   = edd_get_option( 'edd_commissions_email_subject', __( 'New Sale!', 'eddc' ) );
    $message   = edd_get_option( 'edd_commissions_email_message', eddc_get_email_default_body() );
    
    // Parse template tags
    $message   = eddc_parse_template_tags( $message, $download_id, $commission_id, $commission_amount, $rate );
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
