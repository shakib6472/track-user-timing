<?php 

function sttss_create_admin_menu()
{
    add_menu_page(
        'Track Students',             // Page title
        'Track Students',             // Menu title
        'manage_options',             // Capability
        'student-time-tracker',       // Menu slug
        'sttss_display_student_table', // Callback function
        'dashicons-clock',            // Icon
        30                            // Position
    );

    add_submenu_page(
        'student-time-tracker',           // Parent menu slug (can be 'tools.php' or another menu slug)
        'Track By Student',    // Page title
        'Track By Student',    // Menu title
        'manage_options',      // Required capability (adjust as necessary)
        'student_tracking',    // Submenu slug (used in the URL)
        'student_tracking_page_content' // Callback function to display content
    );


}
add_action('admin_menu', 'sttss_create_admin_menu');



function convert_seconds_to_hms($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

function sttss_display_student_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'student_time_tracker';

    // Fetch all students' data
    $students = $wpdb->get_results("SELECT * FROM $table_name");

    // Enqueue the DataTables CSS and JS files
    wp_enqueue_style('sttss-datatables-style', 'https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css');
    wp_enqueue_script('sttss-datatables-script', 'https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js', array('jquery'), null, true);
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Student Time Tracker</h1>

        <!-- Start Table -->
        <table id="studentTimeTable" class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Student ID</th>
                    <th scope="col" class="manage-column">Total Time</th>
                    <th scope="col" class="manage-column">Today's Time</th>
                    <th scope="col" class="manage-column">Last Login</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)) : ?>
                    <?php foreach ($students as $student) : 
                        // Convert total time from seconds to hh:mm:ss
                        $total_time_formatted = convert_seconds_to_hms($student->total_time);
                        
                        // Decode the JSON data for today's time
                        $time_spent_by_date = json_decode($student->time_spent_by_date, true);
                        $today = date('Y-m-d'); 
                        $todays_time = isset($time_spent_by_date[$today]) ? $time_spent_by_date[$today] : 0;
                        $todays_time_formatted = convert_seconds_to_hms($todays_time);
                    ?>
                        <tr>
                            <td><?php echo esc_html($student->user_id); ?></td>
                            <td><?php echo esc_html($total_time_formatted); ?></td>
                            <td><?php echo esc_html($todays_time_formatted); ?></td>
                            <td><?php echo esc_html($student->last_login); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=student_tracking&user=' . $student->user_id); ?>" class="button">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">No students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- End Table -->
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize DataTables on the student table
            $('#studentTimeTable').DataTable();
        });
    </script>
<?php
}


