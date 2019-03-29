<?php


function render_special_events_overview_page()
{
    wp_enqueue_script('jquery-ui-datepicker');
    wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    wp_enqueue_style('jquery-ui');

    global $mySpecialEventsTable;
    echo '</pre><div class="wrap"><h2>Aanmeldingen special events overzicht</h2>';

    echo '<form id="date" name="date" action="" method="post">';
        echo '<input type="hidden" name="page" value="<?php echo $_REQUEST[\'page\'] ?>" />';
        echo '<table>';
            echo '<tr>';
            echo '<td>';
            echo '<label for="from_date" style="margin-right: 20px;">Ticket prijs</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="date" name="from_date" />';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo '<label for="to_date" style="margin-right: 20px;">Code</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="date" name="to_date" />';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td style="padding:20px;">';
            echo '<input type="submit" value="Download deelnemers in csv" name="download_special_events_participants" />';
            echo '</td>';
            echo '<td style="padding:20px;">';
            echo '<input type="submit" value="Download facturen in csv" name="download_special_events_invoices_new" />';
            echo '</td>';
            echo '</tr>';
        echo '</table>';
    echo '</form>';
    ?>

    <?php

    echo '<form method="post" id="Submission" name="submissions">';
    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '">';
        $mySpecialEventsTable->prepare_items_special_events();
        $mySpecialEventsTable->search_box('zoeken', 'search');
        $mySpecialEventsTable->views();
        $mySpecialEventsTable->display();

    echo '</form>';
    echo '</div>';
}

class My_special_events_list extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct(array(
            'singular' => __('special event', 'myspecialeventstable'),
            'plural' => __('special events', 'myspecialeventstable'),
            'ajax' => false
        ));
        add_action('admin_head', array($this, 'admin_header'));
    }
    
	protected function get_views() { 
         $views = array();
         $current = ( !empty($_REQUEST['event_name']) ? $_REQUEST['event_name'] : 'Alle');
   
         global $wpdb;
         $eventNames = $wpdb->get_results("SELECT DISTINCT event_name from {$wpdb->prefix}special_events");

         //All link
         $class = ($current == 'all' ? ' class="current"' :'');
         $all_url = remove_query_arg('event_name');
         $views['all'] = "<a href='{$all_url }' {$class} >Alle</a>";

         foreach ($eventNames as $eventName) {
            $label = $eventName->event_name;
              
            $foo_url = add_query_arg('event_name', $label);
            $class = ($current == $label ? ' class="current"' :'');
            $views[$label] = "<a href='{$foo_url}' {$class} >" . $label . "</a>"; 
         }
   
         return $views;
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
            case 'kennisclub':
            case 'numberOfParticipants':
            case 'event_name':
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
        $path = 'admin.php?page=edit_special_event_submission';
        $editUrl = admin_url($path);

        $actions = array(
            'bewerken' => sprintf('<a href="%s&action=%s&id=%s">Bewerken</a>', $editUrl, 'edit_special_event_submission', $item['id'])
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

        if ('my_special_events_overview' != $page)
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

    function prepare_items_special_events()
    {
        // check if a search was performed.
        $user_search_key = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = $this->get_items_per_page('se-submissions_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page                     //WE have to determine how many items to show on a page
        ));
        $this->items = self::get_submissions_special_events($per_page, $current_page);

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
            'submission_id' => __('Nummer', 'myspecialeventstable'),
            'number' => __('Factuur nummer', 'myspecialeventstable'),
            'active' => __('Negeren in export', 'myspecialeventstable'),
            'event_name' => __('Evenement', 'myspecialeventstable'),
            'submission_date' => __('Inzend datum', 'myspecialeventstable'),
            'organization' => __('Organisatie', 'myspecialeventstable'),
            'lastname' => __('Achternaam', 'myspecialeventstable'),
            'city' => __('Plaats', 'myspecialeventstable'),
            'extra_information' => __('Extra informatie', 'myspecialeventstable'),
            'price' => __('Prijs excl. Btw', 'myspecialeventstable'),
            'notes' => __('Opmerkingen', 'myspecialeventstable')
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'submission_id' => array('submission_id', false),
            'number' => array('number', false),
            'event_name' => array('event_name', false),
            'submission_date' => array('submission_date', false)
        );
        return $sortable_columns;
    }

    public function process_bulk_action()
    {
        if ('active' === $this->current_action()) {
// In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            self::change_active_submission_special_events(absint($_GET['submission']));

            wp_redirect(esc_url(add_query_arg()));
            exit;
        }

        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-active')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-active')
        ) {
            $delete_ids = esc_sql($_POST['submission']);
            foreach ($delete_ids as $id) {
                self::change_active_submission_special_events($id);

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
    public static function change_active_submission_special_events($id)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT active from {$wpdb->prefix}special_events WHERE id = " . $id);

        $newStatus = 1;
        if ($results[0]->active == 1) {
            $newStatus = 0;
        }
        $wpdb->query("UPDATE {$wpdb->prefix}special_events SET active=" . $newStatus . " WHERE id = " . $id);
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}special_events";

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
    public static function get_submissions_special_events($per_page = 5, $page_number = 1)
    {
        global $wpdb;

        //Retrieve $customvar for use in query to get items.
        $event_name = ( isset($_REQUEST['event_name']) ? $_REQUEST['event_name'] : '');
        if($event_name != '') {
            $search_custom_vars= "AND submission.event_name LIKE '" . esc_sql( $wpdb->esc_like( $event_name ) ) . "'";
        } else	{
            $search_custom_vars = '';
        }

        $sql = "SELECT submission.id, invoice.submission_id, invoice.number, submission.active, submission.submission_date, 
        submission.organization, invoice.firstname, invoice.lastname, submission.price, submission.price_tax, 
        submission.notes, invoice.adress, invoice.zipcode, invoice.city, invoice.email, invoice.extra_information, submission.event_name,
        (SELECT COUNT(*) FROM {$wpdb->prefix}special_events_participants p WHERE p.submission_id = invoice.submission_id) as numberOfParticipants 
        FROM {$wpdb->prefix}special_events AS submission 
        INNER JOIN {$wpdb->prefix}special_events_invoices AS invoice ON invoice.submission_id = submission.submission_id WHERE 1=1 {$search_custom_vars}";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";

        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        
        return $result;
    }

} //class
