<?php
/**
 * Postal Mail
 *
 * @package           UHLPostalMail
 * @author            Viorel-Cosmin Miron
 * @copyright         2023 UHL-Services
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Postal Mail
 * Plugin URI:        https://uhlhosting.ch/postal-mail
 * Description:       This plugin integrates with the Postal API to send emails using the Postal service.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Viorel-Cosmin Miron
 * Author URI:        https://uhlhosting.ch
 * Text Domain:       postal-mail
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://uhlhosting.ch/postal-mail/
 */

use AtelliTech\Postal\Client;
use AtelliTech\Postal\SendMessage;
use AtelliTech\Postal\Exception\PostalException;

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/includes/settings.php';

if (!class_exists('AtelliTech\Postal\Client')) {
    // Show an error message in the admin panel if the library is not installed
    add_action('admin_notices', function () {
        echo '<div class="error"><p>The Postal API library is not installed. Please install it by running composer install in the plugin directory.</p></div>';
    });
    return;
}

/**
 * Summary of PostalMail
 */
class PostalMail
{
    /**
     * Summary of instance
     * @var
     */
    private static $instance = null;
    /**
     * Summary of client
     * @var
     */
    private $client;

    /**
     * Summary of __construct
     */
    private function __construct()
    {
        // Create a new Postal client using the server key and URL from the options
        $host = sanitize_text_field(get_option('postal_wp_host')); // sanitize input
        $secretKey = sanitize_text_field(get_option('postal_wp_secret_key')); // sanitize input
        $this->client = new Client($host, $secretKey);
    }

    /**
     * Summary of getInstance
     * @return PostalMail
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new PostalMail();
        }
        return self::$instance;
    }

    /**
     * Summary of getClient
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Summary of send_email_via_postal
     * @param array $params
     * @return bool
     */
    // Modify the send_email_via_postal method
    public function send_email_via_postal($params)
    {
        $client = $this->getClient();

        // Check if the client is set
        if (!$client) {
            error_log('Postal client is not set.');
            return false;
        }

        try {
            // Create a new message using SendMessage class
            $message = new SendMessage;

        // Add some recipients
        if (isset($params['to'])) {
            $recipients = is_array($params['to']) ? $params['to'] : [$params['to']];
            foreach ($recipients as $recipient) {
                $message->to($recipient);
            }
        }

        // Add BCC recipients
        if (isset($params['bcc'])) {
            $bccRecipients = is_array($params['bcc']) ? $params['bcc'] : [$params['bcc']];
            foreach ($bccRecipients as $bccRecipient) {
                $message->bcc($bccRecipient);
            }
        }

        // Specify who the message should be from. This must be from a verified domain
        // on your mail server.
        if (isset($params['from'])) {
            $message->from($params['from']);
        }

        // Set the subject
        if (isset($params['subject'])) {
            $message->subject($params['subject']);
        }

        // Check if both plain and HTML bodies are provided and not null
        if (isset($params['plain_body']) && !is_null($params['plain_body'])) {
            $message->plainBody($params['plain_body']);
        }
        if (isset($params['html_body']) && !is_null($params['html_body'])) {
            $message->htmlBody($params['html_body']);
        }

        // Add any custom headers
        if (isset($params['headers']) && is_array($params['headers'])) {
            foreach ($params['headers'] as $headerKey => $headerValue) {
                if (!is_null($headerKey) && !is_null($headerValue)) {
                    $message->header($headerKey, $headerValue);
                }
            }
        }

        // Send the message and get the result
        $result = $message->send($client);

        // Check if there are recipients
        if ($result->size() > 0) {
            // Loop through each of the recipients to get the message ID and token
            foreach ($result->recipients() as $email => $message) {
                // Get the message ID and token for each recipient
                $messageID = $message->id();
                $messageToken = $message->token();

                // Store these values in the database along with the recipient's email
                global $wpdb;
                $table_name = $wpdb->prefix . 'postal';
                $wpdb->insert(
                    $table_name,
                    [
                        'email' => $email,
                        'message_id' => $messageID,
                        'token' => $messageToken,
                        'time' => current_time('mysql'),
                    ]
                );
            }
            return true; // Return true if the send was successful
        } else {
            // If there was an error, log it and return false
            error_log('Postal API Error: Failed to send email.');
            return false;
        }
    } catch (Exception $e) {
        // If there was an exception, log it and return false
        error_log('Postal API Exception: ' . $e->getMessage());
        return false;}
    }


