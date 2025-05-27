<?php

add_action('wp_ajax_check_ssl_status', 'handle_ssl_ajax');
add_action('wp_ajax_nopriv_check_ssl_status', 'handle_ssl_ajax');

function handle_ssl_ajax() {
    header('Content-Type: application/json');

    $domain = trim($_GET['domain'] ?? '');
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $host = str_replace(['http://', 'https://'], '', $domain);
    $host = explode('/', $host)[0];

    $ctx = stream_context_create([
        "ssl" => [
            "capture_peer_cert" => true,
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ]);

    $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$client) {
        echo json_encode(['error' => "Could not connect to {$host} over SSL"]);
        wp_die();
    }

    $params = stream_context_get_params($client);
    $cert = openssl_x509_parse($params["options"]["ssl"]["peer_certificate"]);

    $validFrom = date('Y-m-d H:i:s', $cert['validFrom_time_t']);
    $validTo = date('Y-m-d H:i:s', $cert['validTo_time_t']);
    $now = time();
    $daysRemaining = floor(($cert['validTo_time_t'] - $now) / 86400);
    $status = ($now >= $cert['validFrom_time_t'] && $now <= $cert['validTo_time_t']) ? 'Valid' : 'Expired';

    echo json_encode([
        'domain' => $host,
        'issuer' => $cert['issuer']['CN'] ?? 'Unknown',
        'valid_from' => $validFrom,
        'valid_to' => $validTo,
        'days_remaining' => $daysRemaining,
        'status' => $status
    ]);

    wp_die();
}


function ssl_checker_shortcode() {
    ob_start(); ?>
    <form id="ssl-check-form">
        <div class="search">
        <input type="text" id="ssl-domain" class="searchTerm" placeholder="Enter domain (e.g., example.com)" required>
        <button type="submit" class="searchButton">Check SSL</button>
</div>
    </form>
    <div id="ssl-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
    document.getElementById('ssl-check-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const domain = document.getElementById('ssl-domain').value;
        const resultBox = document.getElementById('ssl-result');
        resultBox.innerHTML = 'Checking...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_ssl_status&domain=' + encodeURIComponent(domain))
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                } else {
                    resultBox.innerHTML = `<div class="innerwrapper">
                        <strong>SSL Certificate Status:</strong><br>
                        Domain: <code>${data.domain}</code><br>
                        Issuer: <code>${data.issuer}</code><br>
                        Valid From: ${data.valid_from}<br>
                        Valid To: ${data.valid_to}<br>
                        Days Remaining: ${data.days_remaining}<br>
                        Status: <strong style="color:${data.status === 'Valid' ? 'green' : 'red'};">${data.status}</strong>
                    </div>`;
                }
            })
            .catch(err => {
                resultBox.innerHTML = 'Error: ' + err;
            });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('ssl_checker', 'ssl_checker_shortcode');
