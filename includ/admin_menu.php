<?php
// FILE: Your main plugin file or functions.php

// (Your existing admin menu registration remains unchanged)
add_action('admin_menu', 'domain_tools_add_admin_menu');

function domain_tools_add_admin_menu()
{
    add_menu_page(
        'Mail Server Tools Shortcodes',
        'Mail Server Tools',
        'manage_options',
        'domain-tools-settings',
        'domain_tools_settings_page',
        'dashicons-admin-network',
        80
    );
}

function domain_tools_settings_page()
{
    // Handle form submission for Styles
    if (isset($_POST['domain_tools_save_styles']) && check_admin_referer('domain_tools_save_styles_nonce', 'domain_tools_styles_nonce_field')) {
        update_option('domain_tools_bg_color', sanitize_text_field($_POST['bg_color']));
        update_option('domain_tools_font_color', sanitize_text_field($_POST['font_color']));
        update_option('domain_tools_font_size', sanitize_text_field($_POST['font_size']));
        echo '<div class="updated"><p>Styles updated successfully!</p></div>';
    }

    // --- NEW: Handle form submission for SPF Checker AdSense ---
    if (isset($_POST['domain_tools_save_spf_adsense']) && check_admin_referer('domain_tools_save_spf_adsense_nonce', 'domain_tools_spf_adsense_nonce_field')) {
        $spf_adsense_publisher_id = isset($_POST['spf_adsense_publisher_id']) ? sanitize_text_field($_POST['spf_adsense_publisher_id']) : '';
        $spf_adsense_ad_slot_id = isset($_POST['spf_adsense_ad_slot_id']) ? sanitize_text_field($_POST['spf_adsense_ad_slot_id']) : '';

        // Basic validation for AdSense IDs
        if (!empty($spf_adsense_publisher_id) && !preg_match('/^ca-pub-\d{16}$/', $spf_adsense_publisher_id)) {
            echo '<div class="error"><p>Error: Invalid AdSense Publisher ID format for SPF Checker. It should start with "ca-pub-" and have 16 digits.</p></div>';
            $spf_adsense_publisher_id = ''; // Clear invalid ID
        }
        if (!empty($spf_adsense_ad_slot_id) && !preg_match('/^\d{10}$/', $spf_adsense_ad_slot_id)) {
            echo '<div class="error"><p>Error: Invalid AdSense Ad Slot ID format for SPF Checker. It should be 10 digits.</p></div>';
            $spf_adsense_ad_slot_id = ''; // Clear invalid ID
        }

        update_option('domain_tools_spf_adsense_settings', [
            'publisher_id' => $spf_adsense_publisher_id,
            'ad_slot_id'   => $spf_adsense_ad_slot_id,
        ]);
        if (empty(get_settings_errors())) { // Only show success if no errors were added
            echo '<div class="updated"><p>SPF Checker AdSense settings updated successfully!</p></div>';
        }
    }
    // --- END NEW ADSNESE HANDLER ---


    // Get saved values or set defaults for Styles
    $bg_color = get_option('domain_tools_bg_color', '#fff');
    $font_color = get_option('domain_tools_font_color', '#000000');
    $font_size = get_option('domain_tools_font_size', '16px');

    // --- NEW: Get saved values for SPF Checker AdSense ---
    $spf_adsense_options = get_option('domain_tools_spf_adsense_settings', []);
    $current_spf_adsense_publisher_id = $spf_adsense_options['publisher_id'] ?? '';
    $current_spf_adsense_ad_slot_id = $spf_adsense_options['ad_slot_id'] ?? '';
    // --- END NEW ADSNESE RETRIEVAL ---
    ?>

    <div class="wrap">
        <h1>📦 Mail Server Tools – Settings</h1>

        <h2 class="nav-tab-wrapper">
            <a href="#shortcodes" class="nav-tab nav-tab-active">Shortcodes</a>
            <a href="#styles" class="nav-tab">Styles</a>
            <a href="#spf-adsense" class="nav-tab">SPF AdSense</a> </h2>

        <div id="shortcodes" class="tab-content" style="display: block;">
            <p>Use the following shortcodes to display DNS and IP utilities on your pages or posts.</p>
            <div class="domain-wrapper" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🛡️ SPF Checker</h2><p>Displays the SPF record for a domain.</p><code>[spf_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>📨 MX Record Checker</h2><p>Displays the MX records for a domain.</p><code>[mx_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🔒 DKIM Record Checker</h2><p>Displays the DKIM record for a domain (selector required).</p><code>[dkim_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>📧 SMTP Test</h2><p>Tests if the domain's SMTP server is reachable.</p><code>[smtp_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🛑 DMARC Record</h2><p>Displays the DMARC record if available.</p><code>[dmarc_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>📡 A Record (IPv4)</h2><p>Fetches A records for a domain.</p><code>[a_record_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🌐 TXT Records</h2><p>Displays all TXT records.</p><code>[txt_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🔍 IP & Geo Lookup</h2><p>Geolocation and ISP data based on IP (with map).</p><code>[ip_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>🔐 SSL Certificate Checker</h2><p>Fetches SSL expiration and certificate info.</p><code>[ssl_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>📧 Email Deliverability Checker</h2><code>[email_deliverability_checker]</code></div>
                <div class="domain-tool-box" style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background-color: #f9f9f9;"><h2>📧 Blacklist Check Tool</h2><code>[blacklist_checker]</code></div>
            </div>
        </div>

        <div id="styles" class="tab-content" style="display: none;">
            <form method="post">
                <?php wp_nonce_field('domain_tools_save_styles_nonce', 'domain_tools_styles_nonce_field'); ?>
                <h2>🎨 Style Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bg_color">Background Color</label></th>
                        <td><input type="color" name="bg_color" id="bg_color" value="<?php echo esc_attr($bg_color); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="font_color">Font Color</label></th>
                        <td><input type="color" name="font_color" id="font_color" value="<?php echo esc_attr($font_color); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="font_size">Font Size</label></th>
                        <td><input type="text" name="font_size" id="font_size" value="<?php echo esc_attr($font_size); ?>"> (e.g. 14px, 1em)</td>
                    </tr>
                </table>
                <?php submit_button('Save Styles', 'primary', 'domain_tools_save_styles'); ?>
            </form>
        </div>

        <div id="spf-adsense" class="tab-content" style="display: none;">
            <form method="post">
                <?php wp_nonce_field('domain_tools_save_spf_adsense_nonce', 'domain_tools_spf_adsense_nonce_field'); ?>
                <h2>📊 SPF Checker Google AdSense Settings</h2>
                <p>Enter your Google AdSense Publisher ID and Ad Slot ID specifically for the <strong>SPF Checker</strong> tool's pop-up.</p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="spf_adsense_publisher_id">AdSense Publisher ID</label></th>
                        <td>
                            <input type="text" id="spf_adsense_publisher_id" name="spf_adsense_publisher_id" value="<?php echo esc_attr($current_spf_adsense_publisher_id); ?>" class="regular-text" placeholder="e.g., ca-pub-1234567890123456">
                            <p class="description">Your Google AdSense Publisher ID (starts with <code>ca-pub-</code>).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="spf_adsense_ad_slot_id">AdSense Ad Slot ID</label></th>
                        <td>
                            <input type="text" id="spf_adsense_ad_slot_id" name="spf_adsense_ad_slot_id" value="<?php echo esc_attr($current_spf_adsense_ad_slot_id); ?>" class="regular-text" placeholder="e.g., 1234567890">
                            <p class="description">The Ad Slot ID for your responsive display ad unit (10 digits).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save SPF AdSense Settings', 'primary', 'domain_tools_save_spf_adsense'); ?>
            </form>
        </div>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navTabs = document.querySelectorAll('.nav-tab');
            const tabContents = document.querySelectorAll('.tab-content');

            function showTab(tabId) {
                navTabs.forEach(t => t.classList.remove('nav-tab-active'));
                tabContents.forEach(tc => tc.style.display = 'none');

                const activeTab = document.querySelector(`.nav-tab[href="#${tabId}"]`);
                const activeContent = document.getElementById(tabId);

                if (activeTab && activeContent) {
                    activeTab.classList.add('nav-tab-active');
                    activeContent.style.display = 'block';
                }
            }

            // Check URL hash on page load to show the correct tab
            const initialTab = window.location.hash ? window.location.hash.substring(1) : 'shortcodes';
            showTab(initialTab);

            navTabs.forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('href').substring(1); // Remove '#'
                    showTab(tabId);

                    // Update URL hash to remember active tab
                    window.location.hash = tabId;
                });
            });
        });
    </script>

<?php
}