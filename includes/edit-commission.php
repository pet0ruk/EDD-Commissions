<?php

$commission = $_GET['commission'];
$commission_info = get_post_meta( $commission, '_edd_commission_info', true);
?>
<form id="edit-field" method="post">
	<table class="form-table">
		<tbody>
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
		<?php echo wp_nonce_field('edd_sl_edit_nonce', 'edd_sl_edit_nonce'); ?>
		<input type="hidden" name="edd-action" value="edit_commission"/>
		<input type="hidden" name="commission" value="<?php echo $commission; ?>"/>
		<input type="submit" class="button-primary" value="<?php _e('Update', 'eddc'); ?>"/>
	</p>
</form>