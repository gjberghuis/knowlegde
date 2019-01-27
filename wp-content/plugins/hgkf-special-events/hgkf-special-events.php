<?php
/**
 * Plugin Name: Special events Het Grootste Kennisfestival
 * Plugin URI: none
 * Description: Overview and settings for special events
 * Version: 1.0
 * Author: Gert-Jan Berghuis
 * Author URI: none
 * License: none
 */
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

require_once('includes/settings-overview.php');
require_once('includes/submissions.php');
require_once('includes/edit-submission.php');
require_once('includes/participants.php');
require_once('includes/edit-participant.php');
require_once('includes/process-gravity-entry.php');

$submissionCollection = [];

function my_special_events_menu_items()
{
    $hookSubmissions = add_menu_page('Special events', 'Special events', 'manage_options', 'my_special_events_overview', 'render_special_events_overview_page');
    add_submenu_page(null, 'Aanmelding bewerken', 'Aanmelding bewerken', 'manage_options', 'edit_submission', 'render_edit_special_event_submission_page');
    add_submenu_page('my_special_events_overview', 'Deelnemers', 'Deelnemers', 'manage_options', 'participants', 'render_special_events_participants_page');
    add_submenu_page(null, 'Deelnemer bewerken', 'Deelnemer bewerken', 'manage_options', 'edit_participant', 'render_edit_special_events_participant_page');
  //  add_submenu_page('my_special_events_overview', 'Instellingen', 'Instellingen', 'manage_options', 'settings', 'render_special_events_settings_page');
    add_submenu_page('my_special_events_overview', 'Instellingen', 'Instellingen', 'manage_options', 'special_events_settings', 'render_special_events_settings_overview_page');
    add_submenu_page(null, 'Instellingen toevoegen', 'Instellingen toevoegen', 'manage_options', 'add_special_events_settings', 'render_add_special_events_settings_page');
    add_submenu_page(null, 'Instellingen bewerken', 'Instellingen bewerken', 'manage_options', 'edit_special_events_settings', 'render_edit_special_events_settings_page');
  
    add_action("load-$hookSubmissions", 'add_options_special_events');
}

function add_options_special_events()
{
    global $myListTable;

    $option = 'per_page';
    $args = array(
        'label' => 'Special events',
        'default' => 10,
        'option' => 'submissions_per_page'
    );
    add_screen_option($option, $args);

    $myListTable = new My_special_events_list;
}

add_action('admin_menu', 'my_special_events_menu_items');

function render_special_events_overview_page()
{
    wp_enqueue_script('jquery-ui-datepicker');
    wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
    wp_enqueue_style('jquery-ui');

    global $myListTable;
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
            echo '<input type="submit" value="Download deelnemers in csv" name="download_participants" />';
            echo '</td>';
            echo '<td style="padding:20px;">';
            echo '<input type="submit" value="Download facturen in csv" name="download_invoices_new" />';
            echo '</td>';
            echo '</tr>';
        echo '</table>';
    echo '</form>';
    ?>

    <?php

    echo '<form method="post" id="Submission" name="submissions">';
    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '">';
        $myListTable->prepare_items_special_events();
        $myListTable->search_box('zoeken', 'search');
        $myListTable->display();

    echo '</form>';
    echo '</div>';
}

function render_special_events_participants_page()
{
    global $participants;

    $participants = new participants_special_events_list ();
    echo '</pre><div class="wrap"><h2>Deelnemers overzicht</h2>';
    echo '<form action="" method="post" id="Participants" name="participants">';
    echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '">';
    $participants->prepare_special_events_participants();
    $participants->search_box('zoeken', 'search');
    $participants->display();
    echo '</form>';
}

add_action('admin_init', 'convert_special_events_csv');

