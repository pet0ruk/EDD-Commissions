<?php

function eddc_user_commissions( ) {

	global $user_ID;

	if( !is_user_logged_in() )
		return;

	$unpaid_commissions = eddc_get_unpaid_commissions( $user_ID );
	$paid_commissions 	= eddc_get_paid_commissions( $user_ID );
	$stats 				= '';
	if( ! empty( $unpaid_commissions ) || ! empty( $paid_commissions ) ) : // only show tables if user has commission data
		ob_start(); ?>
			<div id="edd_user_commissions">

				<!-- unpaid -->
				<div id="edd_user_commissions_unpaid">
					<h3 class="edd_user_commissions_header"><?php _e('Unpaid Commissions', 'eddc'); ?></h3>
					<table id="edd_user_unpaid_commissions_table" class="edd_user_commissions">
						<thead>
							<tr class="edd_user_commission_row">
								<?php do_action( 'eddc_user_commissions_unpaid_head_row_begin' ); ?>
								<th class="edd_commission_item"><?php _e('Item', 'eddc'); ?></th>
								<th class="edd_commission_amount"><?php _e('Amount', 'eddc'); ?></th>
								<th class="edd_commission_rate"><?php _e('Rate', 'eddc'); ?></th>
								<th class="edd_commission_date"><?php _e('Date', 'eddc'); ?></th>
								<?php do_action( 'eddc_user_commissions_unpaid_head_row_end' ); ?>
							</tr>
						</thead>
						<tbody>
						<?php $total = (float) 0; ?>
						<?php if( ! empty( $unpaid_commissions ) ) : ?>
							<?php foreach( $unpaid_commissions as $commission ) : ?>
								<tr class="edd_user_commission_row">
									<?php
									do_action( 'eddc_user_commissions_unpaid_row_begin', $commission );
									$item_name 			= get_the_title( get_post_meta( $commission->ID, '_download_id', true ) );
									$commission_info 	= get_post_meta( $commission->ID, '_edd_commission_info', true );
									$amount 			= $commission_info['amount'];
									$rate 				= $commission_info['rate'];
									$total 				+= $amount;
									?>
									<td class="edd_commission_item"><?php echo esc_html( $item_name ); ?></td>
									<td class="edd_commission_amount"><?php echo edd_currency_filter( $amount ); ?></td>
									<td class="edd_commission_rate"><?php echo $rate . '%'; ?></td>
									<td class="edd_commission_date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $commission->post_date ) ); ?></td>
									<?php do_action( 'eddc_user_commissions_unpaid_row_end', $commission ); ?>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr class="edd_user_commission_row edd_row_empty">
								<td colspan="4"><?php _e('No unpaid commissions', 'eddc'); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>
					<div id="edd_user_commissions_unpaid_total"><?php _e('Total unpaid:', 'eddc');?>&nbsp;<?php echo edd_currency_filter( $total ); ?></div>
				</div><!--end #edd_user_commissions_unpaid-->

				<!-- paid -->
				<div id="edd_user_commissions_paid">
					<h3 class="edd_user_commissions_header"><?php _e('Paid Commissions', 'eddc'); ?></h3>
					<table id="edd_user_paid_commissions_table" class="edd_user_commissions">
						<thead>
							<tr class="edd_user_commission_row">
								<?php do_action( 'eddc_user_commissions_paid_head_row_begin' ); ?>
								<th class="edd_commission_item"><?php _e('Item', 'eddc'); ?></th>
								<th class="edd_commission_amount"><?php _e('Amount', 'eddc'); ?></th>
								<th class="edd_commission_rate"><?php _e('Rate', 'eddc'); ?></th>
								<th class="edd_commission_date"><?php _e('Date', 'eddc'); ?></th>
								<?php do_action( 'eddc_user_commissions_paid_head_row_end' ); ?>
							</tr>
						</thead>
						<tbody>
						<?php $total = (float) 0; ?>
						<?php if( ! empty( $paid_commissions ) ) : ?>
							<?php foreach( $paid_commissions as $commission ) : ?>
								<tr class="edd_user_commission_row">
									<?php
									do_action( 'eddc_user_commissions_paid_row_begin', $commission );
									$item_name 			= get_the_title( get_post_meta( $commission->ID, '_download_id', true ) );
									$commission_info 	= get_post_meta( $commission->ID, '_edd_commission_info', true );
									$amount 			= $commission_info['amount'];
									$rate 				= $commission_info['rate'];
									$total 				+= $amount;
									?>
									<td class="edd_commission_item"><?php echo esc_html( $item_name ); ?></td>
									<td class="edd_commission_amount"><?php echo edd_currency_filter( $amount ); ?></td>
									<td class="edd_commission_rate"><?php echo $rate . '%'; ?></td>
									<td class="edd_commission_date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $commission->post_date ) ); ?></td>
									<?php do_action( 'eddc_user_commissions_paid_row_end', $commission ); ?>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr class="edd_user_commission_row edd_row_empty">
								<td colspan="4"><?php _e('No paid commissions', 'eddc'); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>
					<div id="edd_user_commissions_paid_total"><?php _e('Total paid:', 'eddc');?>&nbsp;<?php echo edd_currency_filter( $total ); ?></div>
				</div><!--end #edd_user_commissions_unpaid-->

			</div><!--end #edd_user_commissions-->
		<?php
		$stats = apply_filters( 'edd_user_commissions_display', ob_get_clean() );
	endif;

	return $stats;
}
add_shortcode( 'edd_commissions', 'eddc_user_commissions' );