<?php

class settings extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => __('instellingen', 'settings'),
            'plural' => __('instellingen', 'settings'),
            'ajax' => false
        ));

        add_action('admin_head', array($this, 'admin_header'));
    }

    function prepare_special_events_settings()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = self::get_special_events_settings();
    }

    /**
     * Retrieve submission data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_special_events_settings($per_page = 100, $page_number = 1)
    {
        global $wpdb;


        $sql = "SELECT * FROM {$wpdb->prefix}special_events_settings";
        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
      
        return $result;
    }

    function column_event_id($item) {
        $path = 'admin.php?page=edit_special_events_settings';
        $editUrl = admin_url($path);

        $actions = array(
            'edit'      => sprintf('<a href="%s&action=%s&id=%s">Edit</a>',$editUrl,'edit_special_events_settings',$item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
        );

        return sprintf('%1$s %2$s', $item['edit_special_events_settings'], $this->row_actions($actions) );
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'event_name':
            case 'event_id':
            case 'ticket_price_single':
                return $item[ $column_name ];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }


    function get_columns()
    {
        $columns = array(
            'event_name' => __('Event naam', 'settings'),
            'event_id' => __('Event id', 'settings'),
            'ticket_price_single' => __('Ticket prijs', 'settings')
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'event_name' => array('event_name', false)
        );
        return $sortable_columns;
    }
}

?>