    /**
     * Summary of postal_mail
     * @param mixed $args
     * @return mixed
     */
    public function postal_mail($args)
    {
        // Check if email sending is enabled or disabled
        $postal_wp_switch = get_option('postal_wp_switch');
        if (!$postal_wp_switch) {
            // If it's disabled, use the default WordPress email function
            return wp_mail($args['to'], $args['subject'], $args['message'], $args['headers'], $args['attachments']);
        }

        // Create the email parameters
        $params = [
            'subject' => sanitize_text_field($args['subject']),
            'to' => is_array($args['to']) ? array_map('add_name_to_address', array_map('sanitize_email', $args['to'])) : [add_name_to_address(sanitize_email($args['to']))],
            'from' => sanitize_email(get_option('postal_wp_from_address')),
        ];

        // Check if the message is provided and not null
        if (isset($args['message']) && !is_null($args['message'])) {
            $plain_body = $args['message'];
            $html_body = $args['message']; // Directly assign the HTML content without sanitization or additional tags
            $params['plain_body'] = $plain_body;
            $params['html_body'] = $html_body;
        }

        // Check if there are any headers and add them to the parameters
        if (!empty($args['headers'])) {
            $headers = explode("\n", $args['headers']);
            foreach ($headers as $header) {
                if (strpos($header, ':') !== false) {
                    list($key, $value) = explode(':', $header);
                    $key = trim(sanitize_text_field($key));
                    $value = trim(sanitize_text_field($value));
                    switch ($key) {
                        case 'From':
                            $params['from'] = sanitize_email($value);
                            break;
                        case 'Reply-To':
                            $params['reply_to'] = sanitize_email($value);
                            break;
                        case 'CC':
                            $params['cc'] = array_map('sanitize_email', explode(',', $value));
                            break;
                        case 'BCC':
                            $params['bcc'] = array_map('sanitize_email', explode(',', $value));
                            break;
                    }
                }
            }
        }

        // Check if there are any attachments and add them to the parameters
        if (!empty($args['attachments'])) {
            $params['attachments'] = [];
            foreach ($args['attachments'] as $attachment) {
                if (file_exists($attachment)) {
                    $params['attachments'][] = [
                        'name' => sanitize_file_name(basename($attachment)),
                        'data' => base64_encode(file_get_contents($attachment)),
                    ];
                }
            }
        }

        // Send the email using the Postal API
        return $this->send_email_via_postal($params);
    }

}

/**
 * Add a display name part to an email address
 *
 * SpamAssassin doesn't like addresses in HTML messages that are missing display names (e.g., `foo@bar.org`
 * instead of `"Foo Bar" <foo@bar.org>`.
 *
 * @param string $address
 *
 * @return string
 */
function add_name_to_address($address)
{
    // If it's just the address, without a display name
    if (is_email($address)) {
        $address = sprintf('"%s" <%s>', $address, $address);
    }

    return $address;
}

// Function to create the postal table
function create_postal_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'postal';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(100) NOT NULL,
        message_id varchar(255) NOT NULL,
        token varchar(255) NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Register activation hook
register_activation_hook(__FILE__, 'postal_plugin_activation');

// Register deactivation hook
register_deactivation_hook(__FILE__, 'postal_plugin_deactivation');

// Activation hook function
function postal_plugin_activation()
{
    error_log('Plugin activation hook triggered.'); // Add this line
    create_postal_table();
}

// Deactivation hook function
function postal_plugin_deactivation()
{
    // Your deactivation tasks here
    // Perform cleanup tasks on plugin deactivation if needed
}


// Override the default WordPress email function
add_filter('wp_mail', function ($args) {
    $postalMail = PostalMail::getInstance();
    return $postalMail->postal_mail($args);
});

// Load the plugin text domain for translation
add_action('plugins_loaded', function () {
    load_plugin_textdomain('postal-mail', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

// Add a plugin action link to the plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . admin_url('options-general.php?page=postal_mail_settings') . '">' . __('Settings', 'postal-mail') . '</a>';
    return $links;
});

add_action('wp_ajax_postal_mail_test_email', function () {
    $postalMail = PostalMail::getInstance();
    $result = $postalMail->postal_mail([
        'to' => get_option('admin_email'),
        'tag' => __('test email', 'postal-mail'),
        'subject' => __('Test email from Postal Mail', 'postal-mail'),
        'message' => __('This is a test email sent from the Postal Mail plugin.', 'postal-mail'),
    ]);
    if ($result) {
        _e('Test email sent successfully.', 'postal-mail');
    } else {
        _e('Failed to send test email.', 'postal-mail');
    }
    wp_die();
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ('settings_page_postal_mail_settings' != $hook) {
        // Only loads the scripts on the Postal Mail settings page
        return;
    }
    // Enqueue the JavaScript file
    wp_enqueue_script('postal-mail-js', plugin_dir_url(__FILE__) . 'js/postal-mail.js', array('jquery'), '1.0', true);

    // Enqueue the CSS file
    wp_enqueue_style('postal-mail-style', plugins_url('css/postal-mail.css', __FILE__));
});

