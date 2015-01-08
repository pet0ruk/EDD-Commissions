<?php


/**
 * Adds "Commissions" to the report views
 *
 * @access      public
 * @since       1.4
 * @return      void
*/

function eddc_add_commissions_view( $views ) {
	$views['commissions'] = __( 'Commissions', 'edd' );
	return $views;
}
add_filter( 'edd_report_views', 'eddc_add_commissions_view' );


/**
 * Show Commissions Graph
 *
 * @access      public
 * @since       1.0
 * @return      void
*/

function edd_show_commissions_graph() {

	// retrieve the queried dates
	$dates      = edd_get_report_dates();
	$day_by_day = true;

	// Determine graph options
	switch( $dates['range'] ) :
		case 'last_year' :
		case 'this_year' :
		case 'last_quarter' :
		case 'this_quarter' :
			$day_by_day		= false;
			break;
		case 'other' :
			if( ( $dates['m_end'] - $dates['m_start'] ) >= 2 ) {
				$day_by_day 	= false;
			}
			break;
	endswitch;

	$user  = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
	$total = (float) 0.00; // Total commissions for time period shown

	ob_start(); ?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php edd_report_views(); ?></div>
	</div>
	<?php
	$data = array();

	if( $dates['range'] == 'today' ) {
		// Hour by hour
	   	$hour  = 1;
	   	$month = date( 'n' );

		while ( $hour <= 23 ) :

			$commissions = edd_get_commissions_by_date( $dates['day'], $month, $dates['year'], $hour, $user );
			$total      += $commissions;
			$date        = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] );
			$data[]      = array( $date * 1000, (int) $commissions );
			$hour++;

		endwhile;

	} elseif( $dates['range'] == 'this_week' || $dates['range'] == 'last_week' ) {

		//Day by day
		$day     = $dates['day'];
		$day_end = $dates['day_end'];
		$month   = $dates['m_start'];

		while ( $day <= $day_end ) :

			$commissions = edd_get_commissions_by_date( $day, $month, $dates['year'], null, $user );
			$total      += $commissions;
			$date        = mktime( 0, 0, 0, $month, $day, $dates['year'] );
			$data[]      = array( $date * 1000, (int) $commissions );
			$day++;

		endwhile;

	} else {

		$i = $dates['m_start'];

		while ( $i <= $dates['m_end'] ) :

			if ( $day_by_day ) :

				$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $dates['year'] );
				$d           = 1;

				while ( $d <= $num_of_days ) :

					$date        = mktime( 0, 0, 0, $i, $d, $dates['year'] );
					$commissions = edd_get_commissions_by_date( $d, $i, $dates['year'], null, $user );
					$total      += $commissions;
					$data[]      = array( $date * 1000, (int) $commissions );
					$d++;

				endwhile;

			else :

				$date        = mktime( 0, 0, 0, $i, 1, $dates['year'] );
				$commissions = edd_get_commissions_by_date( null, $i, $dates['year'], null, $user );
				$total      += $commissions;
				$data[]      = array( $date * 1000, (int) $commissions );

			endif;

			$i++;

		endwhile;
	}

	$data = array(
		__( 'Commissions', 'eddc' ) => $data
	);
	?>

	<div class="metabox-holder" style="padding-top: 0;">
		<div class="postbox">
			<h3><span><?php _e('Commissions Paid Over Time', 'edd'); ?></span></h3>

			<div class="inside">
				<?php if( ! empty( $user ) ) : $user_data = get_userdata( $user ); ?>
				<p>
					<?php printf( __( 'Showing commissions paid to %s', 'eddc' ), $user_data->display_name ); ?>
					&nbsp;&ndash;&nbsp;<a href="<?php echo esc_url( remove_query_arg( 'user' ) ); ?>"><?php _e( 'clear', 'eddc' ); ?></a>
				</p>
				<?php endif; ?>
				<?php
					edd_reports_graph_controls();
					$graph = new EDD_Graph( $data );
					$graph->set( 'x_mode', 'time' );
					$graph->display();
				?>
				<p id="edd_graph_totals"><strong><?php _e( 'Total commissions for period shown: ', 'edd' ); echo edd_currency_filter( edd_format_amount( $total ) ); ?></strong></p>
   			</div>
   		</div>
   	</div>
	<?php
	echo ob_get_clean();
}
add_action('edd_reports_view_commissions', 'edd_show_commissions_graph');