function convert_special_events_csv()
{ 
    if (isset($_POST['download_participants']) || isset($_POST['download_invoices_new'])) {
        $downloadParticipantsFields = array('submission_id', 'submission_type','submission_date','organization','reduction_code','notes');
        $downloadInvoicesFields = array('submission_date','organization','reduction_code','notes','book_nr','debiteur_nr','cost_post','description','follow_nr',
        'firstname','lastname','adress','zipcode','city','email','extra_information','expiration_days');
   
        $date = '2016-11-01';
        $fromDate = date('Y-m-d', strtotime($date));

        if (!empty($_POST['from_date'])) {
            $fromDate = $_POST['from_date'];
        }
        $toDate = date('Y-m-d');
        if (!empty($_POST['to_date'])) {
            $toDate = $_POST['to_date'];
        }

        $filenamePrefix = 'facturen_';
        if (isset($_POST['download_participants'])) {
            $filenamePrefix = 'deelnemers_';
        }
        $output_file_name = $filenamePrefix . $fromDate . '_' . $toDate . '.csv';
        $delimiter = ',';

        global $wpdb;

        foreach ($wpdb->get_col("DESC " . $wpdb->prefix . 'submissions', 0) as $column_name) {
            if (isset($_POST['download_participants']) && in_array($column_name, $downloadParticipantsFields)) {
                $header[] = $column_name;
            } elseif (isset($_POST['download_invoices_new']) && in_array($column_name, $downloadInvoicesFields)) {
                $header[] = $column_name;
            }
        }

        foreach ($wpdb->get_col("DESC " . $wpdb->prefix . 'special_events_invoices', 0) as $column_name) {
            if ($column_name != 'submission_id') {
                if (isset($_POST['download_participants']) && in_array($column_name, $downloadParticipantsFields)) {
                    $header[] = $column_name;
                } elseif (isset($_POST['download_invoices_new']) && in_array($column_name, $downloadInvoicesFields)) {
                    $header[] = $column_name;
                }
            }
        }

        if (isset($_POST['download_participants'])) {
            $header[] = "participant_firstname";
            $header[] = "participant_lastname";
            $header[] = "participant_email";
            $header[] = "participant_phone";
            $header[] = "participant_parkingticket";
         /*   $header[] = "free_field_1_label";
            $header[] = "free_field_1_value";
            $header[] = "free_field_2_label";
            $header[] = "free_field_2_value";
            $header[] = "free_field_3_label";
            $header[] = "free_field_3_value";
            $header[] = "free_field_4_label";
            $header[] = "free_field_4_value";
            $header[] = "free_field_5_label";
            $header[] = "free_field_5_value";
            $header[] = "free_field_6_label";
            $header[] = "free_field_6_value";*/
        } elseif (isset($_POST['download_invoices_new'])) {
            $header[] = "payment_event";
            $header[] = "payment_row_description";
            $header[] = "payment_price";
            $header[] = "payment_btw_type";
            $header[] = "payment_tax";
        }

        $f = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename=' . $output_file_name);
        fputcsv($f, $header, ';');

        $submissions = $wpdb->get_results("SELECT submission.*, invoice.* FROM {$wpdb->prefix}submissions  as submission INNER JOIN {$wpdb->prefix}special_events_invoices as invoice ON invoice.submission_id = submission.submission_id WHERE active < 1 OR active is NULL AND submission_date >= '" . $fromDate . "' AND submission_date <= '" . $toDate . "'");

        /* loop through array  */
        foreach ($submissions as $submission) {
            $submissionTempArray = (array)$submission;
            $submissionArray = $submissionTempArray;
            foreach ($submissionTempArray as $key => $value) {
                if (isset($_POST['download_participants']) && !in_array($key, $downloadParticipantsFields)) {
                    unset($submissionArray[$key]);
                } elseif (isset($_POST['download_invoices_new']) && !in_array($key, $downloadInvoicesFields)) {
                    unset($submissionArray[$key]);
                }
            }
            
            if (!empty($submissionArray['price'])) {
                $submissionArray['price'] = number_format($submissionArray['price'], 2, ',', '');
            }
            if (!empty($submissionArray['price_tax'])) {
                $submissionArray['price_tax'] = number_format($submissionArray['price_tax'], 2, ',', '');
            }
            if (!empty($submissionArray['tax'])) {
                $submissionArray['tax'] = number_format($submissionArray['tax'], 2, ',', '');
            }

            if (isset($_POST['download_participants'])) {
                $submissionId = $submissionTempArray['submission_id'];

                $submissionsOParticipants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}special_events_participants where submission_id = " . $submissionId);
               
                foreach ($submissionsOParticipants as $participant) {
                    $participantArray = (array)$participant;

                    $lineArray = $submissionArray;
                
                    $nameParticipant = explode(" ", $participantArray['name']);
                    $firstnameParticipant = "";
                    $lastnameParticipant = "";
                    for ($i = 0; $i <= count($nameParticipant); $i++) {
                        if ($i == 0) {
                            $firstnameParticipant = $nameParticipant[$i];
                        } else {
                            $lastnameParticipant .= $nameParticipant[$i];    
                        }
                    }

                    $lineArray[] = $firstnameParticipant;
                    $lineArray[] = $lastnameParticipant;
                    $lineArray[] = $participantArray['email'];
                    $lineArray[] = $participantArray['phone'];
                    $lineArray[] = $participantArray['parkingticket'];

  fputcsv($f, $lineArray, ';'); 
                }

               /* $freeFieldResults = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}submission_free_fields WHERE submission_id = " . $submissionId);
              
                if (count($freeFieldResults) > 0) {
                    
                    for ($i = 0; $i <= count($freeFieldResults); $i++) {
                        if ($i < 6) {
                            $lineArray[] = $freeFieldResults[i]->label;
                            $lineArray[] = $freeFieldResults[i]->value;
                        }
                    }
                } */ 

                 /** default php csv handler **/
               
            } elseif (isset($_POST['download_invoices_new'])) {
                $submissionId = $submissionTempArray['submission_id'];
                $submissionPaymentDetails = $wpdb->get_results("SELECT event as payment_event, row_description as payment_row_description, price as payment_price,btw_type as payment_btw_type, tax as payment_tax FROM {$wpdb->prefix}submission_crm_details where submission_id = " . $submissionId);

                 foreach ($submissionPaymentDetails as $paymentDetail) {
                    $paymentArray = (array)$paymentDetail;
                    
                    $lineArray = $submissionArray;
                    $csvArray = array_merge($lineArray, $paymentArray);
                    fputcsv($f, $csvArray, ';');
                }
            } else {
                fputcsv($f, $submissionArray, ';');
            }
        }
        fclose($f);
        exit;
    }
}

