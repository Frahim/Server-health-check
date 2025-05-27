<?php

add_action('wp_ajax_check_mx_record', 'handle_mx_ajax');
add_action('wp_ajax_nopriv_check_mx_record', 'handle_mx_ajax');

function handle_mx_ajax()
{
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $startTime = microtime(true);
    $mxRecords = dns_get_record($domain, DNS_MX);
    if ($mxRecords === false || empty($mxRecords)) {
        echo json_encode(['error' => 'Failed to retrieve MX records']);
        wp_die();
    }

    $mxHost = $mxRecords[0]['target'];
    $ip = gethostbyname($mxHost);
    $testDuration = round((microtime(true) - $startTime) * 1000); // in ms

    $ipInfoJson = file_get_contents("http://ip-api.com/json/{$ip}?fields=status,message,as,country,org,query");
    $ipInfo = json_decode($ipInfoJson, true);

    $response = [
        "mx_record" => $mxHost,
        "ip" => $ip,
        "status" => $ipInfo['status'] === 'success' ? "Success" : "Failed",
        "test_duration_ms" => $testDuration,
        "as_number" => $ipInfo['as'] ?? null,
        "organization" => $ipInfo['org'] ?? null,
        "domain" => parse_url("http://$domain", PHP_URL_HOST),
        "country" => $ipInfo['country'] ?? null,

    ];

    echo json_encode($response);
    wp_die();
}


function mx_checker_shortcode()
{
    ob_start();
?>
    <form id="mx-check-form">
        <div class="search">
            <input type="text" id="mx-domain" class="searchTerm" placeholder="Enter domain (e.g., example.com)">
            <button type="submit" class="searchButton">
                Check MX
            </button>
        </div>

    </form>
    <div id="mx-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
        document.getElementById('mx-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('mx-domain').value;
            const resultBox = document.getElementById('mx-result');
            resultBox.textContent = 'Checking...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_mx_record&domain=' + encodeURIComponent(domain))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div class="p10" style="color: red;">Error: ${data.error}</div>`;
                        return;
                    }

                    resultBox.innerHTML = `
        <div class="innerwrapper">
            <h3 style="margin-top: 0;">MX Record Info for <strong>${data.domain}</strong></h3>
            <ul style="list-style: none; padding: 0;">
                <li><strong>MX Record:</strong> ${data.mx_record}</li>
                <li><strong>IP Address:</strong> ${data.ip}</li>
                <li><strong>Status:</strong> ${data.status}</li>
                <li><strong>Response Time:</strong> ${data.test_duration_ms} ms</li>
                <li><strong>AS Number:</strong> ${data.as_number || 'N/A'}</li>
                <li><strong>Organization:</strong> ${data.organization || 'N/A'}</li>
                <li><strong>Country:</strong> ${data.country || 'N/A'}</li>
            </ul>
        </div>
    `;
                })
                .catch(error => {
                    resultBox.textContent = 'Error: ' + error;
                });
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('mx_checker', 'mx_checker_shortcode');
