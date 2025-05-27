<?php
// Register AJAX handlers
add_action('wp_ajax_check_txt_record', 'handle_txt_ajax');
add_action('wp_ajax_nopriv_check_txt_record', 'handle_txt_ajax');

function handle_txt_ajax() {
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    $records = dns_get_record($domain, DNS_TXT);
    if ($records === false || empty($records)) {
        echo json_encode(['error' => 'Failed to retrieve TXT records']);
        wp_die();
    }

    echo json_encode($records);
    wp_die();
}


function txt_checker_shortcode() {
    ob_start(); ?>
    <form id="txt-check-form">
        <div class="search">
        <input type="text" id="txt-domain" class="searchTerm" placeholder="Enter domain (e.g., example.com)" required>
        <button type="submit" class="searchButton">Check TXT</button>
</div>
    </form>
    <div id="txt-result" class="resultwrwpper" style="margin-top: 20px;"></div>

    <script>
    document.getElementById('txt-check-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const domain = document.getElementById('txt-domain').value;
        const resultBox = document.getElementById('txt-result');
        resultBox.innerHTML = 'Checking...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_txt_record&domain=' + encodeURIComponent(domain))
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    return;
                }

                let output = `<div class="innerwrapper">
                    <h3>TXT Records for <strong>${domain}</strong></h3>
                    <ul style="list-style: none; padding: 0;">`;

                data.forEach(record => {
                    output += `<li style="margin-bottom: 10px;"><strong>TXT:</strong> ${record.txt.join ? record.txt.join(' ') : record.txt}</li>`;
                });

                output += '</ul></div>';
                resultBox.innerHTML = output;
            })
            .catch(error => {
                resultBox.innerHTML = 'Error: ' + error;
            });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('txt_checker', 'txt_checker_shortcode');
