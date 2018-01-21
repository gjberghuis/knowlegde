<?php

class participants_list extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => __('participant', 'participants'),
            'plural' => __('participants', 'participants'),
            'ajax' => false
        ));
    }

    function usort_reorder($a, $b)
    {
// If no sort, default to title
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'invoice_number';
// If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
// Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);
// Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    function prepare_participants()
    {
         // check if a search was performed.
         $user_search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';


        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = $this->get_items_per_page('submissions_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page                     //WE have to determine how many items to show on a page
        ));

        $this->items = self::get_participants($per_page, $current_page);

        // check if a search was performed.
        if( $user_search_key ) {
            $this->items = $this->filter_table_data($this->items, $user_search_key );
        }
    }

    // filter the table data based on the search key
    public function filter_table_data( $table_data, $search_key ) {
        $filtered_table_data = array_values( array_filter( $table_data, function( $row ) use( $search_key ) {
            foreach( $row as $row_val ) {
                if( stripos( $row_val, $search_key ) !== false ) {
                    return true;
                }
            }
        } ) );
        return $filtered_table_data;
    }

    /**
     * Define the columns that are going to be used in the table
     * @return array $columns, the array of columns to use with the table
     */
    function get_columns() {
        return $columns= array(
        'id'=>__('Id'),
        'submission_id'=>__('Submission id'),
        'name'=>__('Naam'),
        'email'=>__('Email'), 
        'phone'=>__('Telefoon'),    
        'parkingticket'=>__('Parkeerticket')
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
        return array(
            'submission_id' => array('submission_id', false),
            'name' => array('name', false),
            'email' => array('email', false)
        );
    }

    function column_id($item) {
        $path = 'admin.php?page=edit_participant';
        $editUrl = admin_url($path);

        $actions = array(
            'edit'      => sprintf('<a href="%s&action=%s&id=%s">Edit</a>',$editUrl,'edit_participant',$item['id'])
        );

        return sprintf('%1$s %2$s', $item['id'], $this->row_actions($actions) );
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'submission_id':
            case 'name':
            case 'email':
            case 'phone':
            case 'parkingticket':
                return $item[ $column_name ];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

     /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}submission_participants";

        return $wpdb->get_var($sql);
    }

      /**
     * Retrieve submission data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_participants($per_page = 100, $page_number = 1)
    {
        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}submission_participants";
        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        return $result;
    }
}