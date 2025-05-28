<?php
add_shortcode('email_deliverability_checker', 'render_email_deliverability_checker');

function render_email_deliverability_checker()
{
    ob_start();
?>
    <form method="post">
        <div class="search">
            <input type="email" name="edc_email" class="searchTerm" placeholder="Enter email address" required>
            <button type="submit" class="searchButton">Check Email</button>
        </div>
    </form>
<?php

    if (isset($_POST['edc_email'])) {
        $email = sanitize_email($_POST['edc_email']);
        $result = edc_check_email_deliverability($email);

        if (isset($result['error'])) {
            echo "<p style='color:red;'>" . esc_html($result['error']) . "</p>";
        } else {
            echo "<div class='resultwrwpper' style='margin-top: 20px;'><div class='innerwrapper'><h3>Results for <em>" . esc_html($email) . "</em>:</h3>";
            echo "<ul>";
            echo "<li><strong>Domain:</strong> " . esc_html($result['domain']) . "</li>";
            echo "<li><strong>MX Record:</strong> " . ($result['MX'] ? '✅ Present' : '❌ Missing') . "</li>";
            echo "<li><strong>SPF Record:</strong> " . ($result['SPF'] ? '✅ Found' : '❌ Not Found') . "</li>";
            echo "<li><strong>DKIM Record:</strong> " . ($result['DKIM'] ? '✅ Found' : '❌ Not Found') . "</li>";
            echo "<li><strong>DMARC Record:</strong> " . ($result['DMARC'] ? '✅ Found' : '❌ Not Found') . "</li>";
            echo "<li><strong>SMTP Banner:</strong> " . ($result['SMTP'] ? esc_html($result['SMTP']) : '❌ Could not connect') . "</li>";
            echo "</ul></div></div>";
        }
    }

    return ob_get_clean();
}

function edc_check_email_deliverability($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['error' => 'Invalid email format'];
    }

    $domain = substr(strrchr($email, "@"), 1);
    $dns = [
        'MX'    => dns_get_record($domain, DNS_MX),
        'SPF'   => dns_get_record($domain, DNS_TXT),
        'DKIM'  => dns_get_record("default._domainkey.$domain", DNS_TXT),
        'DMARC' => dns_get_record("_dmarc.$domain", DNS_TXT),
    ];

    // Check SPF
    $spf_found = false;
    foreach ($dns['SPF'] as $record) {
        if (stripos($record['txt'], 'v=spf1') !== false) {
            $spf_found = true;
            break;
        }
    }

    // SMTP banner test
    $smtp_banner = edc_smtp_banner($dns['MX'] ?? []);

    return [
        'domain' => $domain,
        'MX'     => !empty($dns['MX']),
        'SPF'    => $spf_found,
        'DKIM'   => !empty($dns['DKIM']),
        'DMARC'  => !empty($dns['DMARC']),
        'SMTP'   => $smtp_banner,
    ];
}

function edc_smtp_banner($mxRecords)
{
    if (empty($mxRecords)) return false;

    $host = $mxRecords[0]['target'];
    $fp = @fsockopen($host, 25, $errno, $errstr, 5);
    if (!$fp) return false;

    $banner = fgets($fp, 1024);
    fclose($fp);
    return trim($banner);
}
