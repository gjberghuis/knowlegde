<?php

// GLOBAL FIELDS
// field values of the gravity form entry
$eventId;
$eventName;
$nameParticipant;
$firstNameParticipant;
$lastNameParticipant;
$emailParticipant;
$phoneParticipant;
$organization;
$nameInvoice;
$firstNameInvoice;
$lastNameInvoice;
$streetInvoice;
$zipcodeInvoice;
$cityInvoice;
$emailInvoice;
$reference;
$notes;
$dataSharing;

// settings
$dbPriceSingleTicket;
$dbBtwLowNr;
$dbBtwLow;
$dbBtwHighNr;
$dbBtwHigh;
$dbFoodPrice;
$dbBtwPaymentDetailDescriptionLowBtw;
$dbBtwPaymentDetailDescriptionHighBtw;
$dbBtwPaymentDetailEventNrLowBtw;
$dbBtwPaymentDetailEventNrHighBtw;
$dbInvoiceExpirationDays;
$dbInvoiceDescriptionText;
$dbInvoiceCostPost;
$dbInvoiceFollowNumberPrefix;
$dbInvoiceBookNr;
$dbInvoiceRelationNrStart;

// generated fields during submission
$dbExpirationDate;
$dbSubmissionDate;
$dbSubmissionId;

// generated fields
$dbInvoiceBtwType;
$dbInvoiceDescription;
$dbInvoiceRowDescription;
$dbInvoiceFollowNumber;
$dbInvoiceNumber;
$dbInvoiceDebiteurNr;
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


/*
* Function to get entry value by label
*/
function get_value_by_label( $form, $entry, $label ) {
    
	foreach ( $form['fields'] as $field ) {
        $lead_key = $field->label;
		if ( strToLower( $lead_key ) == strToLower( $label ) ) {
			return $entry[$field->id];
		}
	}
	return false;
}

function get_value_by_adminlabel( $form, $entry, $label ) {
	foreach ( $form['fields'] as $field ) {
    	$lead_key = $field->adminLabel;
		if ( strToLower( $lead_key ) == strToLower( $label ) ) {
			return $entry[ $field->id ];
		}
	}
	return false;
}

function get_name_participant( $form, $entry) {
    global $firstNameParticipant;
    global $lastNameParticipant;

    foreach ( $form['fields'] as $field ) {
        $lead_key = $field->label;
		if (strToLower( $lead_key ) === 'naam' ) {
            if ($field->type == 'name') {
                foreach ($field->inputs as $input) {
                    if ($input['label'] === 'Voornaam') {
                        $name = $entry[$input['id']];
                        $firstNameParticipant = $entry[$input['id']];
                    }
                    if ($input['label'] === 'Tussenvoegsel') {
                        $name = $name . ' ' . $entry[$input['id']];
                    }
                    if ($input['label'] === 'Achternaam') {
                        $name = $name . ' ' . $entry[$input['id']];
                        $lastNameParticipant = $entry[$input['id']];         
                    }
                }
            }
            return $name;
        }
    }
}

function get_name_invoice( $form, $entry) {
    global $firstNameInvoice;
    global $lastNameInvoice;

    foreach ( $form['fields'] as $field ) {
        $lead_key = $field->label;
    
		if (strToLower( $lead_key ) === 't.a.v.' ) {
            if ($field->type == 'name') {
                foreach ($field->inputs as $input) {
                    if ($input['label'] === 'Voornaam') {
                        $name = $entry[$input['id']];
                        $firstNameInvoice = $entry[$input['id']];
                    }
                    if ($input['label'] === 'Tussenvoegsel') {
                        $name = $name . ' ' . $entry[$input['id']];
                    }
                    if ($input['label'] === 'Achternaam') {
                        $name = $name . ' ' . $entry[$input['id']];
                        $lastNameInvoice = $entry[$input['id']];
                    }
                }
            }
            return $name;
        }
    }
}