function student_tracking_page_content() {
    // Step 1: Check if a user ID is provided in the URL
    $user_id = isset($_GET['user']) ? intval($_GET['user']) : 0; // Default to 0 if no user is selected
    
    // Step 2: If no user ID, show a dropdown to select a student
    if (!$user_id) {
        // Fetch all students' data for the dropdown
        global $wpdb;
        $table_name = $wpdb->prefix . 'student_time_tracker';
        $students = $wpdb->get_results("SELECT * FROM $table_name");

        echo '<div class="wrap">';
        echo '<h1>Track By Student</h1>';
        echo '<p>Select a student to view tracking details.</p>';

        // Correct the form action to dynamically get the current admin URL
        echo '<form method="GET" action="' . admin_url('admin.php') . '">';
        echo '<input type="hidden" name="page" value="student_tracking" />'; 
        echo '<select name="user" id="user" class="postform">';
        
        // Populate the dropdown with student names
        foreach ($students as $student) {
            $user = get_user_by('id', $student->user_id);
            echo '<option value="' . esc_attr($student->user_id) . '" ' . selected($user_id, $student->user_id, false) . '>';
            echo esc_html($user ? $user->user_login : 'Unknown');
            echo '</option>';
        }

        echo '</select>';
        echo '<input type="submit" value="View Tracking" class="button button-primary" />';
        echo '</form>';
        echo '</div>';

        return; // Exit the function here so the rest of the code doesn't run when the user selection form is shown
    }

    // Step 3: If a user ID is provided, fetch and display the tracking data for that student
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker';
    
    // Fetch the specific student data based on the user ID
    $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    if ($student) {
        $total_time = convert_seconds_to_hms($student->total_time);
        $time_spent_by_date = json_decode($student->time_spent_by_date, true);
        $today = date('Y-m-d');
        $todays_time = isset($time_spent_by_date[$today]) ? $time_spent_by_date[$today] : 0;
        $todays_time_formatted = convert_seconds_to_hms($todays_time);
        $user = get_user_by('id', $student->user_id);
    ?>

    <div class="wrap">
        <h1>Track By Student</h1>



        <style>
            .skb-container {
                /* background-color: #06445a; */
                /* height: 100vh; */
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                
            }

            .skb-container .skb-modal {
                background-color: #ededed;
                height: 600px;
                width: 90%;
                border-radius: 13px;
                position: relative;
            }

            .skb-container .skb-modal .close {
                background-color: #cc0c00;
                font-size: 30px;
                height: 50px;
                width: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                position: absolute;
                top: -15px;
                right: -15px;
                border-radius: 5443px;
            }

            .skb-container .skb-modal .main-modal {
                padding: 40px;
            }

            .skb-container .skb-modal .main-modal .modal-header {
                font-size: 21px;
            }

            .skb-container .skb-modal .main-modal .modal-footer {
                margin-top: 12px;
                border-top: 3px solid;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 10px;
            }

            .skb-container .skb-modal .main-modal .modal-body {
                margin-top: 13px;
            }

            .skb-container .skb-modal .main-modal .modal-body .items {
                display: flex;
                gap: 20px;
            }

            .skb-container .skb-modal .main-modal .modal-body .items .item {
                border: 3px solid #d0d0d0;
                padding: 10px 20px;
                border-radius: 8px;
            }

            .skb-container .skb-modal .main-modal .modal-body .table {
                margin-top: 15px;
                width: 100%;
            }

            .skb-container .skb-modal .main-modal .modal-body .table table {
                margin-top: 15px;
                width: 100%;
            }

            .skb-container .skb-modal .main-modal .modal-body .table tr {
                border: 2px solid #b8b8b8;
                text-align: left;
            }

            .skb-container .skb-modal .main-modal .modal-body .table tr th {
                font-size: 21px;
                font-weight: bold;
                border: 2px solid #b8b8b8;
                padding: 6px;
            }

            .skb-container .skb-modal .main-modal .modal-body .table tr td {
                font-size: 16px;
                border: 2px solid #b8b8b8;
                font-weight: 400;
                padding: 6px;
            }

            .scrollber {
                overflow-y: scroll;
                max-height: 450px;
                height: 450px;
            }

            .floating-btn {
                background-color: #cc0c00;
                padding: 15px;
                width: 180px;
                border-radius: 8px;
                color: #fff;
                position: fixed;
                bottom: 30px;
                left: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
<?php 
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_time_tracker';
$user_id = get_current_user_id();
$user = get_user_by('id', $user_id);
$student = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
$total_time_formatted = convert_seconds_to_hms($student->total_time);
?>
        <div class="skb-container">
            <div class="skb-modal">
                <!-- <div class="close">
                    <span>X</span>
                </div> -->
                <div class="main-modal">
                    <div class="modal-header">
                        <h3>Detailed Timing about , <strong><?php echo  esc_html($user ? $user->user_login : 'Unknown'); ?> </strong></h3>
                    </div>
                    <div class="modal-body scrollber">
                        <div class="items">
                            <div class="item">
                                Total Time Logs: <span> <?php echo $total_time_formatted ?></span>
                            </div>
                            <div class="item">
                                Last Login: <span><?php echo esc_html($student->last_login); ?></span>
                            </div>
                        </div>
                        <div class="table">
                            <h2>Check Your Daily Times</h2>
                            <table>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Time</th>
                                </tr>

                                <?php 
                                 $time_spent_by_date = json_decode($student->time_spent_by_date, true);
                                 foreach ($time_spent_by_date as $date => $seconds) : ?> 
                                    <tr>
                                    <td><?php echo esc_html($date); ?></td>
                                    <td><?php echo esc_html(convert_seconds_to_hms($seconds)); ?></td>
                                </tr>


                                <?php endforeach;
                                ?>
                               
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">Thanks For staying with us</div>
                </div>
            </div>
        </div>
        
    </div>
    
    <?php
    } else {
        echo '<div class="wrap"><p>No data found for this student.</p></div>';
    }
}
