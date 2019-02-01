<?php

// GLOBAL FIELDS
// field values of the gravity form entry
$eventName;
$nameParticipant;
$emailParticipant;
$eventParticipant;
$organization;
$nameInvoice;
$adressInvoice;
$emailInvoice;
$reference;
$notes;
$dataSharing;

// settings
$price_single_ticket;
$btw_low_nr;
$btw_low;
$btw_high_nr;
$btw_high;
$food_price;
$payment_detail_description_low_btw;
$payment_detail_description_high_btw;
$payment_detail_event_nr_low_btw;
$payment_detail_event_nr_high_btw;
$dbInvoiceExpirationDays;
$dbInvoiceDescriptionText;
$dbExpirationDate;
$dbSubmissionDate;
$dbSubmissionId;

$dbInvoiceFirstName;
$dbInvoiceLastName;
$dbInvoiceAdress;
$dbInvoiceZipcode;
$dbInvoiceCity;
$dbInvoiceEmail;
$dbInvoiceExtraInformation;
$dbInvoiceEventNr;
$dbInvoiceFollowNumber;
$dbInvoiceNumber;
$dbInvoiceDebiteurNr;
$dbInvoiceBookNr;
$dbInvoiceCostPost;
$dbInvoiceExpirationDays;
$dbInvoiceDescriptionText;

$dbInvoiceBtwType;
$dbInvoiceDescription;
$dbInvoiceRowDescription;
$dbExpirationDate;

$dbPricePartHigh;
$dbPricePartHighBtw;
$dbPricePartHighTotal;
$dbPricePartLowTotal;
$dbPricePartLow;
$dbPricePartLowBtw;
$dbTicketPricePart;
$dbTicketPricePartBtw;
$dbTicketPricePartTotal;
$dbParkingPricePart;
$dbParkingPricePartBtw;
$dbParkingPricePartTotal;
$dbFoodPartPriceLow;
$dbFoodPartPriceLowBtw;
$dbFoodPartPriceLowTotal;
$dbFreeFieldsCollection;
$dbParticipants;
$dbMembershipPricePart;
$dbMembershipPricePartBTW;
$dbMembershipPricePartTotal;

// SETTINGS VARIABLES
$btw_high;
$btw_high_nr;
$btw_low;
$btw_low_nr;

/*
* Function to get entry value by label
*/
function get_value_by_label( $form, $entry, $label ) {
	foreach ( $form['fields'] as $field ) {
        var_dump($field);
        echo "test    ";
    	$lead_key = $field->label;
		if ( strToLower( $lead_key ) == strToLower( $key ) ) {
			return $entry[ $field->id ];
		}
	}
	return false;
}

function get_value_by_adminlabel( $form, $entry, $label ) {
	foreach ( $form['fields'] as $field ) {
        var_dump($field);
        echo "test    ";
    	$lead_key = $field->adminLabel;
		if ( strToLower( $lead_key ) == strToLower( $key ) ) {
			return $entry[ $field->id ];
		}
	}
	return false;
}

