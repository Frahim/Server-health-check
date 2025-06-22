<?php
add_shortcode('email_deliverability_checker', 'render_email_deliverability_checker');

function render_email_deliverability_checker()
{
    ob_start();
?>
    <form id="edc-form" class="formwrapper">
        <h2 class="title">Email Deliverability Checker</h2>
        <p>Enter your Email</p>
        <div class="search">
            <input type="email" name="edc_email" class="searchTerm" placeholder="e.g., hello@example.com" required>
            <button type="submit" class="searchButton twenty-one">Check</button>
        </div>
    </form>

    <div id="ed-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="ed-record-result" class="resultwrapper" style="margin-top: 20px;"></div>
            <div class="adds">
                <h3>Advertise display here</h3>
            </div>
        </div>
    </div>

    <script>
        const edcForm = document.getElementById("edc-form");
        const popup = document.getElementById("ed-result-popup");
        const resultBox = document.getElementById("ed-record-result");
        const closeBtn = document.getElementById("closePopupBtn");

        edcForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const email = edcForm.querySelector('[name="edc_email"]').value.trim();
            if (!email) return;

            resultBox.innerHTML = '<div class="p10">Checking…</div>';
            popup.style.display = "flex";

            fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=check_email_deliverability&email=${encodeURIComponent(email)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div class="p10" style="color: red;">${data.error}</div>`;
                        return;
                    }

                    resultBox.innerHTML = `
                        <div class="innerwrapper">
                            <h3>Results for <em>${data.email}</em></h3>
                            <ul>
                                <li><strong>Domain:</strong> ${data.domain}</li>
                                <li><strong>MX Record:</strong> ${data.MX ? '✅ Present' : '❌ Missing'}</li>
                                <li><strong>SPF Record:</strong> ${data.SPF ? '✅ Found' : '❌ Not Found'}</li>
                                <li><strong>DKIM Record:</strong> ${data.DKIM ? '✅ Found' : '❌ Not Found'}</li>
                                <li><strong>DMARC Record:</strong> ${data.DMARC ? '✅ Found' : '❌ Not Found'}</li>
                                <li><strong>SMTP Banner:</strong> ${data.SMTP || '❌ Could not connect'}</li>
                            </ul>
                        </div>
                    `;
                })
                .catch(err => {
                    resultBox.innerHTML = `<div class="p10" style="color:red;">Error: ${err}</div>`;
                });
        });

        closeBtn.addEventListener("click", () => popup.style.display = "none");
        popup.addEventListener("click", (e) => {
            if (e.target === popup) popup.style.display = "none";
        });
    </script>
<?php
    return ob_get_clean();
}

// Register AJAX handler
add_action('wp_ajax_check_email_deliverability', 'handle_email_deliverability_ajax');
add_action('wp_ajax_nopriv_check_email_deliverability', 'handle_email_deliverability_ajax');

function handle_email_deliverability_ajax()
{
    header('Content-Type: application/json');

    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        wp_die();
    }

    $result = edc_check_email_deliverability($email);
    $result['email'] = $email;

    echo json_encode($result);
    wp_die();
}

// Helper functions
function edc_check_email_deliverability($email)
{
    $domain = substr(strrchr($email, "@"), 1);
    $dns = [
        'MX'    => dns_get_record($domain, DNS_MX),
        'SPF'   => dns_get_record($domain, DNS_TXT),
        'DKIM'  => dns_get_record("default._domainkey.$domain", DNS_TXT),
        'DMARC' => dns_get_record("_dmarc.$domain", DNS_TXT),
    ];

    $spf_found = false;
    foreach ($dns['SPF'] as $record) {
        if (stripos($record['txt'], 'v=spf1') !== false) {
            $spf_found = true;
            break;
        }
    }

    return [
        'domain' => $domain,
        'MX'     => !empty($dns['MX']),
        'SPF'    => $spf_found,
        'DKIM'   => !empty($dns['DKIM']),
        'DMARC'  => !empty($dns['DMARC']),
        'SMTP'   => edc_smtp_banner($dns['MX'] ?? []),
    ];
}

function edc_smtp_banner($mxRecords)
{
    if (empty($mxRecords)) return false;

    $host = $mxRecords[0]['target'];
    $fp = @fsockopen($host, 25, $errno, $errstr, 5);
    if (!$fp) return false;

    $banner = fgets($fp, 1024);
    fclose($fp);
    return trim($banner);
}
