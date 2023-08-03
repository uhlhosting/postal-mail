document.addEventListener('DOMContentLoaded', function () {
    const testEmailButton = document.querySelector('#test_email_button');

    function disableButton() {
        console.log('Disabling button...');
        testEmailButton.disabled = true;
    }

    function enableButton() {
        console.log('Enabling button...');
        testEmailButton.disabled = false;
        testEmailButton.innerText = 'Send Test Email'; // Revert the button text
    }

    function showLoadingIndicator() {
        console.log('Showing loading indicator...');
        testEmailButton.innerText = 'Sending...';
    }

    function showSuccessMessage() {
        console.log('Showing success message...');
        // Create a new a element
        const link = document.createElement('a');

        // Set the href to the ajaxurl
        link.href = ajaxurl;

        // Extract the domain (hostname)
        let domain = link.hostname;
        const parts = domain.split('.');
        if (parts.length > 2) {
            parts.shift();
            domain = parts.join('.');
        }

        const senderEmail = "postmaster@" + domain;
        const sender = "Postmaster <" + senderEmail + ">";

        alert(`Test email sent successfully from: `);
    }

    function showErrorMessage(error) {
        console.log('Showing error message...');
        alert('Error: ' + error);
    }

    function handleTestEmailButtonClick(event) {
        const target = event.target;

        if (!target.matches('#test_email_button')) {
            return;
        }

        disableButton();
        showLoadingIndicator();

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=postal_mail_test_email',
        })
            .then(async (response) => {
                enableButton();

                if (response.ok) {
                    showSuccessMessage();
                } else {
                    throw new Error('Failed to send test email.');
                }
            })
            .catch((error) => {
                enableButton();
                showErrorMessage(error.message);
            });
    }

    document.addEventListener('click', handleTestEmailButtonClick);
});
