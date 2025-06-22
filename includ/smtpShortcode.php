<?php
add_action('wp_ajax_check_smtp_server', 'handle_smtp_ajax');
add_action('wp_ajax_nopriv_check_smtp_server', 'handle_smtp_ajax');

function handle_smtp_ajax()
{
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $mxRecords = dns_get_record($domain, DNS_MX);
    if (!$mxRecords || empty($mxRecords)) {
        echo json_encode(['error' => 'No MX records found for domain']);
        wp_die();
    }

    $mxHost = $mxRecords[0]['target'];
    $ip = gethostbyname($mxHost);
    $port = 25;
    $timeout = 10;

    $start = microtime(true);
    $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    $response = '';
    if ($connection) {
        stream_set_timeout($connection, 5);
        $response = fgets($connection, 1024);
        fclose($connection);
        $status = 'Success';
    } else {
        $status = 'Failed';
        $response = "Error: $errstr ($errno)";
    }

    echo json_encode([
        'mx_host' => $mxHost,
        'ip' => $ip,
        'port' => $port,
        'status' => $status,
        'response' => $response,
        'time_ms' => round((microtime(true) - $start) * 1000)
    ]);
    wp_die();
}

function smtp_checker_shortcode()
{
    ob_start(); ?>
    <form id="smtp-check-form" class="formwrapper">
        <h2 class="title">SMTP Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="smtp-domain" class="searchTerm" placeholder="e.g., example.com" required>
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="smtp-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="smtp-result" class="resultwrapper" style="margin-top: 20px;"></div>
             <div class="adds">
               <h3> Advertise display here</h3>
            </div>
        </div>
    </div>
   
    

    <script>
        document.getElementById('smtp-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('smtp-domain').value;
            const resultBox = document.getElementById('smtp-result');
            const popup = document.getElementById('smtp-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';         

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_smtp_server&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    } else {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <h3 style="margin-top: 0;">SMTP Check Result:</h3>
                        MX Host: <code>${data.mx_host}</code><br>
                        IP: <code>${data.ip}</code><br>
                        Port: <code>${data.port}</code><br>
                        Status: <strong style="color:${data.status === 'Success' ? 'green' : 'red'};">${data.status}</strong><br>
                        Response: <pre style="white-space:pre-wrap;">${data.response}</pre>
                    </div>`;
                    }
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });
        // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('smtp-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('smtp-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('smtp_checker', 'smtp_checker_shortcode');
