<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Unload scripts and styles
wp_dequeue_script('postal-mail-js');
wp_dequeue_style('postal-mail-css');

// Remove options from the database
delete_option('postal_wp_host');
delete_option('postal_wp_secret_key');
delete_option('postal_wp_from_address');
delete_option('postal_wp_switch');

// Drop the custom database table created by the plugin
global $wpdb;
$table_name = $wpdb->prefix . 'postal';
$wpdb->query("DROP TABLE IF EXISTS $table_name");