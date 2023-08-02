<?php

/**
 * Plugin Name: Postal Mail
 * Plugin URI: https://uhlhosting.ch/postal-mail
 * Description: A plugin to send emails through Postal Mail API
 * Version: 1.0.1
 * Author: Viorel-Cosmin Miron
 * Author URI: https://uhlhosting.ch
 * Text Domain: postal-mail
 */

namespace PostalWp;

use AtelliTech\Postal\Client;
use AtelliTech\Postal\SendMessage;
use AtelliTech\Postal\Exception\PostalException;

// Require the composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

if (!class_exists('AtelliTech\Postal\Client')) {
    // Show an error message in the admin panel if the library is not installed
    add_action('admin_notices', function () {
        echo '<div class="error"><p>The Postal API library is not installed. Please install it by running composer install in the plugin directory.</p></div>';
    });
    return;
}

// Include the settings.php file from the includes folder
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

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
            if (isset($params['to']) && is_array($params['to'])) {
                foreach ($params['to'] as $recipient) {
                    $message->to($recipient);
                }
            } elseif (isset($params['to'])) {
                $message->to($params['to']);
            }

            if (isset($params['bcc']) && is_array($params['bcc'])) {
                foreach ($params['bcc'] as $bccRecipient) {
                    $message->bcc($bccRecipient);
                }
            } elseif (isset($params['bcc'])) {
                $message->bcc($params['bcc']);
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

            // Set the content for the e-mail
            if (isset($params['plain_body'])) {
                $message->plainBody($params['plain_body']);
            }

            if (isset($params['html_body'])) {
                $message->htmlBody($params['html_body']);
            }

            // Add any custom headers
            if (isset($params['headers']) && is_array($params['headers'])) {
                foreach ($params['headers'] as $headerKey => $headerValue) {
                    $message->header($headerKey, $headerValue);
                }
            }

            // Send the message and get the result
            $result = $message->send($client);

            // Log the response
            error_log('Postal API Response:');
            error_log(print_r($result, true));

            return true;
        } catch (Exception $e) {
            // If there was an error, log it and return false
            error_log('Postal API Error: ' . $e->getMessage());
            return false;
        }
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
            'to' => is_array($args['to']) ? array_map('sanitize_email', $args['to']) : [sanitize_email($args['to'])],
            'from' => sanitize_email(get_option('postal_wp_from_address')),
            'html_body' => sanitize_textarea_field($args['message']),
        ];

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
    $links[] = '<a href="' . admin_url('options-general.php?page=postal_mail') . '">' . __('Settings', 'postal-mail') . '</a>';
    return $links;
});

add_action('wp_ajax_postal_mail_test_email', function () {
    $postalMail = PostalMail::getInstance();
    $result = $postalMail->postal_mail([
        'to' => get_option('admin_email'),
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

// Enqueue the JavaScript file
add_action('admin_enqueue_scripts', function ($hook) {
    if ('settings_page_postal_mail_settings' != $hook) {
        // Only loads the script on the Postal Mail settings page
        return;
    }
    wp_enqueue_script('postal-mail', plugins_url('js/postal-mail.js', __FILE__), ['jquery'], false, true);
});