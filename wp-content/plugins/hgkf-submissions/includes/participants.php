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

    function prepare_participants()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = self::get_participants();

        // check if a search was performed.
        $user_search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

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
        'parking_ticket'=>__('Parkeerticket')
        );
    }

    /**
     * Decide which columns to activate the sorting functionality on
     * @return array $sortable, the array of columns that can be sorted by the user
     */
    public function get_sortable_columns() {
        return $sortable = array(
        'submission_id'=>'submission_id',
        'name'=>'name',
        'email'=>'email'
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
            case 'parking_ticket':
                return $item[ $column_name ];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
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