function specialEventObjectToArray($obj)
{
    if (is_object($obj)):
        $object = get_object_vars($obj);
    endif;

    return array_map('specialEventObjectToArray', $object); // return the object, converted in array.
}


function render_special_events_settings_overview_page()
{
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && !empty($_GET['id'])) {
        global $wpdb;

        $id = $_GET['id'];
        $result = $wpdb->delete($wpdb->prefix . 'special_events_settings', array( 'id' => $id), array( '%s', '%s' ) );

        if ($result > 0) {
            $path = 'admin.php?page=special_events_settings';
            $url = admin_url($path);
            wp_redirect($url);
        }
    }

    global $settings;

    $settings = new settings();
    echo '</pre><div class="wrap"><h2>Speciale evenementen instellingen</h2>';
    $settings->prepare_special_events_settings();

    $path = 'admin.php?page=add_special_events_settings';
    $url = admin_url($path);

    echo '<a href="' . $url . '"><button>Nieuwe instellingen toevoegen</button></a>';

    $settings->display();
}

function render_add_special_events_settings_page() { 
    if (isset($_POST['add_special_events_settings'])) {
        $event_name = '';
        if (!empty($_POST['event_name'])) {
            $event_name  = $_POST['event_name'];
        }

        $event_id = 0;
        if (!empty($_POST['event_id'])) {
            $event_id  = $_POST['event_id'];
        }
        
        $ticket_price_single = 0;
        if (!empty($_POST['ticket_price_single'])) {
            $ticket_price_single = $_POST['ticket_price_single'];
        }

        $btw_low_number = 0;
        if (!empty($_POST['btw_low_number'])) {
            $btw_low_number  = $_POST['btw_low_number'];
        }

        $btw_low = 0;
        if (!empty($_POST['btw_low'])) {
            $btw_low  = $_POST['btw_low'];
        }

        $btw_high_number = 0;
        if (!empty($_POST['btw_high_number'])) {
            $btw_high_number  = $_POST['btw_high_number'];
        }

        $btw_high = 0;
        if (!empty($_POST['btw_high'])) {
            $btw_high  = $_POST['btw_high'];
        }

        $food_price = 0;
        if (!empty($_POST['food_price'])) {
            $food_price = $_POST['food_price'];
        }

        $payment_detail_description_low_btw = '';
        if (!empty($_POST['payment_detail_description_low_btw'])) {
            $payment_detail_description_low_btw  = $_POST['payment_detail_description_low_btw'];
        }

        $payment_detail_description_high_btw = '';
        if (!empty($_POST['payment_detail_description_high_btw'])) {
            $payment_detail_description_high_btw  = $_POST['payment_detail_description_high_btw'];
        }

        $payment_detail_event_nr_low_btw = 0;
        if (!empty($_POST['payment_detail_event_nr_low_btw'])) {
            $payment_detail_event_nr_low_btw  = $_POST['payment_detail_event_nr_low_btw'];
        }
        
        $payment_detail_event_nr_high_btw = 0;
        if (!empty($_POST['payment_detail_event_nr_high_btw'])) {
            $payment_detail_event_nr_high_btw  = $_POST['payment_detail_event_nr_high_btw'];
        }

        $invoice_expiration_days = 0;
        if (!empty($_POST['invoice_expiration_days'])) {
            $invoice_expiration_days  = $_POST['invoice_expiration_days'];
        }

        $invoice_description_text = '';
        if (!empty($_POST['invoice_description_text'])) {
            $invoice_description_text  = $_POST['invoice_description_text'];
        }

        global $wpdb;
        $result = $wpdb->insert($wpdb->prefix .'special_events_settings', array(
            'event_name' => $event_name,
            'event_id' => $event_id,
            'ticket_price_single' => $ticket_price_single,
            'btw_low_number' => $btw_low_number,
            'btw_low' => $btw_low,
            'btw_high_number' => $btw_high_number,
            'btw_high' => $btw_high,
            'food_price' => $food_price,
            'payment_detail_description_low_btw' => $payment_detail_description_low_btw,
            'payment_detail_description_high_btw' => $payment_detail_description_high_btw,
            'payment_detail_event_nr_low_btw' => $payment_detail_event_nr_low_btw,
            'payment_detail_event_nr_high_btw' => $payment_detail_event_nr_high_btw,
            'invoice_expiration_days' => $invoice_expiration_days,
            'invoice_description_text' => $invoice_description_text),
            array( '%s', '%s' ) )
            ;

        if ($result > 0) {
            $path = 'admin.php?page=special_events_settings';
            $url = admin_url($path);
            wp_redirect($url);
        }
    }

    echo '</pre>';
    echo '<div class="wrap">';

    $path = 'admin.php?page=special_events_settings';
    $url = admin_url($path);

    echo '<a href="' . $url . '">< Terug naar het overzicht</a>';
    echo '<h2>Instellingen toevoegen</h2>';
    echo '<br/>';
    echo '<form action="" method="post">';
    echo '<table>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="event_name" style="margin-right: 20px;">Event naam</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" style="width:600px;" name="event_name" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr><td><br/></td></tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="event_id" style="margin-right: 20px;">Event id</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" style="width:200px;" name="event_id" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr><td><br/></td></tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="ticket_price_single" style="margin-right: 20px;">Ticket prijs</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width:200px;" name="ticket_price_single"/>';
    echo '</td>';
    echo '</tr>';

    echo '<tr><td><br/></td></tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="btw_low_number" style="margin-right: 20px;">BTW nummer laag</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width:100px;" name="btw_low_number" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="btw_low" style="margin-right: 20px;">BTW laag tarief</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;"  step="0.01 name="btw_low" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="btw_high_number" style="margin-right: 20px;">BTW nummer hoog</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;" name="btw_high_number" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="btw_high" style="margin-right: 20px;">BTW hoog tarief</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;" step="0.01" name="btw_high" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="food_price" style="margin-right: 20px;">Prijs voor vertering</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;" step="0.01" name="food_price" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="payment_detail_description_low_btw" style="margin-right: 20px;">Exact: beschrijving laag btw</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" style="width: 600px;" name="payment_detail_description_low_btw" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="payment_detail_description_high_btw" style="margin-right: 20px;">Exact: beschrijving hoog btw</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" style="width: 600px;" name="payment_detail_description_high_btw" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="payment_detail_event_nr_low_btw" style="margin-right: 20px;">Exact: evenement nummer laag btw</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;" name="payment_detail_event_nr_low_btw" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="payment_detail_event_nr_high_btw" style="margin-right: 20px;">Exact: evenement nummer hoog btw</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="number" style="width: 100px;" name="payment_detail_event_nr_high_btw" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="invoice_expiration_days" style="margin-right: 20px;">Factuur: betalingstermijn (in dagen)</label>';
    echo '</td>';
    echo '<td>';
    echo '<input type="text" style="width: 100px;" name="invoice_expiration_days" />';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td>';
    echo '<label for="invoice_description_text" style="margin-right: 20px;">Factuur: beschrijving op factuur</label>';
    echo '</td>';
    echo '<td>';
    echo '<textarea type="text" style="width: 600px; height: 200px;" name="invoice_description_text" id="invoice_description_text"></textarea>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td style="padding:20px;">';
    echo '<input type="submit" value="Toevoegen" name="add_special_events_settings" />';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</form>';
}