function get_adres_invoice( $form, $entry) {
    global $streetInvoice;
    global $zipcodeInvoice;
    global $cityInvoice;

    foreach ( $form['fields'] as $field ) {
        $lead_key = $field->label;
    
		if (strToLower( $lead_key ) === 'adres' ) {
            if (strToLower($field->type) == 'address') {
                foreach ($field->inputs as $input) {
                    if (strToLower($input['label']) === 'straat + huisnummer') {
                        $streetInvoice = $entry[$input['id']];
                    }
                    if (strToLower($input['label']) === 'postcode') {
                        $zipcodeInvoice = $entry[$input['id']];
                    }
                    if (strToLower($input['label']) === 'plaats') {
                        $cityInvoice = $entry[$input['id']];
                    }
                }
            }
            return $name;
        }
    }
}


/*
* Use the gravity hook to process the data to our own tables
*/
add_action('gform_entry_created', 'processGravitySpecialEventsData', 10, 2);
function processGravitySpecialEventsData($entry, $form)
{
    // Check if the form has a cssClass process-event so we know we need to process this form
    if ($form['cssClass'] == 'process-event') {
         // Check if the entry is already present in the database
        global $wpdb;
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}special_events WHERE submission_id = '" . $entry['id'] . "'");

        if ($exists > 1) {
            debug_to_consoleSpecialEvents("Entry already exists");
            return;
        }

        debug_to_consoleSpecialEvents("Adding new entry with id: " . $entry['id']);

        processSpecialEventsFormFields($form, $entry);
        debug_to_consoleSpecialEvents("Processing special event form fields");

        processSpecialEventsSettings();
        debug_to_consoleSpecialEvents("Processing special event setttings completed");

        processSpecialEvents($entry);
        debug_to_consoleSpecialEvents("Processing special event completed");

        processSpecialEventsParticipants($entry);
        debug_to_consoleSpecialEvents("Processing special event participants completed");

        processInvoiceSpecialEventsPaymentFields($entry);
        debug_to_consoleSpecialEvents("Processing Invoice Payment completed");

        processPaymentFieldsSpecialEvents($entry);
        debug_to_consoleSpecialEvents("Processing Payment completed");

    //  processFreeFieldsSpecialEvents($entry, $form);
    //  debug_to_consoleSpecialEvents("Processing Free fields completed");

        saveSubmissionSpecialEvents();
        debug_to_consoleSpecialEvents("Saving entry completed");
    }

   
}

/*
 * Process functions
 */
function processSpecialEventsSettings() {
    global $wpdb;
    global $eventId;
    global $eventName;
    global $dbPriceSingleTicket;
    global $dbBtwLow;
    global $dbBtwLow;
    global $dbBtwHighNr;
    global $dbBtwHigh;
    global $dbFoodPrice;
    global $dbBtwPaymentDetailDescriptionLowBtw;
    global $dbBtwPaymentDetailDescriptionHighBtw;
    global $dbBtwPaymentDetailEventNrLowBtw;
    global $dbBtwPaymentDetailEventNrHighBtw;
    global $dbInvoiceExpirationDays;
    global $dbInvoiceDescriptionText;
    global $dbInvoiceCostPost;
    global $dbInvoiceFollowNumberPrefix;
    global $dbInvoiceBookNr;
    global $dbInvoiceRelationNrStart;
    global $eventName;

    $settings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}special_events_settings  where event_id = '{$eventId}'");

    if($settings && $settings[0]) {
        $dbPriceSingleTicket = $settings[0]->ticket_price_single;
        $dbBtwLow = $settings[0]->btw_low_number;
        $dbBtwLow = $settings[0]->btw_low;
        $dbBtwHighNr = $settings[0]->btw_high_number;
        $dbBtwHigh = $settings[0]->btw_high;
        $dbFoodPrice = $settings[0]->food_price;
        $dbBtwPaymentDetailDescriptionLowBtw = $settings[0]->payment_detail_description_low_btw;
        $dbBtwPaymentDetailDescriptionHighBtw = $settings[0]->payment_detail_description_high_btw;
        $dbBtwPaymentDetailEventNrLowBtw = $settings[0]->payment_detail_event_nr_low_btw;
        $dbBtwPaymentDetailEventNrHighBtw = $settings[0]->payment_detail_event_nr_high_btw;
        $dbInvoiceExpirationDays = $settings[0]->invoice_expiration_days;
        $dbInvoiceDescriptionText = $settings[0]->invoice_description_text;
        $dbInvoiceCostPost = $settings[0]->cost_post;
        $dbInvoiceFollowNumberPrefix = $settings[0]->follow_number_prefix;
        $dbInvoiceBookNr = $settings[0]->book_nr;
        $dbInvoiceRelationNrStart = $settings[0]->relation_nr_start;
        $eventName = $settings[0]->event_name;
    }
}

