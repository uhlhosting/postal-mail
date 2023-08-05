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

    // Disable the button during AJAX request
    function disableButton() {
        testEmailButton.disabled = true;
    }

    // Enable the button after AJAX request is complete
    function enableButton() {
        testEmailButton.disabled = false;
        testEmailButton.innerText = 'Send Test Email'; // Revert the button text
    }

    // Show a loading spinner while AJAX request is in progress
    function showLoadingIndicator() {
        testEmailButton.innerText = 'Sending...';
    }

    // Show a success message
    function showSuccessMessage() {
        // Create a new a element
        var link = document.createElement('a');

        // Set the href to the ajaxurl
        link.href = ajaxurl;

        // Extract the domain (hostname)
        var domain = link.hostname;

        // Generate sender email
        var senderEmail = "postmaster@" + domain;

        alert(`Test email sent successfully from: ${senderEmail}`);
    }

    // Show an error message
    function showErrorMessage(error) {
        // Implement your error message display here, for example:
        alert('Error: ' + error);
    }

    testEmailButton.addEventListener('click', function () {
        // Disable the button during AJAX request
        disableButton();

        // Show a loading indicator while the request is in progress
        showLoadingIndicator();

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=postal_mail_test_email',
        })
            .then(function (response) {
                // Re-enable the button after AJAX request is complete
                enableButton();

                // Check the response status and handle accordingly
                if (response.ok) {
                    showSuccessMessage();
                } else {
                    // Handle HTTP errors
                    throw new Error('Failed to send test email.');
                }
            })
            .catch(function (error) {
                // Re-enable the button after AJAX request is complete
                enableButton();

                // Show the error message
                showErrorMessage(error.message);
            });
    });
});
