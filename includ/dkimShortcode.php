<?php
add_action('wp_ajax_check_dkim_record', 'handle_dkim_ajax');
add_action('wp_ajax_nopriv_check_dkim_record', 'handle_dkim_ajax');

function handle_dkim_ajax()
{
    header('Content-Type: application/json');

    $selector = $_GET['selector'] ?? '';
    $domain = $_GET['domain'] ?? '';

    if (empty($selector) || empty($domain)) {
        echo json_encode(['error' => 'Selector and domain are required']);
        wp_die();
    }

    $dkimHost = $selector . '._domainkey.' . $domain;
    $records = dns_get_record($dkimHost, DNS_TXT);

    if ($records === false) {
        echo json_encode(['error' => 'Failed to retrieve DKIM records']);
        wp_die();
    }

    $dkimRecord = null;
    foreach ($records as $record) {
        if (isset($record['txt']) && strpos($record['txt'], 'v=DKIM1') !== false) {
            $dkimRecord = $record['txt'];
            break;
        }
    }

    echo json_encode(['record' => $dkimRecord]);
    wp_die();
}


function dkim_record_checker_shortcode()
{
    ob_start(); ?>
    <form id="dkim-check-form">
        <div class="search">
            <input type="text" id="dkim-selector" class="searchTerm" placeholder="Selector (e.g., default)" required>
            <input type="text" id="dkim-domain" class="searchTerm" placeholder="Domain (e.g., example.com)" required>
            <button type="submit" class="searchButton">Check DKIM</button>
        </div>

    </form>
    <div id="dkim-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
        document.getElementById('dkim-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const selector = document.getElementById('dkim-selector').value;
            const domain = document.getElementById('dkim-domain').value;
            const resultBox = document.getElementById('dkim-result');
            resultBox.innerHTML = 'Checking...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_dkim_record&selector=' + encodeURIComponent(selector) + '&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    } else if (data.record) {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <strong>DKIM Record:</strong><br><code style="word-break:break-all;">${data.record}</code>
                    </div>`;
                    } else {
                        resultBox.innerHTML = 'No DKIM record found.';
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
add_shortcode('dkim_checker', 'dkim_record_checker_shortcode');