function processSpecialEventsFormFields($form, $entry) {
    global $eventId;
    global $nameParticipant;
    global $emailParticipant;
    global $phoneParticipant;
    global $organization;
    global $nameInvoice;
    global $emailInvoice;
    global $reference;
    global $notes;
    global $dataSharing;

    $eventId = get_value_by_label( $form, $entry, "Eventlabel" );
    
    $nameParticipant = get_name_participant( $form, $entry );
    $emailParticipant = get_value_by_label( $form, $entry, "E-mailadres" );
    $phoneParticipant = get_value_by_label( $form, $entry, "Telefoon" );
    $organization = get_value_by_label( $form, $entry, "Organisatie" );
    $nameInvoice = get_name_invoice( $form, $entry );
    get_adres_invoice( $form, $entry);
    $emailInvoice = get_value_by_label( $form, $entry, "E-mail" );
    $reference = get_value_by_label( $form, $entry, "Referentie" );
    $notes = get_value_by_label( $form, $entry, "Vraag of reactie" );
    $dataSharing = get_value_by_adminLabel( $form, $entry, "Gegevensverwerking" );
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


function processSpecialEventsParticipants($entry, $form) {
    global $dbParticipants;
    global $dbNumberParkingTickets;
    global $nameParticipant;
    global $emailParticipant;
    global $phoneParticipant;
    $dbNumberParkingTickets = 0;

    $dbParticipants = [];
    // The main participant is the non repeater participant
    $mainParticipant = [];
    $mainParticipant['Naam'] = $nameParticipant;
    $mainParticipant['E-mailadres'] = $emailParticipant;
    $mainParticipant['Telefoon'] = $phoneParticipant;
    
/*
    if (!empty($entry[44])) {
        $mainParticipant['Parkeerticket (Kosten 10 euro per ticket) *'] = $entry[44];
        if ($entry[44] == 'Ja') {
            $dbNumberParkingTickets++;
        }
    }*/
   
    $dbParticipants[] = $mainParticipant;
/*
    // Search for repeater fields and loop through the repeater field -> 'Meer deelnemers toevoegen'
    $repeaterID = '';
    foreach ($form['fields'] as $key => $formField) {
        if (get_class($formField) == 'GF_Field_Repeater') {
            if ($formField[label] == 'deelnemers-repeater') {
                $repeaterID = $formField[id];
            }
        }
    }

    // SEARCH THROUGH ENTRY FOR THE FIELD ID OF THE REPEATER
    foreach ($entry as $key => $formEntry) {
        // Check if the field is the repeater field
        if ($key == $repeaterID) {
            // Breakdown the repeater's inputs. us = un-serialized.
            $usEntry = unserialize($formEntry);
        }
    }

    if(is_array($usEntry) || is_object($usEntry)) {
        foreach ($usEntry as $keyOneEntry => $oneEntry) {
            $participant = array();
            // MATCH UP THE FIELDS AND INPUTS
            $singleRepeat = '';
            foreach ($form[fields] as $key => $formField) {
                $fieldId = $formField[id];
                if (array_key_exists($fieldId, $oneEntry)) {
                    $singleInput = implode(" ", $oneEntry[$fieldId]);
                    // Only include inputs that aren't empty
                    if (!empty($singleInput)) {
                        $participant[$formField[label]] = $singleInput;
                        $singleRepeat .= $formField[label] . ": " . $singleInput . ", ";
                    }
                }
            }
            
            if($participant['Parkeerticket (Kosten 10 euro per ticket)'] == 'Ja') {
                $dbNumberParkingTickets++;
            } 

            $dbParticipants[] = $participant;

         //   array_push($repeats, $singleRepeat);
            unset($singleRepeat);
        }
    }*/
}

function processInvoiceSpecialEventsPaymentFields($entry)
{
    global $wpdb;
    global $eventId;
  
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}special_events where event_id = '{$eventId}'"); 
    $invoice_count = $count + 1;

    global $dbInvoiceFollowNumber;
    global $dbInvoiceFollowNumberPrefix;
    $dbInvoiceFollowNumber = $dbInvoiceFollowNumberPrefix . str_pad($invoice_count, 4, "0", STR_PAD_LEFT);

    global $dbInvoiceCostPost;
    global $dbInvoiceNumber;
    $dbInvoiceNumber = $dbInvoiceCostPost . $dbInvoiceFollowNumber;

    global $dbInvoiceFileName;
    global $dbSubmissionOrganization;
    $dbInvoiceFileName = date('Ymd') . '_' . $dbInvoiceNumber . '_' . (!empty($dbSubmissionOrganization) ? $dbSubmissionOrganization : $lastNameInvoice);

    global $dbInvoiceDebiteurNr;
    global $dbInvoiceRelationNrStart;
    $dbInvoiceDebiteurNr = $invoice_count + $dbInvoiceRelationNrStart;

    global $dbInvoiceBtwType;
    $dbInvoiceBtwType = '2';

    global $dbInvoiceDescription;
    $dbInvoiceDescription = 'Deelname Het Grootste Kennisfestival';

    global $dbInvoiceRowDescription;
    $dbInvoiceRowDescription = 'Deelname Het Grootste Kennisfestival';
}