/*
* Use the gravity hook to process the data to our own tables
*/
add_action('gform_entry_created', 'processGravitySpecialEventsData', 10, form_id);
function processGravitySpecialEventsData($entry, $form)
{
    // Check if the form has a cssClass process-event so we know we need to process this form
    if (strpos($form['cssClass'], 'process-event') !== false) {
        $eventName = get_value_by_label( $form, $entry, "Eventnaam" );
        $nameParticipant = get_value_by_label( $form, $entry, "Naam" );
        $emailParticipant = get_value_by_label( $form, $entry, "E-mailadres" );
        $eventParticipant = get_value_by_label( $form, $entry, "Telefoon" );
        $organization = get_value_by_label( $form, $entry, "Organisatie" );
        $nameInvoice = get_value_by_label( $form, $entry, "T.a.v." );
        $adressInvoice = get_value_by_label( $form, $entry, "Adres" );
        $emailInvoice = get_value_by_label( $form, $entry, "E-mail" );
        $reference = get_value_by_label( $form, $entry, "Referentie" );
        $notes = get_value_by_label( $form, $entry, "Vraag of reactie" );
        $dataSharing = get_value_by_adminLabel( $form, $entry, "Gegevensverwerking" );
    }
    
    processSpecialEventsSettings();

    global $dbSubmissionType;

        // Check if the entry is already present in the database
        global $wpdb;
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}submissions WHERE submission_id = '" . $entry['id'] . "'");

        if ($exists > 1) {
            debug_to_consoleSpecialEvents("Entry already exists");
            return;
        }

        debug_to_consoleSpecialEvents("Adding new entry with id: " . $entry['id']);

        processSpecialEventsSettings();

        processSpecialEvents($entry);
        debug_to_consoleSpecialEvents("Processing submission completed");

        processInvoiceSpecialEventsPaymentFields($entry);
        debug_to_consoleSpecialEvents("Processing Invoice Payment completed");

        processPaymentFieldsSpecialEvents($entry);
        debug_to_consoleSpecialEvents("Processing Payment completed");

        processFreeFieldsSpecialEvents($entry, $form);
        debug_to_consoleSpecialEvents("Processing Free fields completed");

        saveSubmissionSpecialEvents();
        debug_to_consoleSpecialEvents("Saving entry completed");
}

/*
 * Process functions
 */
function processSpecialEventsSettings() {
    global $wpdb;
    $settings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}special_events_settings  where event_id = {$eventName}");

    if($settings && $settings[0]) {
        $price_single_ticket = $settings[0]->ticket_price_single;
        $btw_low_nr = $settings[0]->btw_low_number;
        $btw_low = $settings[0]->btw_low;
        $btw_high_nr = $settings[0]->btw_high_number;
        $btw_high = $settings[0]->btw_high;
        $food_price = $settings[0]->food_price;
        $payment_detail_description_low_btw = $settings[0]->payment_detail_description_low_btw;
        $payment_detail_description_high_btw = $settings[0]->payment_detail_description_high_btw;
        $payment_detail_event_nr_low_btw = $settings[0]->payment_detail_event_nr_low_btw;
        $payment_detail_event_nr_high_btw = $settings[0]->payment_detail_event_nr_high_btw;
        $dbInvoiceExpirationDays = $settings[0]->invoice_expiration_days;
        $dbInvoiceDescriptionText = $settings[0]->invoice_description_text;
    }
}

function processSpecialEvents($entry)
{
    // Submission id
    global $dbSubmissionId;
    $dbSubmissionId = $entry['id'];
	
    global $dbSubmissionDate;
    $dbSubmissionDate = date("Y-m-d H:i:s");

    global $dbExpirationDate;
    $dbExpirationDate = date('Y-m-d H:i:s', strtotime("+" . $dbInvoiceExpirationDays . " days"));
}

function processInvoiceSpecialEventsPaymentFields($entry)
{
    global $wpdb;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}special_events");

    $invoice_count = $count + 1;
    $invoice_cost_post = 'HGKF19';

    global $dbInvoiceFollowNumber;
    $dbInvoiceFollowNumber = '2019' . str_pad($invoice_count, 4, "0", STR_PAD_LEFT);

    global $dbInvoiceNumber;
    $dbInvoiceNumber = $invoice_cost_post . $dbInvoiceFollowNumber;

    global $dbInvoiceFileName;
    global $dbSubmissionOrganization;
    global $dbInvoiceLastName;
    $dbInvoiceFileName = date('Ymd') . '_' . $dbInvoiceNumber . '_' . (!empty($dbSubmissionOrganization) ? $dbSubmissionOrganization : $dbInvoiceLastName);

    global $dbInvoiceDebiteurNr;
    $dbInvoiceDebiteurNr = $invoice_count + 19000;

    global $dbInvoiceBookNr;
    $dbInvoiceBookNr = '71';

    global $dbInvoiceCostPost;
    $dbInvoiceCostPost = $invoice_cost_post;

    global $dbInvoiceBtwType;
    $dbInvoiceBtwType = '2';

    global $dbInvoiceDescription;
    $dbInvoiceDescription = 'Deelname Het Grootste Kennisfestival';

    global $dbInvoiceRowDescription;
    $dbInvoiceRowDescription = 'Deelname Het Grootste Kennisfestival';
}