function render_edit_special_events_settings_page() {
    global $wpdb;

    if (isset($_GET['action']) && $_GET['action'] == 'edit_special_events_settings' && isset($_GET['id'])) {
        
        $settings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}special_events_settings WHERE id = " . $_GET['id']);

        if (count($settings) > 0) {
            if (isset($_POST['save_special_events_settings'])) {
            
                if (!empty($_POST['event_name'])) {
                    $settings[0]->event_name = $_POST['event_name'];
                }

                if (!empty($_POST['event_id'])) {
                    $settings[0]->event_id = $_POST['event_id'];
                }

                if (!empty($_POST['ticket_price_single'])) {
                    $settings[0]->ticket_price_single = $_POST['ticket_price_single'];
                }

                if (!empty($_POST['btw_low_number'])) {
                    $settings[0]->btw_low_number = $_POST['btw_low_number'];
                }
                
                if (!empty($_POST['btw_low'])) {
                    $settings[0]->btw_low = $_POST['btw_low'];
                }
                
                if (!empty($_POST['btw_high_number'])) {
                    $settings[0]->btw_high_number = $_POST['btw_high_number'];
                }
                
                if (!empty($_POST['btw_high'])) {
                    $settings[0]->btw_high = $_POST['btw_high'];
                }

                if (!empty($_POST['food_price'])) {
                    $settings[0]->food_price = $_POST['food_price'];
                }

                if (!empty($_POST['payment_detail_description_low_btw'])) {
                    $settings[0]->payment_detail_description_low_btw = $_POST['payment_detail_description_low_btw'];
                }

                if (!empty($_POST['payment_detail_description_high_btw'])) {
                    $settings[0]->payment_detail_description_high_btw = $_POST['payment_detail_description_high_btw'];
                }

                if (!empty($_POST['payment_detail_event_nr_low_btw'])) {
                    $settings[0]->payment_detail_event_nr_low_btw = $_POST['payment_detail_event_nr_low_btw'];
                }

                if (!empty($_POST['payment_detail_event_nr_high_btw'])) {
                    $settings[0]->payment_detail_event_nr_high_btw = $_POST['payment_detail_event_nr_high_btw'];
                }

                if (!empty($_POST['invoice_expiration_days'])) {
                    $settings[0]->invoice_expiration_days = $_POST['invoice_expiration_days'];
                }

                if (!empty($_POST['invoice_description_text'])) {
                    $settings[0]->invoice_description_text = $_POST['invoice_description_text'];
                }

                $result = $wpdb->update($wpdb->prefix  . 'special_events_settings',
                    array(
                        'event_name' => $settings[0]->event_name,
                        'event_id' => $settings[0]->event_id,
                        'ticket_price_single' => $settings[0]->ticket_price_single,
                        'btw_low_number' => $settings[0]->btw_low_number,
                        'btw_low' => $settings[0]->btw_low,
                        'btw_high_number' => $settings[0]->btw_high_number,
                        'btw_high' => $settings[0]->btw_high,
                        'food_price' => $settings[0]->food_price,
                        'payment_detail_description_low_btw' => $settings[0]->payment_detail_description_low_btw,
                        'payment_detail_description_high_btw' => $settings[0]->payment_detail_description_high_btw,
                        'payment_detail_event_nr_low_btw' => $settings[0]->payment_detail_event_nr_low_btw,
                        'payment_detail_event_nr_high_btw' => $settings[0]->payment_detail_event_nr_high_btw,
                        'invoice_expiration_days' => $settings[0]->invoice_expiration_days,
                        'invoice_description_text' => $settings[0]->invoice_description_text),
                    array('id' => $settings[0]->id));

                if ($result > 0) {
                    $path = 'admin.php?page=special_events_settings';
                    $url = admin_url($path);
                    wp_redirect($url);
                }
            }

            echo '</pre>';
            echo '<div class="wrap">';

            $path = 'admin.php?page=special_events_settings';
            $url = admin_url($path);

            echo '<a href="' . $url . '">< Terug naar het overzicht</a>';
            echo '<h2>Instellingen bewerken</h2>';
            echo '<br/>';
            echo '<form action="" method="post">';
            echo '<table>';
 
            echo '<tr>';
            echo '<td>';
            echo '<label for="event_name" style="margin-right: 20px;">Event naam</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width:600px;" name="event_name" value="' . $settings[0]->event_name . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr><td><br/></td></tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="event_id" style="margin-right: 20px;">Event id</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width:200px;" name="event_id" value="' . $settings[0]->event_id . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr><td><br/></td></tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="ticket_price_single" style="margin-right: 20px;">Ticket prijs</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width:200px;" name="ticket_price_single" value="' . $settings[0]->ticket_price_single . '"/>';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr><td><br/></td></tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="btw_low_number" style="margin-right: 20px;">BTW nummer laag</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width:100px;" name="btw_low_number" value="' . $settings[0]->btw_low_number . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="btw_low" style="margin-right: 20px;">BTW laag tarief</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width: 100px;" step="0.01" name="btw_low" value="' . $settings[0]->btw_low . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="btw_high_number" style="margin-right: 20px;">BTW nummer hoog tarief</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width: 100px;" name="btw_high_number" value="' . $settings[0]->btw_high_number . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="btw_high" style="margin-right: 20px;">BTW hoog tarief</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width: 100px;"  step="0.01" name="btw_high" value="' . $settings[0]->btw_high . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="food_price" style="margin-right: 20px;">Prijs voor vertering</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="number" style="width: 100px;" name="food_price" step="0.01" value="' . $settings[0]->food_price . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="payment_detail_description_low_btw" style="margin-right: 20px;">Exact: beschrijving laag btw</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width: 600px;" name="payment_detail_description_low_btw" value="' . $settings[0]->payment_detail_description_low_btw . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="payment_detail_description_high_btw" style="margin-right: 20px;">Exact: beschrijving hoog btw</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width: 600px;" name="payment_detail_description_high_btw" value="' . $settings[0]->payment_detail_description_high_btw . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="payment_detail_event_nr_low_btw" style="margin-right: 20px;">Exact: evenement nummer laag btw</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width: 100px;" name="payment_detail_event_nr_low_btw" value="' . $settings[0]->payment_detail_event_nr_low_btw . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="payment_detail_event_nr_high_btw" style="margin-right: 20px;">Exact: evenement nummer hoog btw</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width: 100px;" name="payment_detail_event_nr_high_btw" value="' . $settings[0]->payment_detail_event_nr_high_btw . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="invoice_expiration_days" style="margin-right: 20px;">Factuur: betalingstermijn (in dagen)</label>';
            echo '</td>';
            echo '<td>';
            echo '<input type="text" style="width: 100px;" name="invoice_expiration_days" value="' . $settings[0]->invoice_expiration_days . '" />';
            echo '</td>';
            echo '</tr>';
        
            echo '<tr>';
            echo '<td>';
            echo '<label for="invoice_description_text" style="margin-right: 20px;">Factuur: beschrijving op factuur</label>';
            echo '</td>';
            echo '<td>';
            echo '<textarea type="text" style="width: 600px; height: 200px;" name="invoice_description_text" id="invoice_description_text">'. $settings[0]->invoice_description_text . '</textarea>';
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td style="padding:20px;">';
            echo '<input type="submit" value="Bewerken" name="save_special_events_settings" />';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            echo '</form>';
        }
        else {
            $path = 'admin.php?page=special_events_settings';
            $url = admin_url($path);
            wp_redirect($url);
        }
    } else {
        $path = 'admin.php?page=special_events_settings';
        $url = admin_url($path);
        wp_redirect($url);
    }
}