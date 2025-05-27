<?php
add_action('wp_ajax_check_spf_record', 'handle_spf_ajax');
add_action('wp_ajax_nopriv_check_spf_record', 'handle_spf_ajax');

function handle_spf_ajax() {
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $records = dns_get_record($domain, DNS_TXT);
    if ($records === false) {
        echo json_encode(['error' => 'Failed to retrieve TXT records']);
        wp_die();
    }

    $spfRecord = null;
    foreach ($records as $record) {
        if (isset($record['txt']) && strpos($record['txt'], 'v=spf') === 0) {
            $spfRecord = $record['txt'];
            break;
        }
    }

    echo json_encode(['record' => $spfRecord]);
    wp_die();
}

function spf_checker_shortcode() {
    ob_start(); ?>
    <form id="spf-check-form">

 <div class="search">
            <input type="text"  id="spf-domain" class="searchTerm" placeholder="Enter domain (e.g., example.com)">
            <button type="submit" class="searchButton">
               Check SPF
            </button>
        </div>
    </form>
    <div id="spf-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
    document.getElementById('spf-check-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const domain = document.getElementById('spf-domain').value;
        const resultBox = document.getElementById('spf-result');
        resultBox.innerHTML = 'Checking...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_spf_record&domain=' + encodeURIComponent(domain))
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    return;
                }

                if (data.record) {
                    resultBox.innerHTML = `<div  class="innerwrapper">
                        <strong>SPF Record:</strong> ${data.record}
                    </div>`;
                } else {
                    resultBox.innerHTML = `<div class="innerwrapper">No SPF record found.</div>`;
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
add_shortcode('spf_checker', 'spf_checker_shortcode');
