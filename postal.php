<?php
/**
 * Postal Mail
 *
 * @package     PostalMail
 * @wordpress-plugin
 * Plugin Name: Postal Mail
 * Plugin URI: https://uhlhosting.ch/postal-mail
 * Description: A plugin to send emails through the Postal Mail API
 * Version: 1.0.1
 * Author: Viorel-Cosmin Miron
 * Author URI: https://uhlhosting.ch
 * Text Domain: postal-mail
 * Domain Path: /lang
 */

namespace PostalWp;

use AtelliTech\Postal\Client;
use AtelliTech\Postal\SendMessage;

// Require the composer autoloader
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
// Include the settings.php file from the includes folder
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

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
     * @var PostalMail|null
     */
    private static $instance = null;
    /**
     * Summary of client
     * @var Client|null
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
     * Summary of initialize
     * Define and initialize constants for the plugin.
     */
    public function initialize()
    {
        // Define your constants here
        define('POSTAL_MAIL_VERSION', '1.0.1');
        define('POSTAL_MAIL_DEBUG', true);

        // Additional initialization code can go here if needed
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
                    $formattedRecipient = addNameToAddress($recipient);
                    $message->to($formattedRecipient);
                }
            } elseif (isset($params['to'])) {
                $formattedRecipient = addNameToAddress($params['to']);
                $message->to($formattedRecipient);
            }

            // Add BCC recipients with display names
            if (isset($params['bcc']) && is_array($params['bcc'])) {
                foreach ($params['bcc'] as $bccRecipient) {
                    $formattedBccRecipient = addNameToAddress($bccRecipient);
                    $message->bcc($formattedBccRecipient);
                }
            } elseif (isset($params['bcc'])) {
                $formattedBccRecipient = addNameToAddress($params['bcc']);
                $message->bcc($formattedBccRecipient);
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

            // Check if both plain and HTML bodies are provided
            if (isset($params['plain_body']) && isset($params['html_body'])) {
                $message->setParts([
                    ['type' => 'text/plain', 'content' => $params['plain_body']],
                    ['type' => 'text/html', 'content' => $params['html_body']],
                ]);
            } elseif (isset($params['plain_body'])) {
                // Set the plain body
                $message->plainBody($params['plain_body']);
            } elseif (isset($params['html_body'])) {
                // Set the HTML body
                $message->htmlBody($params['html_body']);
            }

            // Assume $params['headers'] contains an array of custom headers like this:
            $params['headers'] = array(
                'X-Custom-Header' => 'UHL-Post',
                'X-Mailer' => 'WordPress Mailer'
            );

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
        } catch (\Exception $e) {
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
        // Fetch the option value from the WordPress database
        $postal_wp_switch = get_option('postal_wp_switch');

        // Check if email sending is enabled or disabled
        if (!$postal_wp_switch) {
            // If it's disabled, use the default WordPress email function
            $headers = isset($args['headers']) ? $args['headers'] : [];
            $attachments = isset($args['attachments']) ? $args['attachments'] : '';
            return wp_mail($args['to'], $args['subject'], $args['message'], $headers, $attachments);
        }

        // Ensure the 'headers' key is present in the $args array
        $args['headers'] = isset($args['headers']) ? $args['headers'] : '';

        // Ensure the 'attachments' key is present in the $args array
        $args['attachments'] = isset($args['attachments']) ? $args['attachments'] : [];


        // Create the email parameters
        $params = [
            'subject' => sanitize_text_field($args['subject']),
            'to' => is_array($args['to']) ? array_map('sanitize_email', $args['to']) : [sanitize_email($args['to'])],
            'from' => sanitize_email(get_option('postal_wp_from_address')),
        ];

        // Check if the email is in HTML format
        $is_html = strpos($args['headers'], 'Content-Type: text/html') !== false;

        // If the email is in HTML format but doesn't contain the <html> tag, add it
        if ($is_html && false === strpos($args['message'], '<html')) {
            ob_start();
            ?>
            <!DOCTYPE html>
            <html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <meta name="x-apple-disable-message-reformatting">
                <title></title>
                <!--[if mso]>
                <noscript>
                <xml>
                <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
                </xml>
                </noscript>
                <![endif]-->
            </head>

            <body style="margin:0;padding:0;min-width:100%;background-color:#ffffff;">
                <?php echo $args['message']; ?>
            </body>

            </html>
            <?php
            $params['html_body'] = ob_get_clean();
        } elseif ($is_html) {
            // Set the HTML body
            $params['html_body'] = $args['message'];
        } else {
            // Set the plain text body
            $params['plain_body'] = $args['message'];
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
                            // Decode the "From" header using mb_decode_mimeheader
                            $decodedHeader = mb_decode_mimeheader($value);
                            // Extract the name and email from the decoded header
                            $result = preg_match('/^(.*)<([^>]+)>$/', $decodedHeader, $matches);
                            if ($result) {
                                $name = trim($matches[1]);
                                $email = trim($matches[2]);
                                if (!empty($name) && !empty($email)) {
                                    $params['from'] = addNameToAddress($email, $name);
                                } else {
                                    $params['from'] = sanitize_email($email);
                                }
                            } else {
                                $params['from'] = sanitize_email($value);
                            }
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

    public function enqueue_scripts($hook)
    {
        if ('settings_page_postal_mail_settings' != $hook) {
            // Only loads the scripts on the Postal Mail settings page
            return;
        }

        // Enqueue the JavaScript file
        wp_enqueue_script('postal-mail', plugins_url('js/postal-mail.js', __FILE__), [], false, true);

        // Enqueue the CSS file
        wp_enqueue_style('postal-mail', plugins_url('css/postal-mail.css', __FILE__));
    }

    public function init()
    {
        PostalMailSettings::init();
    }
}

/**
 * Add a display name part to an email address if provided.
 *
 * SpamAssassin doesn't like addresses in HTML messages that are missing display names (e.g., `foo@bar.org`
 * instead of `"Foo Bar" <foo@bar.org>`).
 *
 * @param string $address The email address.
 * @param string|null $name The display name. If null, the address is returned as is.
 *
 * @return string The email address, optionally with a display name.
 */
function addNameToAddress($address, $name = null)
{
    // If it's just the address, without a display name
    if (is_email($address) && $name !== null) {
        // Add the name to the address in the format "Name <email>"
        $address = sprintf('"%s" <%s>', $name, $address);
    }

    return $address;
}


add_filter('wp_mail', function ($args) {
    $postalMail = PostalMail::getInstance();
    return $postalMail->postal_mail($args);
});

// Load the plugin text domain for translation
add_action('plugins_loaded', function () {
    load_plugin_textdomain('postal-mail', false, dirname(plugin_basename(__FILE__)) . '/lang/');
});

// Add a plugin action link to the plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . admin_url('options-general.php?page=postal_mail') . '">' . __('Settings', 'postal-mail') . '</a>';
    return $links;
});

add_action('wp_ajax_postal_mail_test_email', function () {
    $domain = $_SERVER['SERVER_NAME'];
    $from_email = 'postmaster@' . $domain;

    $postalMail = PostalMail::getInstance();
    $result = $postalMail->postal_mail([
        'to' => get_option('admin_email'),
        'from' => 'Postmaster <' . $from_email . '>',
        // Add this line
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

// Enqueue the JavaScript and CSS files
add_action('admin_enqueue_scripts', [PostalMail::getInstance(), 'enqueue_scripts']);

add_action('init', [PostalMail::getInstance(), 'init']);