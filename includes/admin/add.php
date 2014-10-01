<h2><?php _e('Add Commission', 'eddc'); ?></h2>
<form id="add-commission" method="post">
	<table class="form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="user_id"><?php _e('User ID', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="user_id" name="user_id" value=""/>
					<p class="description"><?php _e('The ID of the user that received this commission', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="download_id"><?php _e('Download ID', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="download_id" name="download_id" value=""/>
					<p class="description"><?php _e('The ID of the product this commission was for', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="payment_id_id"><?php _e('Payment ID', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="payment_id_id" name="payment_id_id" value=""/>
					<p class="description"><?php _e('The payment ID this commission is related to (optional).', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="rate"><?php _e('Rate', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="rate" name="rate" value=""/>
					<p class="description"><?php _e('The percentage rate of this commission', 'eddc'); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row" valign="top">
					<label for="amount"><?php _e('Amount', 'eddc'); ?></label>
				</th>
				<td>
					<input type="text" id="amount" name="amount" value=""/>
					<p class="description"><?php _e('The total amount of this commission', 'eddc'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<?php echo wp_nonce_field('eddc_add_nonce', 'eddc_add_nonce'); ?>
		<input type="hidden" name="edd-action" value="add_commission"/>
		<input type="submit" class="button-primary" value="<?php _e('Add Commission', 'eddc'); ?>"/>
	</p>
</form>