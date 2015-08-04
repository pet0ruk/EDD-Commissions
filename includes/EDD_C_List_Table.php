<?php

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class EDD_C_List_Table extends WP_List_Table {


	/**
	 * Number of results to show per page
	 *
	 * @since       1.7
	 * @var         int
	 */
	public $per_page = 10;

	/**
	 * Term counts
	 * @var null
	 */
	public $term_counts = null;


	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular'  => 'commission',     //singular name of the listed records
				'plural'    => 'commissions',    //plural name of the listed records
				'ajax'      => false             //does this table support ajax?
			) );

	}


	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'rate':
				$download = get_post_meta( $item['ID'], '_download_id', true );
				$type = eddc_get_commission_type( $download );
				if( 'percentage' == $type )
					return $item[ $column_name ] . '%';
				else
					return edd_currency_filter( edd_sanitize_amount( $item[ $column_name ] ) );
			case 'status':
				return $item[ $column_name ];
			case 'amount':
				return edd_currency_filter( edd_format_amount( $item[ $column_name ] ) );
			case 'date':
				return date_i18n( get_option( 'date_format' ), strtotime( get_post_field( 'post_date', $item['ID'] ) ) );
			case 'download':
				$download = ! empty( $item['download'] ) ? $item['download'] : false;
				return $download ? '<a href="' . esc_url( add_query_arg( 'download', $download ) ) . '" title="' . __( 'View all commissions for this item', 'eddc' ) . '">' . get_the_title( $download ) . '</a>' . (!empty($item['variation']) ? ' - ' . $item['variation'] : '') : '';
			case 'payment':
				$payment = get_post_meta( $item['ID'], '_edd_commission_payment_id', true );
				return $payment ? '<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment ) ) . '" title="' . __( 'View payment details', 'eddc' ) . '">#' . $payment . '</a> - ' . edd_get_payment_status( get_post( $payment ), true  ) : '';
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title( $item ) {

		//Build row actions
		$actions = array();
		$base = admin_url( 'edit.php?post_type=download&page=edd-commissions' );
		if ( $item['status'] == 'revoked' ) {
			$actions['mark_as_accepted'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Accept', 'eddc' ) . '</a>', $base, 'mark_as_accepted', $item['ID'] );
		} elseif ( $item['status'] == 'paid' ) {
			$actions['mark_as_unpaid'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Mark as Unpaid', 'eddc' ) . '</a>', $base, 'mark_as_unpaid', $item['ID'] );
		} else {
			$actions['mark_as_paid'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Mark as Paid', 'eddc' ) . '</a>', $base, 'mark_as_paid', $item['ID'] );
			$actions['mark_as_revoked'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Revoke', 'eddc' ) . '</a>', $base, 'mark_as_revoked', $item['ID'] );
		}
		$actions['edit'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Edit' ) . '</a>', $base, 'edit', $item['ID'] );
		$actions['delete'] = sprintf( '<a href="%s&action=%s&commission=%s">' . __( 'Delete' ) . '</a>', $base, 'delete', $item['ID'] );


		$user = get_userdata( $item['user'] );

		//Return the title contents
		return sprintf( '%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ '<a href="' . esc_url( add_query_arg( 'user', $user->ID ) ) . '" title="' . __( 'View all commissions for this user', 'eddc' ) . '"">' . $user->display_name . '</a>',
			/*$2%s*/ $item['ID'],
			/*$3%s*/ $this->row_actions( $actions )
		);
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item['ID']
		);
	}


	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'title'     => __( 'User', 'eddc' ),
			'download'  => edd_get_label_singular(),
			'payment'   => __( 'Payment', 'eddc' ),
			'rate'      => __( 'Rate', 'eddc' ),
			'amount'    => __( 'Amount', 'eddc' ),
			'status'    => __( 'Status', 'eddc' ),
			'date'      => __( 'Date', 'eddc' )
		);
		return $columns;
	}

	function get_views() {
		$base    = admin_url( 'edit.php?post_type=download&page=edd-commissions' );
		$user_id = $this->get_filtered_user();
		if ( ! empty( $user_id ) ) {
			$base = add_query_arg( array( 'user' => $user_id, $base ) );
		}

		$current       = isset( $_GET['view'] ) ? $_GET['view'] : '';
		$status_counts = $this->get_commission_status_counts();

		$views = array(
			'all'       => sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( 'view', $base ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'eddc' ), $status_counts['all'] ) . sprintf( _x( '(%d)', 'post count', 'eddc' ), $status_counts['all'] ),
			'unpaid'    => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'view', 'unpaid', $base ) ), $current === 'unpaid' ? ' class="current"' : '', __( 'Unpaid', 'eddc' ), $status_counts['unpaid'] ) . sprintf( _x( '(%d)', 'post count', 'eddc' ), $status_counts['unpaid'] ),
			'revoked'   => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'view', 'revoked', $base ) ), $current === 'revoked' ? ' class="current"' : '', __( 'Revoked', 'eddc' ), $status_counts['revoked'] ) . sprintf( _x( '(%d)', 'post count', 'eddc' ), $status_counts['revoked'] ),
			'paid'      => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'view', 'paid', $base ) ), $current === 'paid' ? ' class="current"' : '', __( 'Paid', 'eddc' ), $status_counts['paid'] ) . sprintf( _x( '(%d)', 'post count', 'eddc' ), $status_counts['paid'] ),
		);
		return $views;
	}


	function get_bulk_actions() {
		$actions = array(
			'mark_as_paid'      => __( 'Mark as Paid', 'eddc' ),
			'mark_as_unpaid'    => __( 'Mark as Unpaid', 'eddc' ),
			'mark_as_revoked'   => __( 'Mark as Revoked', 'eddc' ),
			'delete'            => __( 'Delete', 'eddc' )
		);
		return $actions;
	}


	/**
	 * Retrieve the current page number
	 *
	 * @access      private
	 * @since       1.7
	 * @return      int
	 */
	function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}


	/**
	 * Retrieves the user we are filtering commissions by, if any
	 *
	 * @access      private
	 * @since       1.7
	 * @return      mixed Int if user ID, string if email or login
	 */
	function get_filtered_user() {
		$user_id = ! empty( $_GET['user'] ) ? sanitize_text_field( $_GET['user'] ) : 0;
		if ( ! is_numeric( $user_id ) ) {
			$user    = get_user_by( 'login', $_GET['user'] );
			$user_id = $user->data->ID;
		}

		return ! empty( $user_id ) ? absint( $user_id ) : false;
	}


	/**
	 * Retrieves the ID of the download we're filtering commissions by
	 *
	 * @access      private
	 * @since       1.7
	 * @return      int
	 */
	function get_filtered_download() {
		return ! empty( $_GET['download'] ) ? absint( $_GET['download'] ) : false;
	}


	/**
	 * Retrieves the ID of the download we're filtering commissions by
	 *
	 * @access      private
	 * @since       2.0
	 * @return      int
	 */
	function get_filtered_payment() {
		return ! empty( $_GET['payment'] ) ? absint( $_GET['payment'] ) : false;
	}

	function get_filtered_view() {
		return ! empty( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'all';
	}


	/**
	 * Gets the meta query for the log query
	 *
	 * This is used to return log entries that match our search query, user query, or download query
	 *
	 * @access      private
	 * @since       1.7
	 * @return      array
	 */
	function get_meta_query() {

		$meta_query = array();

		$user     = $this->get_filtered_user();
		$download = $this->get_filtered_download();
		$payment  = $this->get_filtered_payment();

		if( $user ) {
			// Show only commissions from a specific user
			$meta_query[] = array(
				'key'   => '_user_id',
				'value' => $user
			);

		}

		if( $download ) {
			// Show only commissions from a specific download
			$meta_query[] = array(
				'key'   => '_download_id',
				'value' => $download
			);

		}

		if( $payment ) {
			// Show only commissions from a specific payment
			$meta_query[] = array(
				'key'   => '_edd_commission_payment_id',
				'value' => $payment
			);

		}

		return $meta_query;
	}

	/**
	 * Gets the tax query
	 *
	 * This is used to return commissions of a specific status
	 *
	 * @access      private
	 * @since       2.8
	 * @return      array
	 */
	function get_tax_query() {

		$tax_query  = array();
		$view = isset( $_GET['view'] ) ? $_GET['view'] : false;

		if ( $view ) {

			// Show only commissions of a specific status
			$tax_query[] = array(
				'taxonomy' => 'edd_commission_status',
				'terms'    => $view,
				'field'    => 'slug'
			);

		}

		return $tax_query;

	}

	function process_bulk_action() {

		$ids = isset( $_GET['commission'] ) ? $_GET['commission'] : false;

		if ( !is_array( $ids ) )
			$ids = array( $ids );

		foreach ( $ids as $id ) {
			// Detect when a bulk action is being triggered...
			if ( 'delete' === $this->current_action() ) {
				wp_delete_post( $id );
			}
			if ( 'mark_as_paid' === $this->current_action() ) {
				eddc_set_commission_status( $id, 'paid' );
			}
			if ( 'mark_as_unpaid' === $this->current_action() ) {
				eddc_set_commission_status( $id, 'unpaid' );
			}
			if ( 'mark_as_revoked' === $this->current_action() ) {
				eddc_set_commission_status( $id, 'revoked' );
			}
			if ( 'mark_as_accepted' === $this->current_action() ) {
				eddc_set_commission_status( $id, 'unpaid' );
			}
		}
	}


	function commissions_data() {

		$commissions_data = array();

		$paged    = $this->get_paged();
		$user     = $this->get_filtered_user();

		$commission_args = array(
			'post_type'      => 'edd_commission',
			'post_status'    => 'publish',
			'posts_per_page' => $this->per_page,
			'paged'          => $paged,
		);

		$meta_query = $this->get_meta_query();
		if ( ! empty( $meta_query ) ) {
			$commission_args['meta_query'] = $meta_query;
		}

		$tax_query = $this->get_tax_query();
		if ( ! empty( $tax_query ) ) {
			$commission_args['tax_query'] = $tax_query;
		}

		$commissions = new WP_Query( $commission_args );
		if ( $commissions->have_posts() ) :
			while ( $commissions->have_posts() ) : $commissions->the_post();
				$commission_id   = get_the_ID();
				$commission_info = get_post_meta( $commission_id, '_edd_commission_info', true );
				$download_id     = get_post_meta( $commission_id, '_download_id', true );
				$variation       = get_post_meta( $commission_id, '_edd_commission_download_variation', true );

				$commissions_data[] = array(
					'ID'        => $commission_id,
					'title'     => get_the_title( $commission_id ),
					'amount'    => $commission_info['amount'],
					'rate'      => $commission_info['rate'],
					'user'      => $commission_info['user_id'],
					'download'  => $download_id,
					'variation' => $variation,
					'status'    => eddc_get_commission_status( $commission_id ),
				);

			endwhile;
			wp_reset_postdata();
		endif;
		return $commissions_data;
	}

	public function get_commission_status_counts() {

		if ( ! is_null( $this->term_counts ) ) {
			return $this->term_counts;
		}

		$term_counts = array(
			'paid'    => 0,
			'unpaid'  => 0,
			'revoked' => 0,
			'all'     => 0,
		);

		$user = $this->get_filtered_user();
		if ( ! empty( $user ) ) {
			$args = array(
				'number'  => -1,
				'user_id' => $this->get_filtered_user(),
			);

			$paid    = eddc_get_paid_commissions( $args );
			$unpaid  = eddc_get_unpaid_commissions( $args );
			$revoked = eddc_get_revoked_commissions( $args );

			$term_counts = array(
				'paid'    => ! empty( $paid )    ? count( $paid )    : 0,
				'unpaid'  => ! empty( $unpaid )  ? count( $unpaid )  : 0,
				'revoked' => ! empty( $revoked ) ? count( $revoked ) : 0,
			);

			$term_counts['all'] = $term_counts['paid'] + $term_counts['unpaid'] + $term_counts['revoked'];

		} else {
			$found_terms = get_terms( 'edd_commission_status' );

			$total_term_count = 0;
			foreach ( $found_terms as $term ) {
				if ( array_key_exists( $term->slug, $term_counts ) ) {
					$term_counts[ $term->slug ] = $term->count;
					$total_term_count += $term->count;
				}
			}
			$term_counts['all'] = $total_term_count;
		}

		$this->term_counts = $term_counts;

		return $this->term_counts;
	}



	/** ************************************************************************
	 *
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 * *************************************************************************/
	function prepare_items() {

		$columns = $this->get_columns();
		$hidden = array(); // no hidden columns

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$view          = $this->get_filtered_view();
		$status_counts = $this->get_commission_status_counts();

		$total_items = array_key_exists( $view, $status_counts ) ? $status_counts[ $view ] : $status_counts['all'];
		$this->items = $this->commissions_data();

		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $this->per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items/$this->per_page )   //WE have to calculate the total number of pages
		) );
	}

}
