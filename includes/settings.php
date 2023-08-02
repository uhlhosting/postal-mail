<?php

/**
 * File Name: settings.php for Postal Mail Plugin
 * Description: This file is responsible for creating and handling the settings page for the Postal Mail plugin.
 *              It includes functions to initialize the settings page, add it to the WordPress admin menu, display
 *              the settings page, and register the plugin options in the WordPress database.
 * Version: 1.0.1
 * Author: Viorel-Cosmin Miron
 * Author URI: https://uhlhosting.ch
 * Text Domain: postal-mail
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class PostalMailSettings
 * A class to handle the plugin's settings page
 */
class PostalMailSettings
{
    /**
     * Initialize the settings page
     */
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'addSettingsPage']);
        add_action('admin_init', [__CLASS__, 'registerSettings']);
    }

    /**
     * Add the settings page to the WordPress admin menu
     */
    public static function addSettingsPage()
    {
        add_options_page(
            'Postal Mail Settings',
            'Postal Mail',
            'manage_options',
            'postal_mail_settings',
            [__CLASS__, 'displaySettingsPage']
        );
    }

    /**
     * Display the settings page
     */
    public static function displaySettingsPage()
    {
        ?>
        <div class="wrap">
            <h2>
                <?php esc_html_e('Postal Mail Settings', 'postal-mail'); ?>
            </h2>
            <form method="post" action="options.php">
                <?php settings_fields('postal_mail_options'); ?>
                <table class="form-table">
                    <?php self::displayHostSetting(); ?>
                    <?php self::displaySecretKeySetting(); ?>
                    <?php self::displayFromAddressSetting(); ?>
                    <?php self::displayEmailSendingSetting(); ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <!-- Add the test email button here -->
            <button id="test_email_button" class="button button-primary">
                <?php esc_html_e('Send Test Email', 'postal-mail'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Display the host setting field
     */
    public static function displayHostSetting()
    {
        ?>
        <tr valign="top">
            <th scope="row">
                <?php esc_html_e('Postal API Host', 'postal-mail'); ?>
            </th>
            <td>
                <input type="text" name="postal_wp_host" value="<?php echo esc_attr(get_option('postal_wp_host')); ?>"
                    class="regular-text" />
            </td>
        </tr>
        <?php
    }

    /**
     * Display the secret key setting field
     */
    public static function displaySecretKeySetting()
    {
        ?>
        <tr valign="top">
            <th scope="row">
                <?php esc_html_e('Postal API Secret Key', 'postal-mail'); ?>
            </th>
            <td>
                <input type="text" name="postal_wp_secret_key"
                    value="<?php echo esc_attr(get_option('postal_wp_secret_key')); ?>" class="regular-text" />
            </td>
        </tr>
        <?php
    }

    /**
     * Display the from address setting field
     */
    public static function displayFromAddressSetting()
    {
        ?>
        <tr valign="top">
            <th scope="row">
                <?php esc_html_e('From Address', 'postal-mail'); ?>
            </th>
            <td>
                <input type="text" name="postal_wp_from_address"
                    value="<?php echo esc_attr(get_option('postal_wp_from_address')); ?>" class="regular-text" />
            </td>
        </tr>
        <?php
    }

    /**
     * Display the email sending setting field
     */
    public static function displayEmailSendingSetting()
    {
        ?>
        <tr valign="top">
            <th scope="row">
                <?php esc_html_e('Email Sending', 'postal-mail'); ?>
            </th>
            <td>
                <input type="checkbox" name="postal_wp_switch" value="1" <?php checked(1, get_option('postal_wp_switch')); ?> />
                <label for="postal_wp_switch">
                    <?php esc_html_e('Use Postal API for sending emails', 'postal-mail'); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Register the plugin options in the WordPress database
     */
    public static function registerSettings()
    {
        register_setting('postal_mail_options', 'postal_wp_host');
        register_setting('postal_mail_options', 'postal_wp_secret_key');
        register_setting('postal_mail_options', 'postal_wp_from_address');
        register_setting('postal_mail_options', 'postal_wp_switch');
    }
}

PostalMailSettings::init();