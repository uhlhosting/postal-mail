<?php

namespace PostalWp;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class PostalMailSettings
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'addSettingsPage']);
        add_action('admin_init', [self::class, 'registerAndSanitizeSettings']);
        add_action('admin_init', [self::class, 'addSettingsSectionsAndFields']);
    }

    public static function addSettingsPage()
    {
        add_options_page(
            __('Postal Mail Settings', 'postal-mail'),
            __('Postal Mail', 'postal-mail'),
            'manage_options',
            'postal_mail_settings',
            [self::class, 'displaySettingsPage']
        );
    }

    public static function registerAndSanitizeSettings()
    {
        register_setting('postal_mail_options', 'postal_wp_host', 'sanitize_text_field');
        register_setting('postal_mail_options', 'postal_wp_secret_key', 'sanitize_text_field');
        register_setting('postal_mail_options', 'postal_wp_from_address', 'sanitize_email');
        register_setting('postal_mail_options', 'postal_wp_switch', 'intval');
    }

    public static function addSettingsSectionsAndFields()
    {
        add_settings_section(
            'postal_mail_api_settings',
            __('API Settings', 'postal-mail'),
            [self::class, 'displayApiSettingsSection'],
            'postal_mail_settings'
        );

        add_settings_field(
            'postal_wp_host',
            __('Postal API Host', 'postal-mail'),
            [self::class, 'displayHostSettingField'],
            'postal_mail_settings',
            'postal_mail_api_settings'
        );

        add_settings_field(
            'postal_wp_secret_key',
            __('Postal API Secret Key', 'postal-mail'),
            [self::class, 'displaySecretKeySettingField'],
            'postal_mail_settings',
            'postal_mail_api_settings'
        );

        add_settings_field(
            'postal_wp_from_address',
            __('From Address', 'postal-mail'),
            [self::class, 'displayFromAddressSettingField'],
            'postal_mail_settings',
            'postal_mail_api_settings'
        );

        // Add the new Email Sending setting field
        add_settings_field(
            'postal_wp_switch',
            __('Email Sending', 'postal-mail'),
            [self::class, 'displayEmailSendingSettingField'],
            'postal_mail_settings',
            'postal_mail_api_settings'
        );
    }

    public static function displaySettingsPage()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap postal-mail-settings">
            <h2>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('postal_mail_options');
                do_settings_sections('postal_mail_settings');
                submit_button();
                ?>
            </form>
            <button id="test_email_button" class="button button-primary">
                <?php esc_html_e('Send Test Email', 'postal-mail'); ?>
            </button>
        </div>
        <?php
    }

    public static function displayApiSettingsSection()
    {
        echo '<p>' . esc_html__('Enter your Postal API settings below:', 'postal-mail') . '</p>';
    }

    public static function displayHostSettingField()
    {
        $value = get_option('postal_wp_host');
        echo "<input type='text' name='postal_wp_host' value='" . esc_attr($value) . "' />";
    }

    public static function displaySecretKeySettingField()
    {
        $value = get_option('postal_wp_secret_key');
        echo "<input type='text' name='postal_wp_secret_key' value='" . esc_attr($value) . "' />";
    }

    public static function displayFromAddressSettingField()
    {
        $value = get_option('postal_wp_from_address');
        echo "<input type='text' name='postal_wp_from_address' value='" . esc_attr($value) . "' />";
    }

    // Add the new Email Sending setting field function
    public static function displayEmailSendingSettingField()
    {
        $value = get_option('postal_wp_switch');
        echo "<input type='checkbox' name='postal_wp_switch' value='1'" . checked(1, $value, false) . " />";
    }
}

PostalMailSettings::init();
