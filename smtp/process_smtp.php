<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

function performSMTPServerChecks($smtpHost, $smtpPort) {
    $results = [];
    $connection = null;

    try {
        $start = microtime(true);
        $connection = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 5); // 5-second timeout
        $connectionTime = round(microtime(true) - $start, 3);
        $results['SMTP Connection Time'] = "<span class='success'>{$connectionTime} seconds - Good on Connection time</span>";

        if (!$connection) {
            $results['SMTP Connection'] = "<span class='error'>FAILED - Could not connect: {$errstr} ({$errno})</span>";
            return $results;
        } else {
            $results['SMTP Connection'] = "<span class='success'>OK</span>";
        }

        $banner = fgets($connection);
        $results['SMTP Banner Check'] = preg_match("/{$_SERVER['SERVER_ADDR']}/", $banner)
            ? "<span class='success'>OK - Reverse DNS matches SMTP Banner</span>"
            : "<span class='warning'>MISMATCH - Reverse DNS does not match SMTP Banner</span>";

        $reverseDNS = gethostbyaddr($_SERVER['SERVER_ADDR']);
        $results['SMTP Reverse DNS Mismatch'] = ($reverseDNS !== $_SERVER['SERVER_ADDR'])
            ? "<span class='success'>OK - {$_SERVER['SERVER_ADDR']} resolves to {$reverseDNS}</span>"
            : "<span class='warning'>MISMATCH - No PTR record found for {$_SERVER['SERVER_ADDR']}</span>";

        $results['SMTP Valid Hostname'] = (filter_var($reverseDNS, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
            ? "<span class='success'>OK - Reverse DNS is a valid Hostname</span>"
            : "<span class='warning'>FAILED - Reverse DNS is not a valid Hostname</span>";

        // Check for TLS support (EHLO and STARTTLS)
        fwrite($connection, "EHLO test.com\r\n");
        $ehloResponse = '';
        while ($str = fgets($connection)) {
            $ehloResponse .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        $results['SMTP TLS'] = preg_match("/STARTTLS/i", $ehloResponse)
            ? "<span class='success'>OK - Supports TLS.</span>"
            : "<span class='info'>NOT SUPPORTED - Does not advertise STARTTLS.</span>";

        // Basic Open Relay Check (very basic and not foolproof)
        fwrite($connection, "MAIL FROM:<test@example.com>\r\n");
        $mailFromResponse = fgets($connection);
        fwrite($connection, "RCPT TO:<test2@example.com>\r\n");
        $rcptToResponse = fgets($connection);
        fwrite($connection, "QUIT\r\n");
        fgets($connection); // Read QUIT response

        $results['SMTP Open Relay'] = (strpos($mailFromResponse, '250') !== false && strpos($rcptToResponse, '250') !== false)
            ? "<span class='warning'>POTENTIAL RISK - Server accepted recipients without authentication (very basic check).</span>"
            : "<span class='success'>OK - Likely not an open relay (based on basic check).</span>";

        $startTransaction = microtime(true);
        // Perform a minimal successful transaction to measure time
        fwrite($connection, "EHLO test.com\r\n");
        fgets($connection);
        fwrite($connection, "MAIL FROM:<{$_POST['senderEmail']}>\r\n");
        fgets($connection);
        fwrite($connection, "RCPT TO:<{$_POST['recipientEmail']}>\r\n");
        fgets($connection);
        fwrite($connection, "QUIT\r\n");
        fgets($connection);
        $transactionTime = round(microtime(true) - $startTransaction, 3);
        $results['SMTP Transaction Time'] = "<span class='success'>{$transactionTime} seconds - Good on Transaction Time</span>";

        fclose($connection);

    } catch (\Exception $e) {
        $results['SMTP Connection'] = "<span class='error'>ERROR - {$e->getMessage()}</span>";
    } finally {
        if (is_resource($connection)) {
            fclose($connection);
        }
    }

    return $results;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $smtpHost = $_POST['smtpHost'];
    $smtpPort = $_POST['smtpPort'];
    $smtpAuth = $_POST['smtpAuth'];
    $smtpUsername = $_POST['smtpUsername'] ?? '';
    $smtpPassword = $_POST['smtpPassword'] ?? '';
    $senderEmail = $_POST['senderEmail'];
    $recipientEmail = $_POST['recipientEmail'];
    $emailSubject = $_POST['emailSubject'];
    $emailBody = $_POST['emailBody'];

    $smtpChecks = performSMTPServerChecks($smtpHost, $smtpPort);

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->Port       = $smtpPort;

        //Authentication
        if ($smtpAuth === 'yes') {
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        //Recipients
        $mail->setFrom($senderEmail, 'SMTP Test');
        $mail->addAddress($recipientEmail);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body    = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        $startSend = microtime(true);
        $mail->send();
        $sendTime = round(microtime(true) - $startSend, 3);
        echo "<p class='success'>Email sent successfully! (Send time: {$sendTime} seconds)</p>";

        echo "<h3>SMTP Server Checks:</h3><ul>";
        foreach ($smtpChecks as $key => $value) {
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";

    } catch (Exception $e) {
        echo "<p class='error'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
        echo "<h3>SMTP Server Checks:</h3><ul>";
        foreach ($smtpChecks as $key => $value) {
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";
    }
} else {
    echo '<p class="error">Invalid request.</p>';
}
?>