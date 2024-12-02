<?php
function log_current_user_in_footer()
{
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        error_log('Current User ID: ' . $current_user->ID);
        error_log('Current User Name: ' . $current_user->user_login);
        $user_id = get_current_user_id();
?>
        <!-- Send ajax data for each second -->
        <script>
            jQuery(document).ready(function($) {
                // Repeat it every 5 seconds
                setInterval(function() {
                    $.ajax({
                        type: 'POST',
                        url: <?php echo json_encode(admin_url('admin-ajax.php')); ?>, // WordPress AJAX URL
                        data: {
                            action: 'add_each_seconds_to_database', // Action hook to handle the AJAX request in functions.php
                            userid: <?php echo $user_id; ?>, // Pass the user ID
                        },
                        dataType: 'json',
                        success: function(response) {
                            // Handle success response
                            console.log(response);
                        },
                        error: function(xhr, textStatus, errorThrown) {
                            // Handle error
                            console.error('Error:', errorThrown);
                        }
                    });
                }, 2000); // Send AJAX request every 5 seconds
            });
        </script>


        <!-- Popup for students -->

        <style>
            .skb-container {
                background-color: #06445a;
                height: 100vh;
                width: 100%;
                display: none;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 0;
                left: 0;
            }

            .skb-container .skb-modal {
                background-color: #ededed;
                height: 600px;
                width: 60%;
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
                <div class="close">
                    <span>X</span>
                </div>
                <div class="main-modal">
                    <div class="modal-header">
                        <h3>Detailed Timing about you, <strong><?php echo  esc_html($user ? $user->user_login : 'Unknown'); ?> </strong></h3>
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
                                     
                                        <td></td>
                                        <td></td> 
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

        <div class="floating-btn">
            <div class="sbtn">View My times</div>
        </div>

        <script
            src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>
        <script>
            jQuery(document).ready(function($) {
                $(".skb-container .close").click(function() {
                    $(".skb-container").css("display", "none");
                    console.log("Closed");
                });
                $(".floating-btn").click(function() {
                    console.log("Open");
                    $(".skb-container").css("display", "flex");
                });
            });
        </script>







<?php
    } else {
        error_log('No user is logged in.');
    }
}
add_action('wp_footer', 'log_current_user_in_footer');
