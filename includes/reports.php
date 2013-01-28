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
	$dates = edd_get_report_dates();

	// determine graph options
	switch( $dates['range'] ) :

		case 'last_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'this_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'last_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'this_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		default:
			$time_format 	= '%d/%b'; 	// show days by default
			$tick_size		= 'day'; 	// default graph interval
			$day_by_day 	= true;
			break;

	endswitch;

	$time_format = apply_filters( 'edd_graph_timeformat', $time_format );
	$tick_size = apply_filters( 'edd_graph_ticksize', $tick_size );

	echo '<h3>' . __( 'Commissions Paid Over Time', 'eddc' ) . '</h3>';

	// show the date controls
	edd_reports_graph_controls();

	ob_start(); ?>
	<script type="text/javascript">
	   jQuery( document ).ready( function($) {
	   		$.plot(
	   			$("#commissions_chart_div"),
	   			[{
   					data: [
	   					<?php
	   					$i = $dates['m_start'];
						while( $i <= $dates['m_end'] ) :
							if( $day_by_day ) :
								$num_of_days 	= cal_days_in_month( CAL_GREGORIAN, $i, $dates['year'] );
								$d 				= 1;
								while( $d <= $num_of_days ) : $date = mktime( 0, 0, 0, $i, $d, $dates['year'] ); ?>
									[<?php echo $date * 1000; ?>, <?php echo edd_get_commissions_by_date( $d, $i, $dates['year'] ); ?>],
								<?php $d++; endwhile;
							else :
								$date = mktime( 0, 0, 0, $i, 1, $dates['year'] );
								?>
								[<?php echo $date * 1000; ?>, <?php echo edd_get_commissions_by_date( null, $i, $dates['year'] ); ?>],
							<?php
							endif;
							$i++;
						endwhile;
	   					?>,
	   				],
   					label: "<?php _e( 'Commissions', 'eddc' ); ?>",
   					id: 'commissions'
   				}],
	   		{
               	series: {
                   lines: { show: true },
                   points: { show: true }
            	},
            	grid: {
           			show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true
           		},
            	xaxis: {
	   				mode: "time",
	   				timeFormat: "<?php echo $time_format; ?>",
	   				minTickSize: [1, "<?php echo $tick_size; ?>"]
   				}
            });

			function edd_flot_tooltip(x, y, contents) {
		        $('<div id="edd-flot-tooltip">' + contents + '</div>').css( {
		            position: 'absolute',
		            display: 'none',
		            top: y + 5,
		            left: x + 5,
		            border: '1px solid #fdd',
		            padding: '2px',
		            'background-color': '#fee',
		            opacity: 0.80
		        }).appendTo("body").fadeIn(200);
		    }

		    var previousPoint = null;
		    $("#commissions_chart_div").bind("plothover", function (event, pos, item) {
		        $("#x").text(pos.x.toFixed(2));
		        $("#y").text(pos.y.toFixed(2));
	            if (item) {
	                if (previousPoint != item.dataIndex) {
	                    previousPoint = item.dataIndex;
	                    $("#edd-flot-tooltip").remove();
	                    var x = item.datapoint[0].toFixed(2),
	                        y = item.datapoint[1].toFixed(2);
	                    if( item.series.id == 'commissions' ) {
	                    	if( edd_vars.currency_pos == 'before' ) {
								edd_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + edd_vars.currency_sign + y );
	                    	} else {
								edd_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + y + edd_vars.currency_sign );
	                    	}
	                    } else {
		                    edd_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + y.replace( '.00', '' ) );
	                    }
	                }
	            } else {
	                $("#edd-flot-tooltip").remove();
	                previousPoint = null;
	            }
		    });
	   });
    </script>
    <div id="commissions_chart_div" style="height: 300px;"></div>
	<?php
	echo ob_get_clean();
}
add_action('edd_reports_view_commissions', 'edd_show_commissions_graph');