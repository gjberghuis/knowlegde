<?php

// SETTINGS
const form_id = 3;

// GLOBAL FIELDS
$dbSubmissionType;
$dbSubmissionId;
$dbSubmissionDate;
$dbSubmissionOrganization;
$dbNumberParkingTickets;
$dbReductionCode;
$dbMembershipIsEnabled;
$dbNotes;

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
* Use the gravity hook to process the data to our own tables
*/
add_action('gform_entry_created', 'processGravitySpecialEventsData', 10, form_id);
function processGravitySpecialEventsData($entry, $form)
{
    processSpecialEventsSettings();

    global $dbSubmissionType;
    if (form_id == 3) {

        if ($form['title'] == 'Aanmelden - 2019 Individueel') {
            $dbSubmissionType = 'individu';
        } else if ($form['title'] == 'Aanmelden - 2019 Groepsaanmelding') {
            $dbSubmissionType = 'groep';
        }

        // Check if the entry is already present in the database
        global $wpdb;
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}submissions WHERE submission_id = '" . $entry['id'] . "'");

        if ($exists > 1) {
            debug_to_consoleSpecialEvents("Entry already exists");
            return;
        }

        debug_to_consoleSpecialEvents("Adding new entry with id: " . $entry['id']);

        processSpecialEvents($entry);
        if ($dbSubmissionType === 'geen') {
            return;
        }

        debug_to_consoleSpecialEvents("Processing submission completed");

        processInvoiceSpecialEventsNAWFields($entry);
        debug_to_consoleSpecialEvents("Processing NAW completed");

        processParticipantsSpecialEvents($entry, $form);
        debug_to_consoleSpecialEvents("Processing Participants completed");

        processInvoiceSpecialEventsPaymentFields($entry);
        debug_to_consoleSpecialEvents("Processing Invoice Payment completed");

        processPaymentFieldsSpecialEvents($entry);
        debug_to_consoleSpecialEvents("Processing Payment completed");

        processFreeFieldsSpecialEvents($entry, $form);
        debug_to_consoleSpecialEvents("Processing Free fields completed");

        saveSubmissionSpecialEvents();
        debug_to_consoleSpecialEvents("Saving entry completed");
    }
}

/*
 * Process functions
 */
function processSpecialEventsSettings() {
    global $price_membership;
    $price_membership = 165;
    global $price_single_ticket;
    $price_single_ticket = 195;
    global $price_group_ticket;
    $price_group_ticket = 175;
    global $price_parking_ticket;
    $price_parking_ticket = 10;
    global $btw_low_nr;
    $btw_low_nr = '1';
    global $btw_low;
    $btw_low = 0.06;
    global $btw_high_nr;
    $btw_high_nr = '2';
    global $btw_high;
    $btw_high = 0.21;
    global $food_price;
    $food_price = 19.25;
    global $payment_detail_description_low_btw;
    $payment_detail_description_low_btw = 'Vertering Het Grootste Kennisfestival';
    global $payment_detail_description_high_btw;
    $payment_detail_description_high_btw = 'Deelname Het Grootste Kennisfestival';
    global $payment_detail_event_nr_low_btw;
    $payment_detail_event_nr_low_btw = '8030';
    global $payment_detail_event_nr_high_btw;
    $payment_detail_event_nr_high_btw = '8000';

    global $dbInvoiceExpirationDays;
    $dbInvoiceExpirationDays = 14;
    global $dbInvoiceDescriptionText;
    $dbInvoiceDescriptionText = "Wij verzoeken je vriendelijk dit bedrag binnen 14 dagen over te maken naar de Rabobank op rekeningnummer NL93RABO0300479743 ten name van Regio Academy BV onder vermelding van het factuurnummer. Mocht je vragen hebben naar aanleiding van deze factuur dan kan je een mail sturen naar administratie@regioacademy.nl. Dan nemen we zo snel mogelijk contact met je op.";

    global $wpdb;
    $settings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}special_events_settings  where preset = 1");

    if($settings && $settings[0]) {
		$price_membership =  $settings[0]->member_price_single;
        $price_single_ticket = $settings[0]->ticket_price_single;
        $price_group_ticket = $settings[0]->ticket_price_group;
        $price_parking_ticket = $settings[0]->price_parkingticket;
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

    // Submission type
    global $dbSubmissionType;

  /*  if ($entry['50']) {
        if (strpos(strtolower($entry['50']), 'individu') !== false) {
            $dbSubmissionType = 'individu';
        } else if (strpos(strtolower($entry['50']), 'groep') !== false) {
            $dbSubmissionType = 'groep';
        } else {
            $dbSubmissionType = 'geen';
        }
    }*/
	
    global $dbSubmissionDate;
    $dbSubmissionDate = date("Y-m-d H:i:s");

    global $dbSubmissionOrganization;
    $dbSubmissionOrganization = $entry['16'];

    global $dbNumberParkingTickets;
    
    global $dbReductionCode;
    if (!empty($entry[21])) {
        $reductionCode = $entry[21];
        $reductionCode = str_replace('"', "", $reductionCode);
        $reductionCode = str_replace("'", "", $reductionCode);
        $dbReductionCode = strtolower(trim($reductionCode));
    } else {
        $dbReductionCode = '';
    }

    global $dbMembershipIsEnabled;
    $dbMembershipIsEnabled = false;
    if ($dbSubmissionType == 'groep') {
        if (!empty($entry[47]) && $entry[47] == 'Ja') {
            $dbMembershipIsEnabled = true;
        }
    } else if ($dbSubmissionType == 'individu') {
        if (!empty($entry[45]) && $entry[45] == 'Ja') {
            $dbMembershipIsEnabled = true;
        }
    }

    global $dbNotes;
    $dbNotes = $entry['23'];

    global $dbExpirationDate;
    global $dbInvoiceExpirationDays;
    $dbExpirationDate = date('Y-m-d H:i:s', strtotime("+" . $dbInvoiceExpirationDays . " days"));
}