function processPaymentFieldsSpecialEvents($entry)
{
    // Calculate price of a ticket
    global $dbTicketPrice;

    global $dbSubmissionType;
    global $price_single_ticket;
    global $price_group_ticket;
    if ($dbSubmissionType == 'groep') {
        $dbTicketPrice = $price_group_ticket;
    } else if ($dbSubmissionType == 'individu') {
        $dbTicketPrice = $price_single_ticket;
    }
 
    // Calculate high btw first substract the food (low btw) and split into parking ticket and ticket
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $dbTicketPricePart;
    global $dbTicketPricePartBtw;
    global $dbTicketPricePartTotal;
    global $btw_high;

    global $food_price;
    $price_ticket_ex_food = $dbTicketPrice - $food_price;

    // If there is a reduction price which is smaller than the food price the price part high will be below zero
    if ($price_ticket_ex_food < 0) {
        $price_ticket_ex_food = 0;
    }

    global $dbParticipants;
    $dbTicketPricePart = $price_ticket_ex_food * count($dbParticipants);
    $dbTicketPricePartBtw = $dbTicketPricePart * $btw_high;
    $dbTicketPricePartTotal = $dbTicketPricePart + $dbTicketPricePartBtw;

    $dbPricePartHigh = $dbTicketPricePart + $dbParkingPricePart;
    $dbPricePartHighBtw = $dbPricePartHigh * $btw_high;

    global $dbPricePartHighTotal;
    $dbPricePartHighTotal = $dbPricePartHigh + $dbPricePartHighBtw;
    
    // Calculate low btw
    // When the reduction price is smaller than the food price take the reduction price
    global $dbPricePartLow;
    $dbPricePartLow = $food_price;
    if ($dbTicketPrice < $food_price) {
        $dbPricePartLow = $dbTicketPrice;
    }

    global $dbPricePartLowBtw;
    global $dbPricePartLowTotal;
    global $dbFoodPartPriceLow;
    global $dbFoodPartPriceLowBtw;
    global $dbFoodPartPriceLowTotal;

    global $btw_low;

    $dbPricePartLow = count($dbParticipants) * $dbPricePartLow;
    $dbFoodPartPriceLow = $dbPricePartLow;
    $dbPricePartLowBtw = $dbPricePartLow * $btw_low;
    $dbFoodPartPriceLowBtw = $dbPricePartLowBtw;
    $dbPricePartLowTotal = $dbPricePartLow + $dbPricePartLowBtw;
    $dbFoodPartPriceLowTotal = $dbPricePartLowTotal;

    // Payment details
    global $dbTotalBtw;
    $dbTotalBtw = $dbPricePartLowBtw + $dbPricePartHighBtw;
    global $dbTotalPrice;
    $dbTotalPrice = ($dbTicketPrice * count($dbParticipants)) + $dbParkingPricePart + $dbMembershipPricePart;

    $rounded_total_price = number_format($dbTotalPrice * 100, 0, ',', '');
    $rounded_btw_part_low = number_format(($dbPricePartLowBtw) * 100, 0, ',', '');
    $rounded_btw_part_high = number_format(($dbPricePartHighBtw) * 100, 0, ',', '');

    global $dbTotalPriceBtw;
    $dbTotalPriceBtw = ($rounded_total_price + $rounded_btw_part_low + $rounded_btw_part_high) / 100;
}

function processFreeFieldsSpecialEvents($entry, $form)
{
    global $dbFreeFieldsCollection;
    $dbFreeFieldsCollection = [];
    // displays the types of every field in the form
    foreach ( $form['fields'] as $field ) {
        if ($field->adminLabel == 'free_field') {
            if (!empty($entry[$field->id])) {
                $freeField = [];
                $freeField['label'] = $field->label;
                $freeField['value'] = $entry[$field->id];

                $dbFreeFieldsCollection[] = $freeField;
            }
        }
    }
}