function processPaymentFieldsSpecialEvents($entry)
{
    // Calculate high btw first substract the food (low btw) and split into parking ticket and ticket
    global $dbPriceSingleTicket;
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $dbTicketPricePart;
    global $dbTicketPricePartBtw;
    global $dbTicketPricePartTotal;
    global $dbBtwHigh;

    global $dbFoodPrice;
    $price_ticket_ex_food = $dbPriceSingleTicket - $dbFoodPrice;

    // If there is a reduction price which is smaller than the food price the price part high will be below zero
    if ($price_ticket_ex_food < 0) {
        $price_ticket_ex_food = 0;
    }

    global $dbParticipants;
    $dbTicketPricePart = $price_ticket_ex_food * count($dbParticipants);

    $dbTicketPricePartBtw = $dbTicketPricePart * $dbBtwHigh;
    $dbTicketPricePartTotal = $dbTicketPricePart + $dbTicketPricePartBtw;

    $dbPricePartHigh = $dbTicketPricePart + $dbParkingPricePart;
    $dbPricePartHighBtw = $dbPricePartHigh * $dbBtwHigh;

    global $dbPricePartHighTotal;
    $dbPricePartHighTotal = $dbPricePartHigh + $dbPricePartHighBtw;
    
    // Calculate low btw
    // When the reduction price is smaller than the food price take the reduction price
    global $dbPricePartLow;
    $dbPricePartLow = $dbFoodPrice;
    if ($dbTicketPrice < $dbFoodPrice) {
        $dbPricePartLow = $dbTicketPrice;
    }

    global $dbPricePartLowBtw;
    global $dbPricePartLowTotal;
    global $dbFoodPartPriceLow;
    global $dbFoodPartPriceLowBtw;
    global $dbFoodPartPriceLowTotal;

    global $dbBtwLow;

    $dbPricePartLow = count($dbParticipants) * $dbPricePartLow;
    $dbFoodPartPriceLow = $dbPricePartLow;
    $dbPricePartLowBtw = $dbPricePartLow * $dbBtwLow;
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
    global $eventId;
    global $eventName;
    global $dbSubmissionId;
    global $dbSubmissionDate;
    global $dbSubmissionOrganization;
    global $dbNumberParkingTickets;

    global $nameParticipant;
    global $emailParticipant;
    global $phoneParticipant;
    global $organization;
    global $nameInvoice;
    global $streetInvoice;
    global $zipcodeInvoice;
    global $cityInvoice;
    global $emailInvoice;
    global $firstNameInvoice;
    global $lastNameInvoice;
    global $reference;
    global $notes;
    global $dataSharing;

    global $dbBtwPaymentDetailEventNrHighBtw;
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

    $wpdb->insert($wpdb->prefix . 'special_events',
        array(
            'submission_id' => $dbSubmissionId,
            'submission_date' => $dbSubmissionDate,
            'event_id' => $eventId,
            'organization' => $organization,
            'price' => $dbTotalPrice,
            'tax' => $dbTotalBtw,
            'price_tax' => $dbTotalPriceBtw,
            'notes' => $notes,
            'event_name' => $eventName
        )
    );

    debug_to_consoleSpecialEvents('Inserted into: '. $wpdb->prefix . 'special_events' . ' submission with id: ' . $dbSubmissionId);
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
            'firstname' => $firstNameInvoice,
            'lastname' => $lastNameInvoice,
            'adress' => $streetInvoice,
            'zipcode' => $zipcodeInvoice,
            'city' => $cityInvoice,
            'event_nr' => $dbBtwPaymentDetailEventNrHighBtw,
            'btw_type' => $dbInvoiceBtwType,
            'email' => $emailInvoice,
            'extra_information' => $reference,
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
            //'parking_fee' => $dbParkingPricePart,
            //'parking_fee_btw' => $dbParkingPricePartBtw,
            //'parking_fee_total' => $dbParkingPricePartTotal,
            //'food_fee' => $dbFoodPartPriceLow,
            //'food_fee_btw' => $dbFoodPartPriceLowBtw,
            //'food_fee_total' => $dbFoodPartPriceLowTotal,
            //'total_low' => $dbPricePartLow,
            //'total_low_btw' => $dbPricePartLowBtw,
            //'total_low_total' => $dbPricePartLowTotal,
            'total_high' => $dbPricePartHigh,
            'total_high_btw' => $dbPricePartHighBtw,
            'total_high_total' => $dbPricePartHighTotal,
            'total' => $dbTotalPrice,
            'total_btw' => $dbTotalBtw,
            'total_total' => $dbTotalPriceBtw,
            //'membership_fee' => $dbMembershipPricePart,
            //'membership_fee_btw' => $dbMembershipPricePartBTW,
            //'membership_fee_total' => $dbMembershipPricePartTotal
        )
    );

    // First payment detail: insert the entree payment detail row (with btw high)
    global $dbPricePartHighTotal;
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $dbBtwHighNr;
    global $dbBtwPaymentDetailEventNrHighBtw;
    global $dbBtwPaymentDetailDescriptionHighBtw;

    $wpdb->insert($wpdb->prefix . 'special_events_crm_details',
        array(
            'submission_id' => $dbSubmissionId,
            'event' => $dbBtwPaymentDetailEventNrHighBtw,
            'price' => $dbPricePartHigh,
            'btw_type' => $dbBtwHighNr,
            'tax' => $dbPricePartHighBtw,
            'row_description' => $dbBtwPaymentDetailDescriptionHighBtw,
            'price_tax' => $dbPricePartHighTotal,
            'invoice_number' => $dbInvoiceNumber
        )
    );

    debug_to_consoleSpecialEvents('Inserted btw high in payment details table');

    // Second payment detail: insert the food payment detail row (with btw low)
  /*  global $dbPricePartLowBtw;
    global $dbPricePartLow;
    global $dbPricePartLowTotal;
    global $dbBtwLowNr;
    global $dbBtwPaymentDetailEventNrLowBtw;
    global $dbBtwPaymentDetailDescriptionLowBtw;
/*
    $wpdb->insert($wpdb->prefix . 'submission_crm_details',
        array(
            'submission_id' => $dbSubmissionId,
            'event' => $dbBtwPaymentDetailEventNrLowBtw,
            'price' => $dbPricePartLow,
            'btw_type' => $dbBtwLowNr,
            'tax' => $dbPricePartLowBtw,
            'row_description' => $dbBtwPaymentDetailDescriptionLowBtw,
            'price_tax' => $dbPricePartLowTotal,
            'invoice_number' => $dbInvoiceNumber
        )
    );
*/
    //debug_to_consoleSpecialEvents('Inserted btw low in payment details table');

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
}

function debug_to_consoleSpecialEvents($data)
{
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log( 'Debug php: " . $output . "' );</script>";
}

?>