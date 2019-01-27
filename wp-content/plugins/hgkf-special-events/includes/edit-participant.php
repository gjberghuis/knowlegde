<?php

function render_edit_Special_events_participant_page() {
    global $wpdb;

    if (isset($_GET['action']) && $_GET['action'] == 'edit_participant' && isset($_GET['id'])) {
        $participant = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}special_events_participants WHERE id = " . $_GET['id']);

        global $wpdb;

        if ($participant) {
            if (isset($_POST['save_participant'])) {
                if (!empty($_POST['name'])) {
                    $participant->name = $_POST['name'];
                }

                if (isset($_POST['parkingticket']) && $_POST['parkingticket'] == 'yes') {
                    $participant->parkingticket = 1;
                } else {
                    $participant->parkingticket = 0; 
                }

                if (!empty($_POST['email'])) {
                    $participant->email = $_POST['email'];
                }

                if (!empty($_POST['phone'])) {
                    $participant->phone = $_POST['phone'];
                }
        
                $resultParticipant = $wpdb->update($wpdb->prefix . 'special_events_participants',
                    array('name' => $participant->name,
                        'email' => $participant->email,
                        'phone' => $participant->phone,
                        'parkingticket' => $participant->parkingticket),
                    array('id' => $participant->id));

                if ($resultParticipant > 0) {
                    $path = 'admin.php?page=participants';
                    $url = admin_url($path);
                    wp_redirect($url);
                }
            }

            echo '</pre>';
            echo '<div class="wrap">';

            $path = 'admin.php?page=participants';

            if (isset($_GET['submission_id'])) {
                $path .= '&submission_id=' . $_GET['submission_id'];
            }

            $url = admin_url($path);
            echo '<a href="' . $url . '">< Terug naar het deelnemers overzicht</a>';
            echo '<h2>Deelnemer bewerken</h2>';
            echo '<br/>';
            echo '<form action="" method="post">';
            echo '<fieldset><legend>Niet te wijzigen:</legend>';
            echo '<table>';
                echo '<tr>';
                    echo '<td>';
                    echo '<label for="id" style="margin-right: 20px;">Id</label>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="int" name="id" disabled value="' . $participant->id . '"/>';
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    echo '<label for="submission_id" style="margin-right: 20px;">Submission id</label>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="int" name="submission_id" disabled value="' . $participant->submission_id . '"/>';
                    echo '</td>';
                echo '</tr>';
                echo '<tr><td><br/></td></tr></table>';
            echo '</fieldset>';
            echo '<fieldset><legend>Gegevens:</legend><table></tr>';
                echo '<tr>';
                    echo '<td>';
                    echo '<label for="name" style="margin-right: 20px;">Naam</label>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="text" style="width:300px;" name="name" value="' . $participant->name . '"/>';
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    echo '<label for="email" style="margin-right: 20px;">Email</label>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="int" name="email" value="' . $participant->email . '"/>';
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td>';
                    echo '<label for="phone" style="margin-right: 20px;">Telefoon</label>';
                    echo '</td>';
                    echo '<td>';
                    echo '<textarea type="text" style="width:300px;" name="phone">' .  $participant->phone . '</textarea>';
                    echo '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>';
                echo '<label for="parkingticket" style="margin-right: 20px;">Parkeer ticket</label>';
                echo '</td>';
                echo '<td>';
                $checked = false;
                if ($participant->parkingticket == 1) {
                    $checked = true;                
                }
                echo '<input type="checkbox" name="parkingticket" value="yes" '. ($checked ? checked : "") .'/>';
                echo '</td>';
            echo '</tr>';
                echo '<tr><td><br/></td></tr></table>';
            echo '</fieldset>';
            echo '<input type="submit" value="Bewerken" name="save_participant" />';
            echo '</form>';
        }
        else {
            $path = 'admin.php?page=participants';
            $url = admin_url($path);
            wp_redirect($url);
        }
    } else {
        $path = 'admin.php?page=participants';
        $url = admin_url($path);
        wp_redirect($url);
    }
}