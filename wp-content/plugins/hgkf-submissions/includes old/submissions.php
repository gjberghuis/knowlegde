<?php

class My_submission_list extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => __('aanmelding', 'mylisttable'),
            'plural' => __('aanmeldingen', 'mylisttable'),
            'ajax' => false
        ));
        add_action('admin_head', array($this, 'admin_header'));
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'submission_id':
            case 'number':
            case 'active':
            case 'submission_type':
            case 'submission_date':
            case 'organization':
            case 'firstname':
            case 'lastname':
            case 'adress':
            case 'zipcode':
            case 'city':
            case 'extra_information':
            case 'email':
            case 'price':
            case 'price_tax':
            case 'parking_tickets':
            case 'reduction_code':
            case 'notes':
            case 'numberOfParticipants':
                return $item[$column_name];
                
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function no_items()
    {
        _e('Geen aanmeldingen gevonden.');
    }

    function column_submission_id($item)
    {
        $path = 'admin.php?page=edit_submission';
        $editUrl = admin_url($path);

        $actions = array(
            'bewerken' => sprintf('<a href="%s&action=%s&id=%s">Bewerken</a>', $editUrl, 'edit_submission', $item['id'])
        );

        return sprintf('%1$s %2$s', $item['submission_id'], $this->row_actions($actions));
    }

    function column_numberOfParticipants($item) 
    {
        $path = 'admin.php?page=participants';
        $editUrl = admin_url($path);

        $actions = array(
            'bewerken' => sprintf('<a href="%s&action=%s&submission_id=%s">Bewerken</a>', $editUrl, 'participants', $item['submission_id'])
        );

        return sprintf('%1$s %2$s', $item['numberOfParticipants'], $this->row_actions($actions));
    }

    function column_active($item)
    {
        $actions = array(
            'active' => sprintf('<a href="?page=%s&action=%s&submission=%s">Verander status</a>', $_REQUEST['page'], 'active', $item['id'])
        );

        return sprintf('%1$s %2$s', $item['active'], $this->row_actions($actions));
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="submission[]" value="%s" />', $item['id']
        );
    }

    function admin_header()
    {
        $page = (isset($_GET['page'])) ? esc_attr($_GET['page']) : false;

        if ('my_submissions_overview' != $page)
            return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-submission_id { width:80px !important; }';
        echo '.wp-list-table .column-active { width:80px !important; }';
        echo '.wp-list-table .column-notes { width:200px !important; }';
        echo '</style>';
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-active' => 'Exportstatus veranderen'
        ];

        return $actions;
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

    function prepare_items()
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
        $this->items = self::get_submissions($per_page, $current_page);

        if( $user_search_key ) {
            $this->items = $this->filter_table_data($this->items, $user_search_key );
        }
        /** Process bulk action */
        $this->process_bulk_action();
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

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'submission_id' => __('Nummer', 'mylisttable'),
            'number' => __('Factuur nummer', 'mylisttable'),
            'active' => __('Negeren in export', 'mylisttable'),
            'submission_type' => __('Type aanmelding', 'mylisttable'),
            'numberOfParticipants' => __('Aantal deelnemers', 'mylisttable'),
            'submission_date' => __('Inzend datum', 'mylisttable'),
            'organization' => __('Organisatie', 'mylisttable'),
            'firstname' => __('Voornaam', 'mylisttable'),
            'lastname' => __('Achternaam', 'mylisttable'),
            'adress' => __('Adres', 'mylisttable'),
            'zipcode' => __('Postcode', 'mylisttable'),
            'city' => __('Plaats', 'mylisttable'),
            'email' => __('Email', 'mylisttable'),
            'extra_information' => __('Extra informatie', 'mylisttable'),
            'price' => __('Prijs excl. Btw', 'mylisttable'),
            'price_tax' => __('Prijs incl. Btw', 'mylisttable'),
            'parking_tickets' => __('Parkeertickets', 'mylisttable'),
            'reduction_code' => __('Kortingscode', 'mylisttable'),
            'notes' => __('Opmerkingen', 'mylisttable')
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'submission_id' => array('submission_id', false),
            'number' => array('number', false),
            'submission_type' => array('submission_type', false),
            'submission_date' => array('submission_date', false)
        );
        return $sortable_columns;
    }

    public function process_bulk_action()
    {
        if ('active' === $this->current_action()) {
// In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            self::change_active_submission(absint($_GET['submission']));

            wp_redirect(esc_url(add_query_arg()));
            exit;
        }

        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-active')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-active')
        ) {
            $delete_ids = esc_sql($_POST['submission']);
            foreach ($delete_ids as $id) {
                self::change_active_submission($id);

            }

            wp_redirect(esc_url(add_query_arg()));
            exit;
        }
    }

    /**
     * Change the active status a submission in the export for exact
     *
     * @param int $id submission id
     */
    public static function change_active_submission($id)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT active from {$wpdb->prefix}submissions WHERE id = " . $id);

        $newStatus = 1;
        if ($results[0]->active == 1) {
            $newStatus = 0;
        }
        $wpdb->query("UPDATE {$wpdb->prefix}submissions SET active=" . $newStatus . " WHERE id = " . $id);
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}submissions";

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
    public static function get_submissions($per_page = 5, $page_number = 1)
    {
        global $wpdb;
        $sql = "SELECT submission.id, invoice.submission_id, invoice.number, submission.active, submission.submission_type, submission.submission_date, 
submission.organization, invoice.firstname, invoice.lastname, submission.price, submission.price_tax, submission.parking_tickets, submission.reduction_code, 
submission.notes, invoice.adress, invoice.zipcode, invoice.city, invoice.email, invoice.extra_information, 
(SELECT COUNT(*) FROM {$wpdb->prefix}submission_participants p WHERE p.submission_id = invoice.submission_id) as numberOfParticipants
FROM {$wpdb->prefix}submissions AS submission 
INNER JOIN {$wpdb->prefix}submission_invoices AS invoice ON invoice.submission_id = submission.submission_id";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;


        $result = $wpdb->get_results($sql, 'ARRAY_A');
        $submissionCollection = $result;
        return $result;
    }

} //class
