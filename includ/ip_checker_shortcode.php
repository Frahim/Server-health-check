<?php
function ip_checker_shortcode()
{
    ob_start(); ?>
    <form id="ip-check-form">
         <div class="search">            
             <input type="text" class="searchTerm" id="ip-input" placeholder="Enter IP or Domain" required>
        <button type="submit" class="searchButton">Check IP</button>
        </div>
       
    </form>
    <div class="resultwrwpper">
        <div class="grid-colamn">
            <div id="ip-error" style="color: red; display: none;"></div>
            
            <div id="map" style="height: 300px; margin-top: 20px; display: none;"></div>
            <div id="ip-result" style="margin-top: 15px;"></div>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

    <script>
        let myMap;

        document.getElementById('ip-check-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const input = document.getElementById('ip-input').value.trim();
            const isIP = /^(\d{1,3}\.){3}\d{1,3}$/.test(input);
            const errorDiv = document.getElementById('ip-error');
            const resultDiv = document.getElementById('ip-result');
            const mapDiv = document.getElementById('map');
            errorDiv.style.display = 'none';
            resultDiv.textContent = 'Loading...';
            mapDiv.style.display = 'none';

            const fetchIP = ip => {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ip_checker_ajax&ip=' + encodeURIComponent(ip))
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            errorDiv.textContent = data.error || 'Unknown error';
                            errorDiv.style.display = 'block';
                            resultDiv.textContent = '';
                            return;
                        }

                        const d = data.ip_details;
                       
                        // Map
                        if (d.loc) {
                            const [lat, lon] = d.loc.split(',').map(Number);
                            if (!isNaN(lat) && !isNaN(lon)) {
                                if (myMap) myMap.remove();
                                mapDiv.style.display = 'block';
                                myMap = L.map('map').setView([lat, lon], 13);
                                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    maxZoom: 19,
                                    attribution: '&copy; OpenStreetMap contributors'
                                }).addTo(myMap);
                                L.marker([lat, lon]).addTo(myMap)
                                    .bindPopup(`<b>${d.city}</b><br>${d.ip}`).openPopup();
                                setTimeout(() => {
                                    myMap.invalidateSize();
                                }, 100);
                            }
                        }
                         let html = `<div class="innerwrapper">
                        <p><strong>IP:</strong> ${d.ip}</p>
                        <p><strong>Hostname:</strong> ${d.hostname || 'N/A'}</p>
                        <p><strong>City:</strong> ${d.city || 'N/A'}</p>
                        <p><strong>Region:</strong> ${d.region || 'N/A'}</p>
                        <p><strong>Country:</strong> ${d.country || 'N/A'}</p>
                        <p><strong>Location:</strong> ${d.loc || 'N/A'}</p>
                        <p><strong>Postal:</strong> ${d.postal || 'N/A'}</p>
                        <p><strong>Timezone:</strong> ${d.timezone || 'N/A'}</p>
                        <p><strong>Org:</strong> ${d.org || 'N/A'}</p></div>`;
                        resultDiv.innerHTML = html;

                    })
                    .catch(err => {
                        errorDiv.textContent = 'Failed to fetch IP details.';
                        errorDiv.style.display = 'block';
                        resultDiv.textContent = '';
                    });
            };

            if (!isIP) {
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=resolve_ip_ajax&domain=' + encodeURIComponent(input))
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            errorDiv.textContent = 'Domain resolution failed: ' + data.error;
                            errorDiv.style.display = 'block';
                            resultDiv.textContent = '';
                        } else {
                            fetchIP(data.ip);
                        }
                    });
            } else {
                fetchIP(input);
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('ip_checker', 'ip_checker_shortcode');


add_action('wp_ajax_resolve_ip_ajax', 'handle_resolve_ip');
add_action('wp_ajax_nopriv_resolve_ip_ajax', 'handle_resolve_ip');

function handle_resolve_ip()
{
    header('Content-Type: application/json');
    $domain = $_GET['domain'] ?? '';
    $ip = gethostbyname($domain);
    if ($ip === $domain) {
        echo json_encode(['error' => 'Could not resolve domain']);
    } else {
        echo json_encode(['ip' => $ip]);
    }
    wp_die();
}


add_action('wp_ajax_ip_checker_ajax', 'handle_ip_info');
add_action('wp_ajax_nopriv_ip_checker_ajax', 'handle_ip_info');

function handle_ip_info()
{
    header('Content-Type: application/json');

    $ip = $_GET['ip'] ?? '';
    $api_token = 'd38a0d4602830d';

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        echo json_encode(['success' => false, 'error' => 'Invalid IP address']);
        wp_die();
    }

    $url = "https://ipinfo.io/{$ip}/json?token={$api_token}";
    $response = wp_remote_get($url, ['timeout' => 5]);

    if (is_wp_error($response)) {
        echo json_encode(['success' => false, 'error' => $response->get_error_message()]);
        wp_die();
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['error'])) {
        echo json_encode(['success' => false, 'error' => $data['error']['message'] ?? 'IP lookup failed']);
    } else {
        echo json_encode(['success' => true, 'ip_details' => $data]);
    }
    wp_die();
}
