<?php
// Register AJAX handlers
add_action('wp_ajax_check_txt_record', 'handle_txt_ajax');
add_action('wp_ajax_nopriv_check_txt_record', 'handle_txt_ajax');

function handle_txt_ajax()
{
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


function txt_checker_shortcode()
{
    ob_start(); ?>

    <form id="txt-check-form" class="formwrapper">
        <h2 class="title">TXT Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="txt-domain" class="searchTerm" placeholder="e.g., example.com">
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="txt-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="txt-result" class="resultwrapper" style="margin-top: 20px;"></div>
            <div class="adds googleaddscode">
                <div class="ad-wrapper" style="margin-top: 20px;">
                    <ins class="adsbygoogle"
                        style="display:block; text-align:center;"
                        data-ad-layout="in-article"
                        data-ad-format="fluid"
                        data-ad-client="ca-pub-1234567890123456"
                        data-ad-slot="9876543210"></ins>
                    <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        document.getElementById('txt-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('txt-domain').value;
            const resultBox = document.getElementById('txt-result');
            const popup = document.getElementById('txt-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';

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
                        output += `<li style="margin-bottom: 10px;">TXT: <strong>${record.txt.join ? record.txt.join(' ') : record.txt}</strong></li>`;
                    });

                    output += '</ul></div>';
                    resultBox.innerHTML = output;
                })
                .catch(error => {
                    resultBox.innerHTML = 'Error: ' + error;
                });
        });

        // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('txt-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('txt-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('txt_checker', 'txt_checker_shortcode');
