<?php
function add_each_seconds_to_database()
{
    // Step 1: Get the current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID; 

    if (!$user_id) {
        wp_send_json_error('No user logged in or invalid user.');
        return; // Stop further execution
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker';

    // Step 2: Time to be added (5 seconds)
    $time_spent_seconds = 2; // Store time in seconds 

    // Step 3: Check if the record already exists
    $student_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if ($student_record) {

        // Step 4: Update the record
        $total_time = $student_record->total_time + $time_spent_seconds; // Update total time in seconds
        $time_spent_by_date = json_decode($student_record->time_spent_by_date, true);
        $current_date = date('Y-m-d'); // Current date    

        $time_spent_by_date[$current_date] = isset($time_spent_by_date[$current_date]) ? $time_spent_by_date[$current_date] + $time_spent_seconds : $time_spent_seconds;


        // Update the record in the database
        $update_result = $wpdb->update(
            $table_name,
            array(
                'total_time' => $total_time,
                'time_spent_by_date' => json_encode($time_spent_by_date),
                'last_login' => current_time('mysql'),
                'login_history' => json_encode(array_merge(json_decode($student_record->login_history, true), [current_time('mysql')])),
            ),
            array('user_id' => $user_id)
        );
    } else {

        // Step 5: Create a new record for the user
        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'total_time' => $time_spent_seconds, // Store the time in seconds
                'last_login' => current_time('mysql'),
                'time_spent_by_date' => json_encode([date('Y-m-d') => $time_spent_seconds]),
                'login_history' => json_encode([current_time('mysql')])
            )
        );
    }

    // Step 6: Send JSON success response
    wp_send_json_success('Data stored for user ID: ' . $total_time);
}

add_action('wp_ajax_add_each_seconds_to_database', 'add_each_seconds_to_database');
add_action('wp_ajax_nopriv_add_each_seconds_to_database', 'add_each_seconds_to_database');


function get_spent_time_by_lesson()
{
    // Step 1: Get the current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $lesson_id = $_POST['lesson_id'];
    if (!$user_id) {
        wp_send_json_error('No user logged in or invalid user.');
        return; // Stop further execution
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker_by_lesson';
    $student_record = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        )
    );


    // Step 6: Send JSON success response
    wp_send_json_success($student_record->total_time);
}

add_action('wp_ajax_get_spent_time_by_lesson', 'get_spent_time_by_lesson');
add_action('wp_ajax_nopriv_get_spent_time_by_lesson', 'get_spent_time_by_lesson');



function add_each_seconds_to_lesson_database()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker_by_lesson';

    // Assuming the time spent comes from the frontend (e.g., via AJAX)
    $time_spent_seconds = 1; // Default to 1 second if not passed via AJAX (could be adjusted)
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $lesson_id = isset($_POST['lesson_id']) ? $_POST['lesson_id'] : 0;  // Ensure 'lesson_id' is passed

    // Validate 'lesson_id' to prevent SQL injection and errors
    if (!$lesson_id) {
        wp_send_json_error(array('message' => 'Invalid lesson ID'));
        return;
    }

    // Step 3: Check if the record already exists
    $student_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND lesson_id = %d",
        $user_id,
        $lesson_id
    ));

    if ($student_record) {
        // Step 4: Update the record
        $total_time = $student_record->total_time + $time_spent_seconds; // Update total time in seconds  

        // Update the record in the database
        $update_result = $wpdb->update(
            $table_name,
            array('total_time' => $total_time),
            array('user_id' => $user_id, 'lesson_id' => $lesson_id)
        );

        if ($update_result === false) {
            wp_send_json_error(array('message' => 'Failed to update time.'));
            return;
        }
    } else {
        // Step 5: Create a new record for the user
        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'lesson_id' => $lesson_id,
                'total_time' => $time_spent_seconds
            )
        );

        if ($insert_result === false) {
            wp_send_json_error(array('message' => 'Failed to insert new time record.'));
            return;
        }
    }

    // Step 6: Send JSON success response
    wp_send_json_success(array('message' => 'Data stored for user ID: ' . $user_id));
}

add_action('wp_ajax_add_each_seconds_to_lesson_database', 'add_each_seconds_to_lesson_database');
add_action('wp_ajax_nopriv_add_each_seconds_to_lesson_database', 'add_each_seconds_to_lesson_database');
