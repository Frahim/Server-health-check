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

    $testDuration = round((microtime(true) - $startTime) * 1000); // in ms

    $mxInfoList = [];

    foreach ($mxRecords as $record) {
        $mxHost = $record['target'];
        $priority = $record['pri'];
        $ip = gethostbyname($mxHost);

        $ipInfoJson = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,message,as,country,org,query");
        $ipInfo = json_decode($ipInfoJson, true);

        $mxInfoList[] = [
            "priority" => $priority,
            "mx_record" => $mxHost,
            "ip" => $ip,
            "status" => $ipInfo['status'] === 'success' ? "Success" : "Failed",
            "as_number" => $ipInfo['as'] ?? null,
            "organization" => $ipInfo['org'] ?? null,
            "country" => $ipInfo['country'] ?? null,
        ];
    }

    $response = [
        "domain" => parse_url("http://$domain", PHP_URL_HOST),
        "test_duration_ms" => $testDuration,
        "records" => $mxInfoList
    ];

    echo json_encode($response);
    wp_die();
}


function mx_checker_shortcode($atts = []) { 
    $atts = shortcode_atts([
        'img' => '',
		'img2' => '',
        'url' => ''
    ], $atts);   
    ob_start();
?>
    <form id="mx-check-form" class="formwrapper">
        <h2 class="title">MX Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="mx-domain" class="searchTerm" placeholder="e.g., example.com">
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="mx-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="mx-result" class="resultwrapper" style="margin-top: 20px;">
            </div>
           <?php if (!empty($atts['img']) && !empty($atts['url'])) : ?> <!-- NEW -->
                <div class="adds"> <!-- NEW -->
                    <a href="<?php echo esc_url($atts['url']); ?>" target="_blank"> <!-- NEW -->
                        <img class="addimage" src="<?php echo esc_url($atts['img']); ?>" alt="Advertisement"/> <!-- NEW -->
                    </a> <!-- NEW -->
					<a href="<?php echo esc_url($atts['url']); ?>" target="_blank"> <!-- NEW -->
                        <img class="addimage" src="<?php echo esc_url($atts['img2']); ?>" alt="Advertisement"/> <!-- NEW -->
                    </a> <!-- NEW -->
                </div> <!-- NEW -->   
            <?php endif; ?> <!-- NEW -->
        </div>
    </div>


    <script>
        document.getElementById('mx-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('mx-domain').value;
            const resultBox = document.getElementById('mx-result');
            const popup = document.getElementById('mx-result-popup');

            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_mx_record&domain=' + encodeURIComponent(domain))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div class="p10" style="color: red;">Error: ${data.error}</div>`;
                        return;
                    }

                    let output = `
        <div class="innerwrapper">
            <h3 style="margin-top: 0;">MX Records for <strong>${data.domain}</strong></h3>            
            <ul style="list-style: none; padding: 0;">
    `;

                    data.records.forEach(record => {
                        output += `
            <li class="item-wrwpper">
            <ul>
              <li>Priority: <span>${record.priority}</span><br/></li>
              <li>MX Record: <span>${record.mx_record}</span><br/></li>
              <li>IP Address: <span>${record.ip}</span><br/></li>
              <li> Status: <span>${record.status}</span><br/></li>
              <li>AS Number: <span>${record.as_number || 'N/A'}</span><br/></li>
              <li>Organization: <span>${record.organization || 'N/A'}</span><br/></li>
              <li>Country: <span>${record.country || 'N/A'}</span><br/></li>
                     </ul> </li>
        `;
                    });

                    output += `</ul></div>`;
                    resultBox.innerHTML = output;
                })

                .catch(error => {
                    resultBox.innerHTML = `<div class="p10" style="color: red;">Error: ${error}</div>`;
                });
        });

        // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('mx-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('mx-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('mx_checker', 'mx_checker_shortcode');
