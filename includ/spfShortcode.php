<?php
// FILE: Your SPF Checker shortcode file (or plugin file/functions.php)

// Your existing AJAX handler for SPF (unchanged from your provided code)
add_action('wp_ajax_check_spf_record', 'handle_spf_ajax');
add_action('wp_ajax_nopriv_check_spf_record', 'handle_spf_ajax');

function handle_spf_ajax()
{
    header('Content-Type: application/json');

    $domain = $_GET['domain'] ?? '';
    if (empty($domain)) {
        echo json_encode(['error' => 'Domain is required']);
        wp_die();
    }

    // Basic domain validation
    if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
        echo json_encode(['error' => 'Invalid domain format.']);
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

function spf_checker_shortcode()
{
    ob_start(); // Start output buffering
    ?>

    <form id="spf-check-form" class="formwrapper">
        <h2 class="title">SPF Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="spf-domain" class="searchTerm" placeholder="e.g., example.com" required>
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="spf-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closeSpfPopupBtn">&times;</span>
            <div id="spf-result" class="resultwrapper" style="margin-top: 20px;"></div>

           
                <?php
                // --- Dynamic AdSense Code Injection Here ---
                // Fetch the stored AdSense IDs for SPF Checker
                $adsense_options = get_option('domain_tools_spf_adsense_settings', []);
                $publisher_id = $adsense_options['publisher_id'] ?? '';
                $ad_slot_id = $adsense_options['ad_slot_id'] ?? '';

                // Only display AdSense code if IDs are configured and valid
                if (!empty($publisher_id) && preg_match('/^ca-pub-\d{16}$/', $publisher_id) &&
                    !empty($ad_slot_id) && preg_match('/^\d{10}$/', $ad_slot_id)) {
                    ?>
                     <div class="adds googleaddscode"> 
                    <div style="text-align: center; margin: 10px auto;">
                        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($publisher_id); ?>" crossorigin="anonymous"></script>
                        <ins class="adsbygoogle"
                             style="display:block"
                             data-ad-client="<?php echo esc_attr($publisher_id); ?>"
                             data-ad-slot="<?php echo esc_attr($ad_slot_id); ?>"
                             data-ad-format="auto"
                             data-full-width-responsive="true"></ins>
                        <script>
                             (adsbygoogle = window.adsbygoogle || []).push({});
                        </script>
                    </div>
                     </div>
                    <?php
                } else {
                    // Optionally display nothing or a generic placeholder for regular users
                    // echo '';
                }
                // --- END Dynamic AdSense Code ---
                ?>
           
        </div>
    </div>


    <script>
        document.getElementById('spf-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('spf-domain').value.trim(); // Trim whitespace
            const resultBox = document.getElementById('spf-result');
            const popup = document.getElementById('spf-result-popup');

            // Show the popup and set initial checking message
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10" style="text-align: center;">Checking...</div>'; // Center the message

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_spf_record&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red; text-align: center;">Error: ${data.error}</div>`; // Center error
                        return;
                    }

                    if (data.record) {
                        resultBox.innerHTML = `<div class="innerwrapper" style="text-align: center;">
                            <h3 style="margin-top: 0;">SPF Record for <strong>${domain}</strong></h3>
                            <strong>SPF Record:</strong> <span style="word-wrap: break-word; display: block; max-width: 100%;">${data.record}</span>
                        </div>`;
                    } else {
                        resultBox.innerHTML = `<div class="innerwrapper" style="text-align: center;">No SPF record found for <strong>${domain}</strong>.</div>`;
                    }
                })
                .catch(err => {
                    resultBox.innerHTML = `<div style="color:red; text-align: center;">An unexpected error occurred. Please try again.</div>`; // More user-friendly error
                    console.error("SPF fetch error:", err); // Log full error to console for debugging
                });
        });

        // Close popup functionality (Unique ID for this specific popup)
        document.getElementById('closeSpfPopupBtn').addEventListener('click', function() {
            document.getElementById('spf-result-popup').style.display = 'none';
        });

        // Close popup when clicking outside of the content (Unique ID for this specific popup)
        document.getElementById('spf-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('spf_checker', 'spf_checker_shortcode');