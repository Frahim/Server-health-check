<?php
/* Template Name: DNS Security Checker */
get_header();
?>
<!-- Include Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Include Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="bodywraper">
    <div class="dns-checker-wrapper mail-server-health-page-wrapper">
        <div id="domain-input-section" class="domain-input-section">
            <h2 class="form-title text-center">Mail Server Health Checker</h2>
            <form id="dns-check-form" class="domain-input-form">
                <input class="domain-input-field" type="text" id="domainInput" placeholder="Type your domain (e.g. example.com)" required>
                <button class="scan-domain-button" type="submit">Scan</button>
            </form>
        </div>
        <div id="dns-results" style="display:none;">
            <h3>Results for <span id="scanned-domain"></span></h3>
            <div class="resultgrid">
                <div id="a-records" class="security-check-item"></div>
                <div id="mx-records" class="security-check-item"></div>
                <div id="spf-records" class="security-check-item"></div>
                <div id="txt-records" class="security-check-item"></div>
                <div id="dkim-records" class="security-check-item"></div>
                <div id="dmarc-records" class="security-check-item"></div>
                <div id="smtp-records" class="security-check-item"></div>
                <div id="ssl-records" class="security-check-item"></div>

                <div id="ip-info" class="security-check-item">                   
                    <div id="ip-details"></div>
                    <div id="ip-map" style="height: 300px; width: 400px; display: none; margin-top: 20px; border-radius: 8px; overflow: hidden;"></div>
                </div>

            </div>
            <div class="result-actions">
                <button id="backToScan" class="scan-again-button">Scan Again</button>
            </div>
        </div>
    </div>
</div>

