<?php

add_action('wp_ajax_check_dmarc_record', 'handle_dmarc_ajax');
add_action('wp_ajax_nopriv_check_dmarc_record', 'handle_dmarc_ajax');

function handle_dmarc_ajax()
{
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $dmarcDomain = '_dmarc.' . $domain;
    $records = dns_get_record($dmarcDomain, DNS_TXT);

    if ($records === false) {
        echo json_encode(['error' => 'Failed to retrieve DMARC records']);
        wp_die();
    }

    $dmarcRecord = null;
    foreach ($records as $record) {
        if (isset($record['txt']) && strpos($record['txt'], 'v=DMARC1') === 0) {
            $dmarcRecord = $record['txt'];
            break;
        }
    }

    echo json_encode(['record' => $dmarcRecord]);
    wp_die();
}


function dmarc_record_checker_shortcode()
{
    ob_start(); ?>
    <form id="dmarc-check-form">
        <div class="search">
            <input type="text" id="dmarc-domain" class="searchTerm" placeholder="Enter domain (e.g., example.com)" required>
            <button type="submit" class="searchButton">Check DMARC</button>
        </div>

    </form>
    <div id="dmarc-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
        document.getElementById('dmarc-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('dmarc-domain').value;
            const resultBox = document.getElementById('dmarc-result');
            resultBox.innerHTML = 'Checking...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_dmarc_record&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                        return;
                    }

                    if (data.record) {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <strong>DMARC Record:</strong> ${data.record}
                    </div>`;
                    } else {
                        resultBox.innerHTML = `<div>No DMARC record found.</div>`;
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
add_shortcode('dmarc_checker', 'dmarc_record_checker_shortcode');
