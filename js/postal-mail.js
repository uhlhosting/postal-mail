/**
 * File: postal-mail.js
 *
 * This script handles the click event for the 'Send Test Email' button
 * in the Postal Mail settings page. When the button is clicked, it sends
 * an AJAX request to the server to send a test email.
 *
 * Dependencies: None
 *
 * @package PostalMail
 */

document.addEventListener('DOMContentLoaded', function () {
    var testEmailButton = document.querySelector('#test_email_button');
    testEmailButton.addEventListener('click', function () {
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=postal_mail_test_email',
        })
            .then(response => response.text())
            .then(response => alert(response))
            .catch(error => alert('Error: ' + error));
    });
});