function processInvoiceSpecialEventsNAWFields($entry)
{
    global $dbInvoiceFirstName;
    $dbInvoiceFirstName = $entry['17.3'];

    global $dbInvoiceLastName;
    $dbInvoiceLastName = $entry['17.6'];

    global $dbInvoiceAdress;
    $dbInvoiceAdress = $entry['18.1'];

    global $dbInvoiceZipcode;
    $dbInvoiceZipcode = $entry['18.3'];

    global $dbInvoiceCity;
    $dbInvoiceCity = $entry['18.5'];

    global $dbInvoiceEmail;
    $dbInvoiceEmail = $entry['19'];

    global $dbInvoiceExtraInformation;
    $dbInvoiceExtraInformation = $entry['20'];

    global $dbInvoiceEventNr;
    $dbInvoiceEventNr = '8000';
}

function processInvoiceSpecialEventsPaymentFields($entry)
{
    global $wpdb;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}submissions");

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

function processParticipantsSpecialEvents($entry, $form) {
    global $dbParticipants;
    global $dbNumberParkingTickets;
    $dbNumberParkingTickets = 0;

    $dbParticipants = [];
    // The main participant is the non repeater participant
    $mainParticipant = [];

    if (!empty($entry['15.3']) && !empty($entry['15.6'])) {
        $mainParticipant['Naam'] = $entry['15.3'] . " " . $entry['15.6'];
    }
    if (!empty($entry[13])) {
        $mainParticipant['E-mailadres'] = $entry[13];
    }

    if (!empty($entry[40])) {
        $mainParticipant['Telefoon'] = $entry[40];
    }

    if (!empty($entry[44])) {
        $mainParticipant['Parkeerticket (Kosten 10 euro per ticket) *'] = $entry[44];
        if ($entry[44] == 'Ja') {
            $dbNumberParkingTickets++;
        }
    }
   
    $dbParticipants[] = $mainParticipant;

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
    }
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
 
    // Calculate parking costs and add to the ticketprice
    global $dbNumberParkingTickets;
    // Calculate high btw first substract the food (low btw) and split into parking ticket and ticket
    global $dbPricePartHigh;
    global $dbPricePartHighBtw;
    global $dbTicketPricePart;
    global $dbTicketPricePartBtw;
    global $dbTicketPricePartTotal;
    global $dbParkingPricePart;
    global $dbParkingPricePartBtw;
    global $dbParkingPricePartTotal;
    global $price_parking_ticket;
    global $btw_high;
    global $dbMembershipPricePart;
    global $dbMembershipPricePartBTW;
    global $dbMembershipPricePartTotal;
    global $price_membership;
    global $dbMembershipIsEnabled;

    $dbParkingPricePart = 0;
    if ($dbNumberParkingTickets > 0) {
        if ($freeParkingTicket ) {
            $price_parking_ticket = 0;
        }

        // Parking costs and btw
        $dbParkingPricePart = ($dbNumberParkingTickets * $price_parking_ticket);
        $dbParkingPricePartBtw = $dbParkingPricePart  * $btw_high;
        $dbParkingPricePartTotal = $dbParkingPricePart  + $dbParkingPricePartBtw;
    } else {
        $dbParkingPricePart = 0;
        $dbParkingPricePartBtw = 0;
        $dbParkingPricePartTotal = 0;
    }

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

    if ($dbMembershipIsEnabled) {
        $dbMembershipPricePart = $price_membership;
        $dbMembershipPricePartBTW = $dbMembershipPricePart * $btw_high;
        $dbMembershipPricePartTotal = $dbMembershipPricePart + $dbMembershipPricePartBTW;
    } else {
        $dbMembershipPricePart = 0;
        $dbMembershipPricePartBTW = 0;
        $dbMembershipPricePartTotal = 0;
    }

    $dbPricePartHigh = $dbTicketPricePart + $dbParkingPricePart + $dbMembershipPricePart;
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