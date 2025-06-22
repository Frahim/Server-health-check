<?php

add_action('wp_ajax_check_ssl_status', 'handle_ssl_ajax');
add_action('wp_ajax_nopriv_check_ssl_status', 'handle_ssl_ajax');

function handle_ssl_ajax()
{
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


function ssl_checker_shortcode()
{
    ob_start(); ?>
    <form id="ssl-check-form" class="formwrapper">
        <h2 class="title">SSL Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="ssl-domain" class="searchTerm" placeholder="e.g., example.com" required>
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="ssl-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
             <div id="ssl-result" class="resultwrapper" style="margin-top: 20px;"></div>
            <div class="adds">
                <h3> Advertise display here</h3>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('ssl-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('ssl-domain').value;
            const resultBox = document.getElementById('ssl-result');
             const popup = document.getElementById('ssl-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_ssl_status&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    } else {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <h3 style="margin-top: 0;">SSL Records for <strong>${domain}</strong></h3> 
                        <span>Status: <strong style="color:${data.status === 'Valid' ? 'green' : 'red'};">${data.status}</strong></span><br/>                        
                        Issuer: <strong><code>${data.issuer}</code></strong><br>
                        Valid From:<strong> ${data.valid_from}</strong><br>
                        Valid To:<strong> ${data.valid_to}</strong><br>
                        Days Remaining: <strong>${data.days_remaining}</strong><br>
                       
                    </div>`;
                    }
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });

         // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('ssl-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('ssl-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('ssl_checker', 'ssl_checker_shortcode');
