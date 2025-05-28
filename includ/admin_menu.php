<?php
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
    // Handle form submission
    if (isset($_POST['domain_tools_save_styles'])) {
        update_option('domain_tools_bg_color', sanitize_text_field($_POST['bg_color']));
        update_option('domain_tools_font_color', sanitize_text_field($_POST['font_color']));
        update_option('domain_tools_font_size', sanitize_text_field($_POST['font_size']));
        echo '<div class="updated"><p>Styles updated successfully!</p></div>';
    }

    // Get saved values or set defaults
    $bg_color = get_option('domain_tools_bg_color', '#fff');
    $font_color = get_option('domain_tools_font_color', '#000000');
    $font_size = get_option('domain_tools_font_size', '16px');
    ?>

    <div class="wrap">
        <h1>📦 Mail Server Tools – Settings</h1>

        <h2 class="nav-tab-wrapper">
            <a href="#shortcodes" class="nav-tab nav-tab-active">Shortcodes</a>
            <a href="#styles" class="nav-tab">Styles</a>
        </h2>

        <div id="shortcodes" class="tab-content" style="display: block;">
            <p>Use the following shortcodes to display DNS and IP utilities on your pages or posts.</p>
            <div class="domain-wrapper">
                <!-- repeat domain-tool-boxes here -->
                <!-- (Same content from your original shortcode boxes) -->
                <div class="domain-tool-box"><h2>🛡️ SPF Checker</h2><p>Displays the SPF record for a domain.</p><code>[spf_checker]</code></div>
                <div class="domain-tool-box"><h2>📨 MX Record Checker</h2><p>Displays the MX records for a domain.</p><code>[mx_checker]</code></div>
                <div class="domain-tool-box"><h2>🔒 DKIM Record Checker</h2><p>Displays the DKIM record for a domain (selector required).</p><code>[dkim_checker]</code></div>
                <div class="domain-tool-box"><h2>📧 SMTP Test</h2><p>Tests if the domain's SMTP server is reachable.</p><code>[smtp_checker]</code></div>
                <div class="domain-tool-box"><h2>🛑 DMARC Record</h2><p>Displays the DMARC record if available.</p><code>[dmarc_checker]</code></div>
                <div class="domain-tool-box"><h2>📡 A Record (IPv4)</h2><p>Fetches A records for a domain.</p><code>[a_record_checker]</code></div>
                <div class="domain-tool-box"><h2>🌐 TXT Records</h2><p>Displays all TXT records.</p><code>[txt_checker]</code></div>
                <div class="domain-tool-box"><h2>🔍 IP & Geo Lookup</h2><p>Geolocation and ISP data based on IP (with map).</p><code>[ip_checker]</code></div>
                <div class="domain-tool-box"><h2>🔐 SSL Certificate Checker</h2><p>Fetches SSL expiration and certificate info.</p><code>[ssl_checker]</code></div>
                <div class="domain-tool-box"><h2>📧 Email Deliverability Checker</h2><code>[email_deliverability_checker]</code></div>
                <div class="domain-tool-box"><h2>📧 Blacklist Check Tool</h2><code>[blacklist_checker]</code></div>
            </div>
        </div>

        <div id="styles" class="tab-content" style="display: none;">
            <form method="post">
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
    </div>

    <script>
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
                document.querySelectorAll('.tab-content').forEach(tc => tc.style.display = 'none');
                tab.classList.add('nav-tab-active');
                document.querySelector(tab.getAttribute('href')).style.display = 'block';
            });
        });
    </script>

  
<?php
}
