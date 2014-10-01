<?php

$commission = absint( $_GET['commission'] );
$commission_info = get_post_meta( $commission, '_edd_commission_info', true);
?>
<h2><?php _e('Edit Commission', 'eddc'); ?></h2>
<form id="edit-field" method="post">
	<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="user_id"><?php _e('User ID', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="user_id" name="user_id" value="<?php echo $commission_info['user_id']; ?>"/>
					<p class="description"><?php _e('The ID of the user that received this commission', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="download_id"><?php _e('Download ID', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="download_id" name="download_id" value="<?php echo get_post_meta( $commission, '_download_id', true ); ?>"/>
					<p class="description"><?php _e('The ID of the product this commission was for', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rate"><?php _e('Rate', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="rate" name="rate" value="<?php echo $commission_info['rate']; ?>"/>
					<p class="description"><?php _e('The percentage rate of this commission', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="amount"><?php _e('Amount', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="amount" name="amount" value="<?php echo $commission_info['amount']; ?>"/>
					<p class="description"><?php _e('The total amount of this commission', 'eddc'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<?php echo wp_nonce_field('eddc_edit_nonce', 'eddc_edit_nonce'); ?>
		<input type="hidden" name="edd-action" value="edit_commission"/>
		<input type="hidden" name="commission" value="<?php echo $commission; ?>"/>
		<input type="submit" class="button-primary" value="<?php _e('Update', 'eddc'); ?>"/>
	</p>
</form>