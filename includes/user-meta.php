<?php

function eddc_user_paypal_email( $user ) { 
	?>
	<h3><?php _e('Easy Digital Downloads Commissions', 'eddc'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label><?php _e('User\'s PayPal Email', 'eddc'); ?></label></th>
			<td>
				<input type="email" name="eddc_user_paypal" id="eddc_user_paypal" class="regular-text" value="<?php echo get_user_meta( $user->ID, 'eddc_user_paypal', true ); ?>" />
				<span class="description"><?php _e('If the user\'s PayPal address is different than their account email, enter it here.', 'eddc'); ?></span>
			</td>
		</tr>	
	</table>
	<?php 
}
add_action( 'show_user_profile', 'eddc_user_paypal_email' );
add_action( 'edit_user_profile', 'eddc_user_paypal_email' );


function eddc_save_user_paypal( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	if( is_email( $_POST['eddc_user_paypal'] ) ) {
		update_usermeta( $user_id, 'eddc_user_paypal', $_POST['eddc_user_paypal'] );
	}
}
add_action( 'personal_options_update', 'eddc_save_user_paypal' );
add_action( 'edit_user_profile_update', 'eddc_save_user_paypal' );