<script>
   document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dns-check-form');
    const domainInput = document.getElementById('domainInput');
    const scannedDomain = document.getElementById('scanned-domain');
    const resultSection = document.getElementById('dns-results');
    const inputSection = document.getElementById('domain-input-section');
    const backBtn = document.getElementById('backToScan');

    // These correspond to the variables in your `fetchIP` snippet
    const ipDetailsDiv = document.getElementById('ip-details'); // This will act as resultDiv
    const ipMapDiv = document.getElementById('ip-map');       // This will act as mapDiv
    let myMap; // For Leaflet instance

    // Removed the need for a separate errorDiv for IP info, will show error in ipDetailsDiv
    // const errorDiv = document.getElementById('ip-error-display'); // You might need to add this div if you want separate error display

    const showLoader = el => el.innerHTML = '<div class="loader"></div>';
    const formatRows = rows => rows.map(r => `<div class="row"><div class="column"><p>${r.label}</p></div><div class="column"><p>${r.value}</p></div></div>`).join('');

    // Define the fetchIP function in this scope
    const fetchIP = (ipToFetch, originalInputForDisplay) => {
        showLoader(ipDetailsDiv); // Show loader while fetching IP details
        ipMapDiv.style.display = 'none'; // Hide map while loading new data
        if (myMap) { // Clean up existing map if any
            myMap.remove();
            myMap = null;
        }

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=ip_checker_ajax&ip=' + encodeURIComponent(ipToFetch))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    ipDetailsDiv.innerHTML = `<p style="color:red;">IP Error: ${data.error || 'Unknown error'}</p>`;
                    ipMapDiv.style.display = 'none';
                    return;
                }

                const d = data.ip_details;

                // Display IP info
                let html = `<div class="innerwrapper">
                    <h4>IP Info for <strong>${originalInputForDisplay}</strong></h4>
                    <div class="row"><strong>IP:</strong> ${d.ip || 'N/A'}</div>
                    <div class="row"><strong>Host:</strong> ${d.hostname || 'N/A'}</div>
                    <div class="row"><strong>City:</strong> ${d.city || 'N/A'}</div>
                    <div class="row"><strong>Region:</strong> ${d.region || 'N/A'}</div>
                    <div class="row"><strong>Country:</strong> ${d.country || 'N/A'}</div>
                    <div class="row"><strong>Location:</strong> ${d.loc || 'N/A'}</div>
                    <div class="row"><strong>Postal:</strong> ${d.postal || 'N/A'}</div>
                    <div class="row"><strong>Timezone:</strong> ${d.timezone || 'N/A'}</div>
                    <div class="row"><strong>Org:</strong> ${d.org || 'N/A'}</div>
                </div>`;
                ipDetailsDiv.innerHTML = html;

                // Map rendering
                if (d.loc) {
                    const [lat, lon] = d.loc.split(',').map(Number);
                    if (!isNaN(lat) && !isNaN(lon)) {
                        ipMapDiv.style.display = 'block';
                        // Map instance already removed at the start of fetchIP if it existed
                        myMap = L.map('ip-map').setView([lat, lon], 13); // Changed 'map' to 'ip-map'
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { // Changed tile URL
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(myMap);
                        L.marker([lat, lon]).addTo(myMap)
                            .bindPopup(`<b>${d.city || ''}</b><br>${d.ip}`).openPopup(); // Added || '' for city
                        setTimeout(() => {
                            myMap.invalidateSize();
                        }, 200); // Increased delay slightly for safety
                    } else {
                        ipMapDiv.style.display = 'none'; // Hide map if lat/lon invalid
                    }
                } else {
                    ipMapDiv.style.display = 'none'; // Hide map if no location data
                }
            })
            .catch(err => {
                ipDetailsDiv.innerHTML = `<p style="color:red;">Failed to fetch IP details: ${err.message || err}</p>`; // More robust error message
                ipMapDiv.style.display = 'none';
                if (myMap) {
                    myMap.remove(); // Remove map instance on error
                    myMap = null;
                }
                console.error("Fetch IP details error: ", err); // Log the actual error
            });
    };

    form.addEventListener('submit', e => {
        e.preventDefault();
        const domain = domainInput.value.trim();
        if (!domain) return alert("Please enter a domain");

        scannedDomain.innerText = domain;
        inputSection.style.display = 'none';
        resultSection.style.display = 'block';

        // Clear previous IP details and map before starting new checks
        ipDetailsDiv.innerHTML = '';
        ipMapDiv.style.display = 'none';
        if (myMap) {
            myMap.remove();
            myMap = null;
        }

        const sections = [{
                id: 'a-records',
                action: 'check_a_record'
            },
            {
                id: 'mx-records',
                action: 'check_mx_record'
            },
            {
                id: 'spf-records',
                action: 'check_spf_record'
            },
            {
                id: 'txt-records',
                action: 'check_txt_record'
            },
            {
                id: 'dkim-records',
                action: 'check_dkim_record&selector=default'
            },
            {
                id: 'dmarc-records',
                action: 'check_dmarc_record'
            },
            {
                id: 'smtp-records',
                action: 'check_smtp_record'
            },
            {
                id: 'ssl-records',
                action: 'check_ssl_record'
            }
        ];

        sections.forEach(s => {
            const el = document.getElementById(s.id);
            showLoader(el);
            fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=${s.action}&domain=${domain}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) throw data.error;
                    let rows = [];
                    switch (s.id) {
                        case 'a-records':
                            data.forEach(d => rows.push({
                                label: 'IP',
                                value: d.value
                            }, {
                                label: 'TTL',
                                value: d.ttl
                            }));
                            break;
                        case 'mx-records':
                            data.records.forEach(r =>
                                rows.push({
                                    label: 'Priority',
                                    value: r.priority
                                }, {
                                    label: 'MX',
                                    value: r.mx_record
                                }, {
                                    label: 'IP',
                                    value: r.ip
                                }, {
                                    label: 'Org',
                                    value: r.organization
                                })
                            );
                            break;
                        case 'spf-records':
                            rows.push({
                                label: 'SPF',
                                value: data.record
                            });
                            break;
                        case 'txt-records':
                            if (Array.isArray(data.records) && data.records.length > 0) {
                                data.records.forEach(txt => rows.push({
                                    label: 'TXT',
                                    value: txt
                                }));
                            } else {
                                rows.push({
                                    label: 'TXT',
                                    value: data.record || 'No TXT records found'
                                });
                            }
                            break;
                        case 'dkim-records':
                            rows.push({
                                label: 'DKIM',
                                value: data.record
                            });
                            break;
                        case 'dmarc-records':
                            rows.push({
                                label: 'DMARC',
                                value: data.record
                            });
                            break;
                        case 'smtp-records':
                            rows.push({
                                label: 'SMTP',
                                value: data.status
                            });
                            break;
                        case 'ssl-records':
                            rows.push({
                                label: 'Issuer',
                                value: data.issuer
                            }, {
                                label: 'Valid From',
                                value: data.validFrom
                            }, {
                                label: 'Valid To',
                                value: data.validTo
                            });
                            break;
                    }
                    el.innerHTML = `<h4>${s.id.replace(/-/g, ' ').toUpperCase()}</h4>` + formatRows(rows);
                })
                .catch(err => {
                    document.getElementById(s.id).innerHTML = `<p style="color:red;">Error: ${err}</p>`;
                });
        });

        // IP Info + Map Section - Now using the fetchIP function
        // Determine if the input is an IP or a domain
        const isIP = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(domain); // Basic regex for IPv4

        if (!isIP) {
            fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=resolve_ip_ajax&domain=${domain}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        ipDetailsDiv.innerHTML = `<p style="color:red;">Domain resolution failed: ${data.error}</p>`;
                        ipMapDiv.style.display = 'none';
                    } else {
                        fetchIP(data.ip, domain); // Pass the resolved IP and original domain as display
                    }
                })
                .catch(err => {
                    ipDetailsDiv.innerHTML = `<p style="color:red;">Failed to resolve domain: ${err.message || err}</p>`;
                    ipMapDiv.style.display = 'none';
                    console.error("Domain resolution fetch error: ", err);
                });
        } else {
            fetchIP(domain, domain); // If input is an IP, pass it directly
        }
    });

    // Back Button
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            resultSection.style.display = 'none';
            inputSection.style.display = 'block';
            form.reset();
            [...document.querySelectorAll('.security-check-item')].forEach(d => d.innerHTML = '');
            ipMapDiv.style.display = 'none';
            if (myMap) {
                myMap.remove();
                myMap = null;
            }
            ipDetailsDiv.innerHTML = ''; // Clear IP details
        });
    }
});
</script>

<?php get_footer(); ?>