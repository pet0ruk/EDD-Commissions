<?php

/**
 * Register Dashboard Widgets
 *
 * Registers the dashboard widgets.
 *
 * @access      private
 * @since       1.6
*/

function eddc_register_dashboard_commission_widgets() {
	if( eddc_user_has_commissions() ) {
		wp_add_dashboard_widget( 'edd_dashboard_user_commissions', __('Commissions Summary', 'edd'), 'eddc_dashboard_commissions_widget' );
	}
}
add_action('wp_dashboard_setup', 'eddc_register_dashboard_commission_widgets', 100 );


/**
 * Commissions Summary Dashboard Widget
 *
 * @access      private
 * @since       1.6
*/

function eddc_dashboard_commissions_widget() {
	global $user_ID;

	$unpaid_paged = isset( $_GET['eddcup'] ) ? absint( $_GET['eddcup'] ) : 1;
	$paid_paged   = isset( $_GET['eddcp'] ) ? absint( $_GET['eddcp'] ) : 1;

	$unpaid_commissions = eddc_get_unpaid_commissions( array( 'user_id' => $user_ID, 'number' => 20, 'paged' => $unpaid_paged ) );
	$paid_commissions 	= eddc_get_paid_commissions( array( 'user_id' => $user_ID, 'number' => 20, 'paged' => $paid_paged ) );

	$total_unpaid       = eddc_count_user_commissions( $user_ID, 'unpaid' );
	$total_paid         = eddc_count_user_commissions( $user_ID, 'paid' );

	$unpaid_offset      = 20 * ( $unpaid_paged - 1 );
	$unpaid_total_pages = ceil( $total_unpaid / 20 );

	$paid_offset        = 20 * ( $paid_paged - 1 );
	$paid_total_pages   = ceil( $total_paid / 20 );

	$stats 				= '';

	if( ! empty( $unpaid_commissions ) || ! empty( $paid_commissions ) ) : // only show tables if user has commission data
		ob_start(); ?>
			<div id="edd_user_commissions" class="edd_dashboard_widget">
				<style>#edd_user_commissions_unpaid { margin-top: 30px; }#edd_user_commissions_unpaid_total,#edd_user_commissions_paid_total { padding-bottom: 20px; } .edd_user_commissions { width: 100%; margin: 0 0 20px; }.edd_user_commissions th, .edd_user_commissions td { text-align:left; padding: 4px 4px 4px 0; }</style>
				<!-- unpaid -->
				<div id="edd_user_commissions_unpaid" class="table">
					<p class="edd_user_commissions_header sub"><?php _e('Unpaid Commissions', 'eddc'); ?></p>
					<table id="edd_user_unpaid_commissions_table" class="edd_user_commissions">
						<thead>
							<tr class="edd_user_commission_row">
								<th class="edd_commission_item"><?php _e('Item', 'eddc'); ?></th>
								<th class="edd_commission_amount"><?php _e('Amount', 'eddc'); ?></th>
								<th class="edd_commission_rate"><?php _e('Rate', 'eddc'); ?></th>
								<th class="edd_commission_date"><?php _e('Date', 'eddc'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php $total = (float) 0; ?>
						<?php if( ! empty( $unpaid_commissions ) ) : ?>
							<?php foreach( $unpaid_commissions as $commission ) : ?>
								<tr class="edd_user_commission_row">
									<?php
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
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr class="edd_user_commission_row edd_row_empty">
								<td colspan="4"><?php _e('No unpaid commissions', 'eddc'); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>

					<div id="edd_user_commissions_unpaid_total"><?php _e('Total unpaid:', 'eddc');?>&nbsp;<?php echo edd_currency_filter( eddc_get_unpaid_totals( $user_ID ) ); ?></div>

					<div id="edd_commissions_unpaid_pagination" class="navigation" style="padding: 0 0 15px;">
					<?php
						$big = 999999;
						echo paginate_links( array(
							'base'    => admin_url() . '%_%#edd_user_commissions_unpaid',
							'format'  => '?eddcup=%#%',
							'current' => max( 1, $unpaid_paged ),
							'total'   => $unpaid_total_pages
						) );
					?>
					</div>

				</div><!--end #edd_user_commissions_unpaid-->

				<!-- paid -->
				<div id="edd_user_commissions_paid" class="table">
					<p class="edd_user_commissions_header sub"><?php _e('Paid Commissions', 'eddc'); ?></p>
					<table id="edd_user_paid_commissions_table" class="edd_user_commissions">
						<thead>
							<tr class="edd_user_commission_row">
								<th class="edd_commission_item"><?php _e('Item', 'eddc'); ?></th>
								<th class="edd_commission_amount"><?php _e('Amount', 'eddc'); ?></th>
								<th class="edd_commission_rate"><?php _e('Rate', 'eddc'); ?></th>
								<th class="edd_commission_date"><?php _e('Date', 'eddc'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php $total = (float) 0; ?>
						<?php if( ! empty( $paid_commissions ) ) : ?>
							<?php foreach( $paid_commissions as $commission ) : ?>
								<tr class="edd_user_commission_row">
									<?php
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
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr class="edd_user_commission_row edd_row_empty">
								<td colspan="4"><?php _e('No paid commissions', 'eddc'); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>

					<div id="edd_user_commissions_paid_total"><?php _e('Total paid:', 'eddc');?>&nbsp;<?php echo edd_currency_filter( eddc_get_paid_totals( $user_ID ) ); ?></div>

					<div id="edd_commissions_paid_pagination" class="navigation" style="padding: 0 0 15px;">
					<?php
						$big = 999999;
						echo paginate_links( array(
							'base'    => admin_url() . '%_%#edd_user_commissions_paid',
							'format'  => '?eddcp=%#%',
							'current' => max( 1, $paid_paged ),
							'total'   => $paid_total_pages
						) );
					?>
					</div>
				</div><!--end #edd_user_commissions_paid-->

				<div id"edd_commissions_export">
					<p><strong><?php _e( 'Export Paid Commissions', 'eddc' ); ?></strong></p>
					<form method="post" action="<?php echo admin_url( 'index.php' ); ?>">
						<?php echo EDD()->html->month_dropdown(); ?>
						<?php echo EDD()->html->year_dropdown(); ?>
						<input type="hidden" name="edd_action" value="generate_commission_export"/>
						<input type="submit" class="button-secondary" value="<?php _e( 'Download CSV', 'eddc' ); ?>"/>
					</form>
				</div>
			</div><!--end #edd_user_commissions-->
		<?php
		$stats = ob_get_clean();
	endif;

	echo $stats;
}