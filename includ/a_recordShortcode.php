<?php

add_action('wp_ajax_check_a_record', 'handle_a_record_ajax');
add_action('wp_ajax_nopriv_check_a_record', 'handle_a_record_ajax');

function handle_a_record_ajax()
{
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $records = dns_get_record($domain, DNS_A);
    if ($records === false || empty($records)) {
        echo json_encode(['error' => 'Failed to retrieve A records']);
        wp_die();
    }

    $formatted = array_map(function ($record) use ($domain) {
        return [
            'record' => $domain,
            'type' => 'A',
            'value' => $record['ip'],
            'ttl' => $record['ttl']
        ];
    }, $records);

    echo json_encode($formatted);
    wp_die();
}



function a_record_checker_shortcode()
{
    ob_start(); ?>

    <form id="a-record-check-form" class="formwrapper">
        <h2 class="title">A Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="a-record-domain" class="searchTerm" placeholder="e.g., example.com">
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="a-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="a-record-result" class="resultwrapper" style="margin-top: 20px;"></div>
             <div class="adds">
               <h3> Advertise display here</h3>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('a-record-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('a-record-domain').value;
            const resultBox = document.getElementById('a-record-result');
            const popup = document.getElementById('a-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_a_record&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                        return;
                    }
                    let output = `<div class="innerwrapper">
                 <h3 style="margin-top: 0;">A Records for <strong>${domain}</strong></h3> 
                    <table style="width:100%; border-collapse: collapse;">
                        <tr><th>Type</th><th>IP Address</th><th>TTL</th></tr>`;
                    data.forEach(item => {
                        output += `<tr>
                        <td>${item.type}</td>
                        <td>${item.value}</td>
                        <td>${item.ttl}</td>
                    </tr>`;
                    });
                    output += '</table></div>';
                    resultBox.innerHTML = output;
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });

        // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('a-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('a-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('a_record_checker', 'a_record_checker_shortcode');
