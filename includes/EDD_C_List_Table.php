<?php

if( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EDD_C_List_Table extends WP_List_Table {


    function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'commission',     //singular name of the listed records
            'plural'    => 'commissions',    //plural name of the listed records
            'ajax'      => false             //does this table support ajax?
        ) );

    }


    function column_default($item, $column_name){
        switch($column_name){
            case 'rate':
            	return $item[$column_name] . '%';
            case 'status':
                $status = get_post_meta( $item['ID'], '_commission_status', true );
                return $status ? $status : __('unpaid', 'eddc');
            case 'amount':
                return edd_currency_filter( edd_format_amount( $item[$column_name] ) );
            case 'date':
                return date_i18n( get_option( 'date_format' ), strtotime( get_post_field( 'post_date', $item['ID'] ) ) );
            case 'download':
                $download = get_post_meta( $item['ID'], '_download_id', true );
                return $download ? get_the_title( $download ) : '';
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item) {

        //Build row actions
        $actions = array();
        $base = admin_url('edit.php?post_type=download&page=edd-commissions');
        if( get_post_meta( $item['ID'], '_commission_status', true ) == 'paid' ) {
            $actions['mark_as_unpaid'] = sprintf('<a href="%s&action=%s&commission=%s">' . __('Mark as Unpaid', 'eddc') . '</a>', $base, 'mark_as_unpaid',$item['ID']);
        } else {
            $actions['mark_as_paid'] = sprintf('<a href="%s&action=%s&commission=%s">' . __('Mark as Paid', 'eddc') . '</a>', $base, 'mark_as_paid',$item['ID']);
        }
        $actions['edit'] = sprintf('<a href="%s&action=%s&commission=%s">' . __('Edit') . '</a>', $base, 'edit',$item['ID']);
        $actions['delete'] = sprintf('<a href="%s&action=%s&commission=%s">' . __('Delete') . '</a>', $base, 'delete',$item['ID']);


        $user = get_userdata( $item['user'] );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $user->display_name,
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],
            /*$2%s*/ $item['ID']
        );
    }


    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'     => __('User', 'eddc'),
            'download'  => edd_get_label_singular(),
            'rate'    	=> __('Rate', 'eddc'),
            'amount'    => __('Amount', 'eddc'),
            'status'    => __('Status', 'eddc'),
            'date'      => __('Date', 'eddc')
        );
        return $columns;
    }

    function get_views() {
        $base = admin_url('edit.php?post_type=download&page=edd-commissions');
        $current = isset( $_GET['view'] ) ? $_GET['view'] : '';
        $views = array(
            'all'       => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( 'view', $base ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All') ),
            'unpaid'    => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'view', 'unpaid', $base ), $current === 'unpaid' ? ' class="current"' : '', __('Unpaid') ),
            'paid'      => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'view', 'paid', $base ), $current === 'paid' ? ' class="current"' : '', __('Paid') )
        );
        return $views;
    }


    function get_bulk_actions() {
        $actions = array(
            'mark_as_paid'      => __('Mark as Paid'),
            'mark_as_unpaid'    => __('Mark as Unpaid'),
            'delete'            => __('Delete'),
        );
        return $actions;
    }


    function process_bulk_action() {

        $ids = isset( $_GET['commission'] ) ? $_GET['commission'] : false;

        if( !is_array( $ids ) )
            $ids = array( $ids );

        foreach( $ids as $id ) {
            // Detect when a bulk action is being triggered...
            if( 'delete' === $this->current_action() ) {
                wp_delete_post( $id );
            }
            if( 'mark_as_paid' === $this->current_action() ) {
                update_post_meta( $id, '_commission_status', 'paid' );
            }
            if( 'mark_as_unpaid' === $this->current_action() ) {
                update_post_meta( $id, '_commission_status', 'unpaid' );
            }
        }
    }


    function commissions_data() {

	    $commissions_data = array();

		$commission_args = array(
			'post_type' => 'edd_commission',
			'post_status' => 'publish',
			'posts_per_page' => -1
		);

        $view = isset( $_GET['view'] ) ? $_GET['view'] : false;
        if( $view ) {
            $commission_args['meta_key'] = '_commission_status';
            $commission_args['meta_value'] = $view;
        }

		$commissions = get_posts( $commission_args );
		if( $commissions ) {
			foreach( $commissions as $commission ) {
                $commission_info = get_post_meta( $commission->ID, '_edd_commission_info', true );
				$download_id = get_post_meta( $commission->ID, '_download_id', true );
				$commissions_data[] = array(
					'ID' 		=> $commission->ID,
					'title' 	=> get_the_title( $commission->ID ),
					'amount' 	=> $commission_info['amount'],
					'rate'		=> $commission_info['rate'],
                    'user'      => $commission_info['user_id'],
					'download'  => $download_id
				);
			}
		}
		return $commissions_data;
    }

    /** ************************************************************************
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;


        $columns = $this->get_columns();
        $hidden = array(); // no hidden columns


        $this->_column_headers = array($columns, $hidden);


        $this->process_bulk_action();


        $data = $this->commissions_data();


        $current_page = $this->get_pagenum();


        $total_items = count($data);


        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);


        $this->items = $data;


        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }

}