function saveSubmissionSpecialEvents()
{
    global $dbSubmissionId;
    global $dbSubmissionType;
    global $dbSubmissionDate;
    global $dbSubmissionOrganization;
    global $dbNumberParkingTickets;
    global $dbInvoiceFirstName;
    global $dbInvoiceLastName;
    global $dbInvoiceAdress;
    global $dbInvoiceZipcode;
    global $dbInvoiceCity;
    global $dbInvoiceEmail;
    global $dbInvoiceExtraInformation;
    global $dbInvoiceEventNr;
    global $dbInvoiceNumber;
    global $dbInvoiceFileName;
    global $dbInvoiceDebiteurNr;
    global $dbInvoiceBookNr;
    global $dbInvoiceCostPost;
    global $dbInvoiceExpirationDays;
    global $dbInvoiceBtwType;
    global $dbInvoiceDescription;
    global $dbInvoiceRowDescription;
    global $dbReductionCode;
    global $dbNotes;
    global $dbInvoiceFollowNumber;
    global $dbExpirationDate;
    global $dbTotalPriceBtw;
    global $dbTotalPrice;
    global $dbTotalBtw;
    global $dbParkingTicketFree;
    global $dbPricePartLowBtw;
    global $dbPricePartLow;
    global $dbPricePartLowTotal;
    global $dbPricePartHighTotal;
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $dbTicketPricePart;
    global $dbTicketPricePartBtw;
    global $dbTicketPricePartTotal;
    global $dbParkingPricePart;
    global $dbParkingPricePartBtw;
    global $dbParkingPricePartTotal;
    global $dbFoodPartPriceLow;
    global $dbFoodPartPriceLowBtw;
    global $dbFoodPartPriceLowTotal;
    global $dbFreeFieldsCollection;
    global $dbMembershipPricePart;
    global $dbMembershipPricePartBTW;
    global $dbMembershipPricePartTotal;

    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'submissions',
        array(
            'submission_id' => $dbSubmissionId,
            'submission_type' => $dbSubmissionType,
            'submission_date' => $dbSubmissionDate,
            'expiration_date' => $dbExpirationDate,
            'organization' => $dbSubmissionOrganization,
            'price' => $dbTotalPrice,
            'tax' => $dbTotalBtw,
            'price_tax' => $dbTotalPriceBtw,
            'parking_tickets' => $dbNumberParkingTickets,
            'reduction_code' => $dbReductionCode,
            'reduction_code_free_parking_ticket' => $dbParkingTicketFree,
            'notes' => $dbNotes
        )
    );

    debug_to_consoleSpecialEvents('Inserted submission with id: ' . $dbSubmissionId);

    debug_to_consoleSpecialEvents('Insert into table: ' . $wpdb->prefix . 'special_events_invoices');
    $wpdb->insert($wpdb->prefix . 'special_events_invoices',
        array(
            'submission_id' => $dbSubmissionId,
            'debiteur_nr' => $dbInvoiceDebiteurNr,
            'number' => $dbInvoiceNumber,
            'filename' => $dbInvoiceFileName,
            'book_nr' => $dbInvoiceBookNr,
            'cost_post' => $dbInvoiceCostPost,
            'description' => $dbInvoiceDescription,
            'row_description' => $dbInvoiceRowDescription,
            'follow_nr' => $dbInvoiceFollowNumber,
            'expiration_days' => $dbInvoiceExpirationDays,
            'firstname' => $dbInvoiceFirstName,
            'lastname' => $dbInvoiceLastName,
            'adress' => $dbInvoiceAdress,
            'zipcode' => $dbInvoiceZipcode,
            'city' => $dbInvoiceCity,
            'event_nr' => $dbInvoiceEventNr,
            'btw_type' => $dbInvoiceBtwType,
            'email' => $dbInvoiceEmail,
            'extra_information' => $dbInvoiceExtraInformation,
            'date' => $dbSubmissionDate,
            'expiration_date' => $dbExpirationDate
        )
    );

    debug_to_consoleSpecialEvents('Inserted invoice with submission id: ' . $dbSubmissionId);

    $wpdb->insert($wpdb->prefix . 'special_events_payment_details',
        array(
            'submission_id' => $dbSubmissionId,
            'entry_fee' => $dbTicketPricePart,
            'entry_fee_btw' => $dbTicketPricePartBtw,
            'entry_fee_total' => $dbTicketPricePartTotal,
            'parking_fee' => $dbParkingPricePart,
            'parking_fee_btw' => $dbParkingPricePartBtw,
            'parking_fee_total' => $dbParkingPricePartTotal,
            'food_fee' => $dbFoodPartPriceLow,
            'food_fee_btw' => $dbFoodPartPriceLowBtw,
            'food_fee_total' => $dbFoodPartPriceLowTotal,
            'total_low' => $dbPricePartLow,
            'total_low_btw' => $dbPricePartLowBtw,
            'total_low_total' => $dbPricePartLowTotal,
            'total_high' => $dbPricePartHigh,
            'total_high_btw' => $dbPricePartHighBtw,
            'total_high_total' => $dbPricePartHighTotal,
            'total' => $dbTotalPrice,
            'total_btw' => $dbTotalBtw,
            'total_total' => $dbTotalPriceBtw,
            'membership_fee' => $dbMembershipPricePart,
            'membership_fee_btw' => $dbMembershipPricePartBTW,
            'membership_fee_total' => $dbMembershipPricePartTotal
        )
    );

    // First payment detail: insert the entree payment detail row (with btw high)
    global $dbPricePartHighTotal;
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $btw_high_nr;
    global $payment_detail_event_nr_high_btw;
    global $payment_detail_description_high_btw;

    $wpdb->insert($wpdb->prefix . 'submission_crm_details',
        array(
            'submission_id' => $dbSubmissionId,
            'event' => $payment_detail_event_nr_high_btw,
            'price' => $dbPricePartHigh,
            'btw_type' => $btw_high_nr,
            'tax' => $dbPricePartHighBtw,
            'row_description' => $payment_detail_description_high_btw,
            'price_tax' => $dbPricePartHighTotal,
            'invoice_number' => $dbInvoiceNumber
        )
    );

    debug_to_consoleSpecialEvents('Inserted btw high in payment details table');

    // Second payment detail: insert the food payment detail row (with btw low)
    global $dbPricePartLowBtw;
    global $dbPricePartLow;
    global $dbPricePartLowTotal;
    global $btw_low_nr;
    global $payment_detail_event_nr_low_btw;
    global $payment_detail_description_low_btw;

    $wpdb->insert($wpdb->prefix . 'submission_crm_details',
        array(
            'submission_id' => $dbSubmissionId,
            'event' => $payment_detail_event_nr_low_btw,
            'price' => $dbPricePartLow,
            'btw_type' => $btw_low_nr,
            'tax' => $dbPricePartLowBtw,
            'row_description' => $payment_detail_description_low_btw,
            'price_tax' => $dbPricePartLowTotal,
            'invoice_number' => $dbInvoiceNumber
        )
    );

    debug_to_consoleSpecialEvents('Inserted btw low in payment details table');

    global $dbParticipants;
    foreach ($dbParticipants as $part) {
        $wpdb->insert($wpdb->prefix . 'special_events_participants',
            array(
                'submission_id' => $dbSubmissionId,
                'name' => $part['Naam'],
                'email' => $part['E-mailadres'],
                'phone' => $part['Telefoon'],
                'parkingticket' => $part['Parkeerticket (Kosten 10 euro per ticket) *'] == 'Ja' ? 1 : 0
            )
        );
    }

    debug_to_consoleSpecialEvents('Inserted participants into participants table');

    global $dbFreeFieldsCollection;
    foreach ($dbFreeFieldsCollection as $freeField) {
         $wpdb->insert($wpdb->prefix . 'submission_free_fields',
            array(
                'submission_id' => $dbSubmissionId,
                'label' => $freeField['label'],
                'value' => $freeField['value']
            )
        );
    }

    debug_to_consoleSpecialEvents('Inserted free fields in table');
}

function debug_to_consoleSpecialEvents($data)
{
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log( 'Debug php: " . $output . "' );</script>";
}

?>