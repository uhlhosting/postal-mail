<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Unload scripts and styles
wp_dequeue_script('postal-mail-script');
wp_dequeue_style('postal-mail-style');

// Remove options from the database
delete_option('postal_wp_host');
delete_option('postal_wp_secret_key');
delete_option('postal_wp_from_address');
delete_option('postal_wp_switch');