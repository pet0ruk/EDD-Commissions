<?php

function eddc_add_commission_meta_box() {

	if( current_user_can( 'manage_shop_settings' ) ) {
		add_meta_box( 'edd_download_commissions', __( 'Commission', 'edd' ), 'eddc_render_commissions_meta_box', 'download', 'side', 'core' );
	}
}
add_action( 'add_meta_boxes', 'eddc_add_commission_meta_box', 100 );

// render the download information meta box
function eddc_render_commissions_meta_box() {
	global $post;

	// Use nonce for verification
	echo '<input type="hidden" name="edd_download_commission_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	echo '<table class="form-table">';

	$enabled = get_post_meta( $post->ID, '_edd_commisions_enabled', true ) ? true : false;
	$meta    = get_post_meta( $post->ID, '_edd_commission_settings', true );
	$user_id = isset( $meta['user_id'] ) ? $meta['user_id'] : '';
	$amount  = isset( $meta['amount']  ) ? $meta['amount']  : '';
	$type    = isset( $meta['type']    ) ? $meta['type']    : 'percentage';

	$display = $enabled ? '' : ' style="display:none";';

	$script = '<script type="text/javascript">';
	$script .= 'jQuery(document).ready(function($){';
		$script .= '$("#edd_commisions_enabled").change(function(){';
			$script .= '$(".eddc_commission_row").toggle();';
		$script .= '});';
	$script .= '});';
	$script .= '</script>';

	echo $script;

	echo '<tr>';
		echo '<td class="edd_field_type_text" colspan="2">';
			echo '<input type="checkbox" name="edd_commisions_enabled" id="edd_commisions_enabled" value="1" ' . checked( true, $enabled, false ) . '/>&nbsp;';
			echo __( 'Check to enable commissions', 'eddc' );
		echo '<td>';
	echo '</tr>';

	echo '<tr' . $display . ' class="eddc_commission_row">';
		echo '<th style="width:20%"><label>' . __ ( 'Type', 'eddc' ) . '</label></th>';
		echo '<td class="edd_field_type_text">';
			echo '<input type="radio" name="edd_commission_settings[type]" value="percentage"' . checked( $type, 'percentage', false ) . '/>&nbsp;' . __( 'Percentage', 'eddc' ) . '<br/>';
			echo '<input type="radio" name="edd_commission_settings[type]" value="flat"' . checked( $type, 'flat', false ) . '/>&nbsp;' . __( 'Flat', 'eddc' ) . '<br/>';
			echo __( 'Select the type of commission(s) to record.', 'eddc' );
		echo '<td>';
	echo '</tr>';

	echo '<tr' . $display . ' class="eddc_commission_row">';
		echo '<th style="width:20%"><label for="edd_commission_user">' . __ ( 'User(s)', 'eddc' ) . '</label></th>';
		echo '<td class="edd_field_type_text">';
			echo '<input type="text" name="edd_commission_settings[user_id]" id="edd_commission_user" value="' . $user_id . '"/><br/>';
			echo __( 'Enter the user ID that should receive a commission of each sale. Separate user IDs by a comma.', 'eddc' );
		echo '<td>';
	echo '</tr>';

	echo '<tr' . $display . ' class="eddc_commission_row">';
		echo '<th style="width:20%"><label for="edd_commission_amount">' . __ ( 'Rate(s)', 'eddc' ) . '</label></th>';
		echo '<td class="edd_field_type_text">';
			echo '<input type="text" name="edd_commission_settings[amount]" id="edd_commission_amount" value="' . $amount . '"/><br/>';
			echo __( 'Enter the amount the user(s) should receive of each sale. Separate rates by a comma.', 'eddc' );
		echo '<td>';
	echo '</tr>';

	echo '</table>';
}

// Save data from meta box
function eddc_download_meta_box_save( $post_id ) {
	global $post;

	// verify nonce
	if ( ! isset( $_POST['edd_download_commission_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['edd_download_commission_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// Check for auto save / bulk edit
	if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return $post_id;
	}

	if ( isset( $_POST['post_type'] ) && 'download' != $_POST['post_type'] ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return $post_id;
	}

	if ( isset( $_POST['edd_commisions_enabled'] ) ) {

		update_post_meta( $post_id, '_edd_commisions_enabled', true );

		$new = isset( $_POST['edd_commission_settings'] ) ? $_POST['edd_commission_settings'] : false;
		if ( $new ) {
			if( ! empty( $new['amount'] ) ) {
				$new['amount'] = str_replace( '%', '', $new['amount'] );
				$new['amount'] = str_replace( '$', '', $new['amount'] );
				if ( $new['amount'] < 1 )
					$new['amount'] = $new['amount'] * 100;
				$new['amount'] = trim( $new['amount'] );
			}
		}
		update_post_meta( $post_id, '_edd_commission_settings', $new );

	} else {
		delete_post_meta( $post_id, '_edd_commisions_enabled' );
	}
}
add_action( 'save_post', 'eddc_download_meta_box_save' );
