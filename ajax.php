<?php
function add_each_seconds_to_database()
{
    // Step 1: Get the current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Log the current user ID to track which user is being processed
    error_log('Storing each second for user ID: ' . $user_id);

    if (!$user_id) {
        // Log and exit if no user is found
        error_log('No user is logged in or the user ID is invalid.');
        wp_send_json_error('No user logged in or invalid user.');
        return; // Stop further execution
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker';

    // Log the table name to ensure it's the right one
    error_log('Using table: ' . $table_name);

    // Step 2: Time to be added (5 seconds)
    $time_spent_seconds = 2; // Store time in seconds
    error_log('Time spent (seconds) in this update: ' . $time_spent_seconds);

    // Step 3: Check if the record already exists
    $student_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if ($student_record) {
        // Log: Found the existing record
        error_log('Found existing record for user ID: ' . $user_id);

        // Step 4: Update the record
        $total_time = $student_record->total_time + $time_spent_seconds; // Update total time in seconds
        $time_spent_by_date = json_decode($student_record->time_spent_by_date, true);
        $current_date = date('Y-m-d'); // Current date  
        error_log('Current date: ' . $current_date); 
        // Log current time spent for the day
        error_log('Current date: ' . $current_date);
        error_log('Current time spent today (seconds): ' . (isset($time_spent_by_date[$current_date]) ? $time_spent_by_date[$current_date] : 0));

        $time_spent_by_date[$current_date] = isset($time_spent_by_date[$current_date]) ? $time_spent_by_date[$current_date] + $time_spent_seconds : $time_spent_seconds;

        // Log updated time data
        error_log('Updated time spent for today (seconds): ' . $time_spent_by_date[$current_date]);

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

        if ($update_result === false) {
            error_log('Error updating the record for user ID: ' . $user_id);
        } else {
            error_log('Successfully updated the record for user ID: ' . $user_id);
        }
    } else {
        // Log: No existing record, creating a new one
        error_log('No existing record found for user ID: ' . $user_id);

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

        if ($insert_result === false) {
            error_log('Error inserting the new record for user ID: ' . $user_id);
        } else {
            error_log('Successfully inserted new record for user ID: ' . $user_id);
        }
    }

    // Step 6: Send JSON success response
    wp_send_json_success('Data stored for user ID: ' . $user_id);
}

add_action('wp_ajax_add_each_seconds_to_database', 'add_each_seconds_to_database');
add_action('wp_ajax_nopriv_add_each_seconds_to_database', 'add_each_seconds_to_database');
