<?php
/**
 * Email functions
 *
 * @since 2.9.2
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


/**
 * Retrieve default email body
 *
 * @since       2.9.2
 * @return      string $body The default email
 */
function eddc_get_email_default_body() {
    $from_name = edd_get_option( 'from_name', get_bloginfo( 'name' ) );
	$message   = __( 'Hello', 'eddc' ) . "\n\n" . sprintf( __( 'You have made a new sale on %s!', 'eddc' ), stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) ) . "\n\n";
	$message  .= __( 'Item sold: ', 'eddc' ) . "{download}\n\n";
	$message  .= __( 'Amount: ', 'eddc' ) . "{amount}\n\n";
	$message  .= __( 'Commission Rate: ', 'eddc' ) . "{rate}%\n\n";
    $message  .= __( 'Thank you', 'eddc' );

    return apply_filters( 'eddc_email_default_body', $message );
}


/**
 * Parse template tags for display
 *
 * @since       2.9.2
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
 * @since       2.9.2
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
        )
    );

    return apply_filters( 'eddc_email_template_tags', $tags );
}


/**
 * Parse email template tags
 *
 * @since       2.9.2
 * @param       string $message The email body
 * @param       int $download_id The ID for a given download
 * @param       int $commission_id The ID of this commission
 * @param       int $commission_amount The amount of the commission
 * @param       int $rate The commission rate of the user
 * @return      string $message The email body
 */
function eddc_parse_template_tags( $message, $download_id, $commission_id, $commission_amount, $rate ) {
    $variation = get_post_meta( $commission_id, '_edd_commission_download_variation', true );
    $download  = get_the_title( $download_id ) . ( ! empty( $variation ) ? ' - ' . $variation : '' );
    $amount    = html_entity_decode( edd_currency_filter( edd_format_amount( $commission_amount ) ) );
    $date      = date_i18n( get_option( 'date_format' ), strtotime( get_post_field( 'post_date', $commission_id ) ) );

    $message   = str_replace( '{download}', $download, $message );
    $message   = str_replace( '{amount}', $amount, $message );
    $message   = str_replace( '{date}', $date, $message );
    $message   = str_replace( '{rate}', $rate, $message );

    return $message;
}
