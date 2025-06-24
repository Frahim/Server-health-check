<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Test Tool</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }
        .container {
            width: 500px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width: calc(100% - 12px);
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        #result {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 3px;
            background-color: #f9f9f9;
            white-space: pre-wrap;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>SMTP Test Tool</h2>
        <form id="smtpForm">
            <div>
                <label for="smtpHost">SMTP Host:</label>
                <input type="text" id="smtpHost" name="smtpHost" required value="localhost">
            </div>
            <div>
                <label for="smtpPort">SMTP Port:</label>
                <input type="text" id="smtpPort" name="smtpPort" required value="25">
            </div>
            <div>
                <label for="smtpAuth">SMTP Authentication:</label>
                <select id="smtpAuth" name="smtpAuth">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
            </div>
            <div id="authDetails" style="display: none;">
                <label for="smtpUsername">Username:</label>
                <input type="text" id="smtpUsername" name="smtpUsername">
                <label for="smtpPassword">Password:</label>
                <input type="password" id="smtpPassword" name="smtpPassword">
            </div>
            <div>
                <label for="senderEmail">Sender Email:</label>
                <input type="email" id="senderEmail" name="senderEmail" required>
            </div>
            <div>
                <label for="recipientEmail">Recipient Email:</label>
                <input type="email" id="recipientEmail" name="recipientEmail" required>
            </div>
            <div>
                <label for="emailSubject">Email Subject:</label>
                <input type="text" id="emailSubject" name="emailSubject" required value="SMTP Test Email">
            </div>
            <div>
                <label for="emailBody">Email Body:</label>
                <textarea id="emailBody" name="emailBody" rows="5">This is a test email sent using the SMTP Test Tool.</textarea>
            </div>
            <button type="button" onclick="sendTestEmail()">Send Test Email</button>
        </form>
        <div id="result"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const smtpAuthSelect = document.getElementById('smtpAuth');
            const authDetailsDiv = document.getElementById('authDetails');

            smtpAuthSelect.addEventListener('change', function() {
                authDetailsDiv.style.display = this.value === 'yes' ? 'block' : 'none';
                const usernameInput = document.getElementById('smtpUsername');
                const passwordInput = document.getElementById('smtpPassword');
                usernameInput.required = this.value === 'yes';
                passwordInput.required = this.value === 'yes';
            });
        });

        async function sendTestEmail() {
            const formData = new FormData(document.getElementById('smtpForm'));
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Sending email...';

            try {
                const response = await fetch('process_smtp.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.text();
                resultDiv.innerHTML = data;
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">An error occurred: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>