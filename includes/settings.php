<?php
/**
 * Postal Mail
 *
 * @package           UHLPostalMail
 * @subpackage        PostalSettings
 * @license           GPL-2.0-or-later
 */

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

    public static function getLastFiveEmails()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'postal';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 5");

        $output = '<div><h2>Last 5 Emails</h2><ul>';
        foreach ($results as $row) {
            $output .= '<li>Email: ' . $row->email . ', Message ID: ' . $row->message_id
                     . ', Sent at: ' . $row->time . '</li>';
        }
        $output .= '</ul></div>';

        return $output;
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
        <div class="wrap postal-mail-settings" role="main">
            <h2 id="postal_mail_settings_title">
                <?php echo esc_html(get_admin_page_title()); ?>
            </h2>
            <form action="options.php" method="post" aria-labelledby="postal_mail_settings_title">
                <div class="settings-grid">
                    <div class="left-column">
                        <?php
                        settings_fields('postal_mail_options');
                        do_settings_sections('postal_mail_settings');
                        submit_button();
                        ?>
                    </div>
                    <div class="right-column">
                        <button id="test_email_button" class="button button-primary"
                                aria-label="<?php esc_html_e('Send Test Email', 'postal-mail'); ?>">
                            <?php esc_html_e('Send Test Email', 'postal-mail'); ?>
                        </button>
                        <div class="last-emails">
                            <?php echo self::getLastFiveEmails(); ?>
                        </div>
                        <div class="help-section">
                            <?php self::displayHelpSection(); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    public static function displayApiSettingsSection()
    {
        echo '<div class="api-settings-text" role="region" aria-label="API Settings">'
           . esc_html__('Enter your Postal API settings below:', 'postal-mail')
           . '</div>';
    }

    public static function displayHostSettingField()
    {
        $value = get_option('postal_wp_host');
            echo "<input type='text' name='postal_wp_host' value='" . esc_attr($value)
            . "' aria-label='Postal API Host' />";
    }

    public static function displaySecretKeySettingField()
    {
        $value = get_option('postal_wp_secret_key');
        echo "<input type='text' name='postal_wp_secret_key' value='" . esc_attr($value)
           . "' aria-label='Postal API Secret Key' />";
    }

    public static function displayFromAddressSettingField()
    {
        $value = get_option('postal_wp_from_address');
        echo "<input type='text' name='postal_wp_from_address' value='" . esc_attr($value)
           . "' aria-label='From Address' />";
    }

    public static function displayEmailSendingSettingField()
    {
        $value = get_option('postal_wp_switch');
        echo "<div class='checkbox-wrapper'><input type='checkbox' name='postal_wp_switch' value='1'"
           . checked(1, $value, false)
           . " aria-label='Email Sending' /></div>";
    }

    public static function displayHelpSection()
    {
        echo '<h3>' . esc_html__('Help', 'postal-mail') . '</h3>
        <p>' . esc_html__('This section provides more information about each of the settings.', 'postal-mail') . '</p>
        <ul>
            <li>
                <strong>Postal API Host</strong>:
                ' . esc_html__('The URL of the Postal API host.', 'postal-mail') . '
            </li>
            <li>
                <strong>Postal API Secret Key</strong>:
                ' . esc_html__('Your Postal API secret key.', 'postal-mail') . '
            </li>
            <li>
                <strong>From Address</strong>:
                ' . esc_html__('The email address that will be used to send emails.', 'postal-mail') . '
            </li>
            <li>
                <strong>Email Sending</strong>:
                ' . esc_html__('Whether or not to enable email sending.', 'postal-mail') . '
            </li>
        </ul>';
    }

    public static function displayLastSentEmails()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'postal';
        $emails = $wpdb->get_results("SELECT email, time, message_id, token FROM $table_name ORDER BY id DESC LIMIT 5");

        if (!empty($emails)) {
            echo '<h3>Last 5 Sent Emails</h3>';
            echo '<ul>';
            foreach ($emails as $email) {
                echo '<li>Email: ' . $email->email . ', Time: ' . $email->time
                   . ', Message ID: ' . $email->message_id . ', Token: ' . $email->token . '</li>';
            }
            echo '</ul>';
        }
    }
}

// Outside the class and namespace, at the end of the `settings.php` file

add_action('after_plugin_row_' . plugin_basename(__FILE__), 'postal_add_important_notice', 0, 3);

/**
 * Display an important notice below the plugin details.
 *
 * @param string $plugin_file The plugin file name.
 * @param array  $plugin_data The plugin file data.
 * @param string $status      The plugin file status.
 */
function postal_add_important_notice($plugin_file, $plugin_data, $status) {
    ?>
    <tr class="plugin-update-tr active">
        <td colspan="3" class="plugin-update colspanchange">
            <div class="update-message notice inline notice-warning notice-alt">
                <p><strong>IMPORTANT NOTICE:</strong> From this Version[2.2.4] update onwards, the plugin and its support will be handled by WP Swings.
                    We are going to discontinue the ORG version of this plugin. We will be providing support to our premium users only for six months. We are migrating this plugin on WooCommerce. Please Visit <a href="https://wpswings.com" target="_blank">WP Swings</a> for all your WordPress/WooCommerce solutions.</p>
            </div>
        </td>
    </tr>
    <?php
}


PostalMailSettings::init();
