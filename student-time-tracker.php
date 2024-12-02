<?php
/*
 * Plugin Name:      Student Time Tracker
 * Plugin URI:        https://github.com/shakib6472/
 * Description:       Student time tracker by Shakib Shown
 * Version:           1.0.0
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

    // Execute the query to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue the plugin styles and scripts
function sttss_enqueue_scripts()
{
    wp_enqueue_style('sttss-style', plugins_url('/assets/css/style.css', __FILE__));
    wp_enqueue_script('sttss-script', plugins_url('/assets/js/script.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'sttss_enqueue_scripts');

// Create admin menu for tracking students





