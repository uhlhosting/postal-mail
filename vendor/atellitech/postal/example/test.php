<?php

namespace Example;

use AtelliTech\Postal\Client;
use AtelliTech\Postal\SendMessage;

require dirname(__DIR__) . '/vendor/autoload.php';

$host = 'xxxx';
$secretKey = 'xxxx';
$params = [
    'subject' => 'Test email',
    'to' => ['xxxx@abc.com'],
    'from' => 'Tester <no-reply@abc.com>',
    'html_body' => '<h3>Hello</h3><p>Test Message</p>'
];

// Create a new Postal client using the server key you generate in the web interface
$client = new Client($host, $secretKey);

$message = new SendMessage($params);
// Loop through each of the recipients to get the message ID
foreach ($result->recipients() as $email => $message) {
    $email;            // The e-mail address of the recipient
    $message->id();    // Returns the message ID
    $message->token(); // Returns the message's token
}

// Create a new message
$message = new SendMessage;

// Add some recipients
$message->to('xxxx@abc.com');
$message->bcc('xxxx@abc.com');

// Specify who the message should be from. This must be from a verified domain
// on your mail server.
$message->from('Tester <no-reply@abc.com>');

// Set the subject
$message->subject('Hi there!');

// Set the content for the e-mail
$message->plainBody('Hello world!');
$message->htmlBody('<p>Hello world!</p>');

// Add any custom headers
$message->header('X-PHP-Test', 'value');

// Attach any files
// $message->attach('textmessage.txt', 'text/plain', 'Hello world!');

// Send the message and get the result
$result = $message->send($client);

// Loop through each of the recipients to get the message ID
foreach ($result->recipients() as $email => $message) {
    $email;            // The e-mail address of the recipient
    $message->id();    // Returns the message ID
    $message->token(); // Returns the message's token
}