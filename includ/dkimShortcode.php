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


function dkim_record_checker_shortcode($atts = [])
{

     $atts = shortcode_atts([
        'img' => '',
		'img2' => '',
        'url' => ''
    ], $atts);

    ob_start(); ?>
 <form id="dkim-check-form" class="formwrapper">
        <h2 class="title">DKIM Record Checker</h2>        
        <div class="search">           
             <input type="text" id="dkim-selector" class="searchTerm" placeholder="Selector (e.g., default)" required>
            <input type="text" id="dkim-domain" class="searchTerm" placeholder="Domain (e.g., example.com)" required>
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="dkim-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="dkim-result" class="resultwrapper" style="margin-top: 20px;"></div>
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
        document.getElementById('dkim-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const selector = document.getElementById('dkim-selector').value;
            const domain = document.getElementById('dkim-domain').value;
            const resultBox = document.getElementById('dkim-result');
             const popup = document.getElementById('dkim-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = 'Checking...';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_dkim_record&selector=' + encodeURIComponent(selector) + '&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                    } else if (data.record) {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <h3 style="margin-top: 0;">DKIM Record for <strong>${domain}</strong></h3> 
                        <div class="dkim-result"><code style="word-break:break-all;">${data.record}</code></div>
                    </div>`;
                    } else {
                        resultBox.innerHTML = 'No DKIM record found.';
                    }
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });

         // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('dkim-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('dkim-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('dkim_checker', 'dkim_record_checker_shortcode');
