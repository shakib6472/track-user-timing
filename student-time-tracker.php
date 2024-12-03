<?php
/*
 * Plugin Name:      Student Time Tracker
 * Plugin URI:        https://github.com/shakib6472/
 * Description:       Student time tracker by Shakib Shown
 * Version:           1.2.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Shakib Shown
 * Author URI:        https://github.com/shakib6472/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sttss 
 * Domain Path:       /languages
 */

// Prevent direct access to the file
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/frontend.php';
require_once __DIR__ . '/ajax.php';
require_once __DIR__ . '/admin.php';

// Define the plugin version
define('STTSS_VERSION', '1.0.0');

// Hook to activate the plugin
register_activation_hook(__FILE__, 'sttss_create_custom_table');

// Create custom table on plugin activation
function sttss_create_custom_table()
{
    global $wpdb;

    // Define table name with WordPress prefix
    $table_name = $wpdb->prefix . 'student_time_tracker';
    $lesson_table_name = $wpdb->prefix . 'student_time_tracker_by_lesson';
    // SQL to create the custom table
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        total_time INT(11) DEFAULT 0,
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        time_spent_by_date TEXT,
        time_spent_by_last_login INT(11) DEFAULT 0,
        login_history TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $lesson_sql = "CREATE TABLE $lesson_table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        lesson_id BIGINT(20) UNSIGNED NOT NULL,
        total_time INT(11) DEFAULT 0 
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // Execute the query to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($lesson_sql);

    // Here I need a function. 
    // Get all post type lp_lesson & for each student (user) add a row for $lesson_sql this table. 
    // valuse total time will be 0
    // can you do that? 


    // Get all 'lp_lesson' posts
    $lessons = get_posts(array(
        'post_type' => 'lp_lesson',
        'posts_per_page' => -1,  // Get all lessons
    ));

    // Get all users (students)
    $users = get_users(array(
        'fields' => 'ID',  // Only fetch user IDs
    ));


    // Loop through each user
    foreach ($users as $user_id) {
        // Loop through each lesson and insert a row for the user
        foreach ($lessons as $lesson) {
            // Insert a new row into the 'student_time_tracker_by_lesson' table
            $wpdb->insert(
                $wpdb->prefix . 'student_time_tracker_by_lesson',
                array(
                    'user_id' => $user_id,
                    'lesson_id' => $lesson->ID,
                    'total_time' => 0,  // Set initial total time to 0
                ),
                array(
                    '%d',  // user_id as integer
                    '%d',  // lesson_id as integer
                    '%d',  // total_time as integer
                )
            );
        }
    }
}



function check_the_lesson_timing()
{ 
    if (is_singular('lp_course')) { 
        $lesson_Id = get_the_ID(); 
?>
        <script>
            jQuery(document).ready(function($) {
                lesson_id = $('form.learn-press-form.form-button input[name="id"]').val(); 
                $('form.learn-press-form.form-button').hide(); // Hide form initially

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>', // WordPress AJAX URL
                    data: {
                        action: 'get_spent_time_by_lesson', // Action hook to handle the AJAX request in your functions.php
                        lesson_id: lesson_id, // Pass lesson ID to the backend
                    },
                    dataType: 'json',
                    success: function(response) {
                        let mintime = 2 * 60 * 60; // 2 hours in seconds (7200 seconds)
                        let totaltime = response.data; // Time in seconds returned from the backend

                        // Check if the total time has exceeded 2 hours
                        if (totaltime < mintime) {
                            // Total time is less than 2 hours, show countdown
                            let remainingTime = mintime - totaltime; // Remaining time to reach 2 hours
                            // Create and append the countdown element before the form
                            $('<h4> Complete Lesson Button will be available after </h4> <h3 id="countdown"></h3>').insertBefore('form.learn-press-form.form-button');
                            // Show countdown clock
                            let countdown = setInterval(function() {
                                let hours = Math.floor(remainingTime / 3600);
                                let minutes = Math.floor((remainingTime % 3600) / 60);
                                let seconds = remainingTime % 60;

                                // Display the countdown timer
                                $('#countdown').text(hours + "h " + minutes + "m " + seconds + "s");

                                // Decrease remaining time by 1 second
                                remainingTime--;

                                // If time reaches 0, hide the countdown and show the button
                                if (remainingTime < 0) {
                                    clearInterval(countdown);
                                    $('form.learn-press-form.form-button').show(); // Show form button
                                }

                                // Update the backend every 2 seconds to track time
                                $.ajax({
                                    type: 'POST',
                                    url: '<?php echo admin_url('admin-ajax.php'); ?>', // WordPress AJAX URL
                                    data: {
                                        action: 'add_each_seconds_to_lesson_database', // Action hook to handle the AJAX request
                                        lesson_id: lesson_id, // Pass lesson ID
                                    },
                                    dataType: 'json',
                                    success: function(response) { 
                                    },
                                    error: function(xhr, textStatus, errorThrown) { 
                                    }
                                });
                            }, 1000); // Update countdown every second
                        } else {
                            // If total time is 2 hours or more, hide the button
                            $('form.learn-press-form.form-button').hide();
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) { 
                    }
                });
            });
        </script>

<?php

    }
}

add_action('wp_footer', 'check_the_lesson_timing');
