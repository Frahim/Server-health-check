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


function dmarc_record_checker_shortcode($atts = []) { 
    $atts = shortcode_atts([
        'img' => '',
		'img2' => '',
        'url' => ''
    ], $atts);   

    ob_start(); ?>
     <form id="dmarc-check-form" class="formwrapper">
        <h2 class="title">DMARC Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="dmarc-domain" class="searchTerm" placeholder="e.g., example.com">
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="dmarc-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
             <div id="dmarc-result" class="resultwrapper" style="margin-top: 20px;"></div>
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
        document.getElementById('dmarc-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('dmarc-domain').value;
            const resultBox = document.getElementById('dmarc-result');
             const popup = document.getElementById('dmarc-result-popup');
            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';
           

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_dmarc_record&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                        return;
                    }

                    if (data.record) {
                        resultBox.innerHTML = `<div class="innerwrapper">
                        <h3 style="margin-top: 0;">DMARC Records for <strong>${domain}</strong></h3>                       
                        <div><code style="word-break:break-all;">${data.record}</code></div>
                    </div>`;
                    } else {
                        resultBox.innerHTML = `<div>No DMARC record found.</div>`;
                    }
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });

         // Close popup functionality
        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('dmarc-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content
        document.getElementById('dmarc-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('dmarc_checker', 'dmarc_record_checker_shortcode');
