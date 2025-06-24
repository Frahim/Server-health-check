const domainInput = document.getElementById('domain');
const checkButton = document.getElementById('checkButton');
const resultsDiv = document.getElementById('results'); // Assuming you have a 'results' div

// MX Records
const mxRecordsDiv = document.getElementById('mxRecords');
const mxRecordsList = document.getElementById('mxRecordsList');
const mxRecordsError = document.getElementById('mxRecordsError');

// A Records
const aRecordsDiv = document.getElementById('aRecords');
const aRecordsList = document.getElementById('aRecordsList');
const aRecordsError = document.getElementById('aRecordsError');

// TXT Records
const txtRecordsDiv = document.getElementById('txtRecords');
const txtRecordsList = document.getElementById('txtRecordsList');
const txtRecordsError = document.getElementById('txtRecordsError');

// SPF Record
const spfRecordDiv = document.getElementById('spfRecord');
const spfRecordValue = document.getElementById('spfRecordValue');
const spfRecordError = document.getElementById('spfRecordError');

// DMARC Record
const dmarcRecordDiv = document.getElementById('dmarcRecord');
const dmarcRecordValue = document.getElementById('dmarcRecordValue');
const dmarcRecordError = document.getElementById('dmarcRecordError');

// SMTP Check
const smtpCheckDiv = document.getElementById('smtpCheck');
const smtpCheckResult = document.getElementById('smtpCheckResult');
const smtpCheckError = document.getElementById('smtpCheckError');

// IP Lookup
const ipLookupDiv = document.getElementById('ipLookup');
const ipLookupResult = document.getElementById('ipLookupResult');
const ipLookupError = document.getElementById('ipLookupError');

// Reverse IP Lookup
const reverseIpLookupDiv = document.getElementById('reverseIpLookup');
const reverseIpLookupResult = document.getElementById('reverseIpLookupResult');
const reverseIpLookupError = document.getElementById('reverseIpLookupError');

// CNAME Records
const cnameRecordsDiv = document.getElementById('cnameRecords');
const cnameRecordsList = document.getElementById('cnameRecordsList');
const cnameRecordsError = document.getElementById('cnameRecordsError');


// Map related elements
// Map related elements
const mapDiv = document.getElementById('map');
let myMap = null; // To hold the Leaflet map instance

// Update the resetErrors function (if you have one) to include map cleanup
function resetErrors() {
    // ... (your existing error resets)

    // Hide map and remove instance if it exists
    if (myMap) {
        myMap.remove(); // Destroy the map instance
        myMap = null;
    }
    mapDiv.style.display = 'none'; // Hide the map container
}

function resetErrors() {
    mxRecordsError.style.display = 'none';
    aRecordsError.style.display = 'none';
    txtRecordsError.style.display = 'none';
    spfRecordError.style.display = 'none';
    dmarcRecordError.style.display = 'none';
    domainError.style.display = 'none';
    smtpCheckError.style.display = 'none';
    ipLookupError.style.display = 'none';
    reverseIpLookupError.style.display = 'none';
    cnameRecordsError.style.display = 'none'; // Added CNAME error reset


    // Hide all result sections
    mxRecordsDiv.style.display = 'none';
    aRecordsDiv.style.display = 'none';
    txtRecordsDiv.style.display = 'none';
    spfRecordDiv.style.display = 'none';
    dmarcRecordDiv.style.display = 'none';
    smtpCheckDiv.style.display = 'none';
    ipLookupDiv.style.display = 'none';
    reverseIpLookupDiv.style.display = 'none';
    cnameRecordsDiv.style.display = 'none'; // Added CNAME div hide
}

function validateDomain(domain) {
    if (!domain) {
        domainError.textContent = "Please enter a domain name or IP address.";
        domainError.style.display = 'block';
        return false;
    }
    // Updated regex to be more permissive for domain names, allowing subdomains more flexibly
    const domainRegex = /^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i;
    const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!domainRegex.test(domain) && !ipRegex.test(domain)) {
        domainError.textContent = "Invalid domain name or IP address format.";
        domainError.style.display = 'block';
        return false;
    }
    return true;
}

checkButton.addEventListener('click', () => {
    resetErrors();
    const domain = domainInput.value.trim();
    if (!validateDomain(domain)) {
        return;
    }

    // Clear previous results for CNAME
    cnameRecordsList.innerHTML = '';


    // Show loading state for all
    mxRecordsDiv.style.display = 'block';
    aRecordsDiv.style.display = 'block';
    txtRecordsDiv.style.display = 'block';
    spfRecordDiv.style.display = 'block';
    dmarcRecordDiv.style.display = 'block';
    smtpCheckDiv.style.display = 'block';
    ipLookupDiv.style.display = 'block';
    reverseIpLookupDiv.style.display = 'block';
    cnameRecordsDiv.style.display = 'block'; // Added CNAME div show


    mxRecordsList.innerHTML = '<li>Loading...</li>';
    aRecordsList.innerHTML = '<li>Loading...</li>';
    txtRecordsList.innerHTML = '<li>Loading...</li>';
    spfRecordValue.textContent = 'Loading...';
    dmarcRecordValue.textContent = 'Loading...';
    smtpCheckResult.textContent = 'Loading...';
    ipLookupResult.textContent = 'Loading...';
    reverseIpLookupResult.textContent = 'Loading...';
    cnameRecordsList.innerHTML = '<li>Loading...</li>'; // Added CNAME loading state


   // Determine if the input is an IP address
const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
const isIPAddress = ipRegex.test(domain);
let ipAddressForLookup = domain; // Default to the input domain

if (!isIPAddress) {
    // If it's a domain, resolve it to an IP address first
    fetch(`/api/resolve_ip.php?domain=${encodeURIComponent(domain)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                ipLookupError.textContent = "Could not resolve domain to IP: " + data.error;
                ipLookupError.style.display = 'block';
                ipLookupResult.textContent = 'Error resolving domain.';
                // We will still perform other checks
            } else {
                ipAddressForLookup = data.ip; // Use the resolved IP for lookup
                // Now do the IP lookup using the new ip_lookup.php
                fetch(`/api/ip_lookup.php?ip=${encodeURIComponent(ipAddressForLookup)}`)
                    .then(response => response.json())
                   .then(data => {
                        if (data.success && data.ip_details) { // Check for success and existence of ip_details
                            ipLookupError.style.display = 'none'; // Clear any previous error
                            const details = data.ip_details; // Extract the ip_details object

                            // Build the HTML for display
                            let resultHTML = `<p><strong>IP Address:</strong> ${details.ip || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Hostname:</strong> ${details.hostname || 'N/A'}</p>`;
                            resultHTML += `<p><strong>City:</strong> ${details.city || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Region:</strong> ${details.region || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Country:</strong> ${details.country || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Location (Lat/Lon):</strong> ${details.loc || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Postal Code:</strong> ${details.postal || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Timezone:</strong> ${details.timezone || 'N/A'}</p>`;
                            resultHTML += `<p><strong>Organization:</strong> ${details.org || 'N/A'}</p>`; // ipinfo.io uses 'org' for organization
                            ipLookupResult.innerHTML = resultHTML;

                            // --- Map Display Logic (Leaflet) ---
                            // Ensure mapDiv is visible
                            mapDiv.style.display = 'block';

                            if (details.loc) {
                                const [lat, lon] = details.loc.split(',').map(Number);

                                if (!isNaN(lat) && !isNaN(lon)) {
                                    if (myMap) {
                                        myMap.remove(); // Remove previous map instance if it exists
                                    }
                                    // Initialize map centered on coordinates with zoom
                                    myMap = L.map('map').setView([lat, lon], 13);

                                    // Add OpenStreetMap tiles
                                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        maxZoom: 19,
                                        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(myMap);

                                    // Add a marker to the map
                                    L.marker([lat, lon]).addTo(myMap)
                                        .bindPopup(`<b>${details.city || 'Location'}</b><br>${details.ip || ''}`).openPopup();

                                    // Invalidate map size to ensure it renders correctly after being hidden
                                    // This is crucial if mapDiv was display:none; before
                                    setTimeout(() => {
                                        myMap.invalidateSize();
                                    }, 100);
                                } else {
                                    console.warn("Invalid latitude or longitude received:", details.loc);
                                    // If coordinates are invalid, hide the map
                                    mapDiv.style.display = 'none';
                                }
                            } else {
                                // If no location data, hide the map
                                mapDiv.style.display = 'none';
                            }
                            // --- End Map Display Logic ---

                        } else {
                            // Handle cases where success is false or ip_details is missing
                            ipLookupError.textContent = data.message || data.error || 'An unknown error occurred during IP lookup.';
                            ipLookupError.style.display = 'block';
                            ipLookupResult.textContent = 'Error fetching IP information.';
                            mapDiv.style.display = 'none'; // Hide map if there's an error
                        }
                    })
                    .catch(error => {
                        ipLookupError.textContent = 'Failed to perform IP lookup.';
                        ipLookupError.style.display = 'block';
                        ipLookupResult.textContent = 'Error fetching IP information.';
                        console.error('Error fetching IP information (via domain resolution):', error);
                        mapDiv.style.display = 'none'; // Hide map on fetch error
                    });

                // Perform reverse IP lookup (this block remains mostly the same)
                fetch(`/api/reverse_ip_lookup.php?ip=${encodeURIComponent(ipAddressForLookup)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            reverseIpLookupError.textContent = data.error;
                            reverseIpLookupError.style.display = 'block';
                            reverseIpLookupResult.textContent = 'Error fetching reverse IP lookup.';
                        } else {
                            reverseIpLookupResult.innerHTML = `<p><b>Hostname:</b> ${data.hostname || 'N/A'}</p>`;
                        }
                    })
                    .catch(error => {
                        reverseIpLookupError.textContent = 'Failed to perform reverse IP lookup.';
                        reverseIpLookupError.style.display = 'block';
                        reverseIpLookupResult.textContent = 'Error fetching reverse IP lookup.';
                        console.error('Error fetching reverse IP information:', error);
                    });
            }
        })
        .catch(error => {
            ipLookupError.textContent = 'Failed to resolve domain to IP address.';
            ipLookupError.style.display = 'block';
            ipLookupResult.textContent = 'Error resolving domain.';
            console.error('Error resolving domain (initial step):', error);
        });
} else {
    // If it is already an IP address, perform the IP and reverse IP lookup directly
    fetch(`/api/ip_lookup.php?ip=${encodeURIComponent(ipAddressForLookup)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.ip_details) { // Check for success and existence of ip_details
                ipLookupError.style.display = 'none'; // Clear any previous error
                const details = data.ip_details; // Extract the ip_details object

                // Build the HTML for display
                let resultHTML = `<p><strong>IP Address:</strong> ${details.ip || 'N/A'}</p>`;
                resultHTML += `<p><strong>Hostname:</strong> ${details.hostname || 'N/A'}</p>`;
                resultHTML += `<p><strong>City:</strong> ${details.city || 'N/A'}</p>`;
                resultHTML += `<p><strong>Region:</strong> ${details.region || 'N/A'}</p>`;
                resultHTML += `<p><strong>Country:</strong> ${details.country || 'N/A'}</p>`;
                resultHTML += `<p><strong>Location (Lat/Lon):</strong> ${details.loc || 'N/A'}</p>`;
                resultHTML += `<p><strong>Postal Code:</strong> ${details.postal || 'N/A'}</p>`;
                resultHTML += `<p><strong>Timezone:</strong> ${details.timezone || 'N/A'}</p>`;
                resultHTML += `<p><strong>Organization:</strong> ${details.org || 'N/A'}</p>`; // ipinfo.io uses 'org' for organization
                ipLookupResult.innerHTML = resultHTML;

            } else {
                // Handle cases where success is false or ip_details is missing
                ipLookupError.textContent = data.message || data.error || 'An unknown error occurred during IP lookup.';
                ipLookupError.style.display = 'block';
                ipLookupResult.textContent = 'Error fetching IP information.';
            }
        })
        .catch(error => {
            ipLookupError.textContent = 'Failed to perform IP lookup.';
            ipLookupError.style.display = 'block';
            ipLookupResult.textContent = 'Error fetching IP information.';
            console.error('Error fetching IP information (direct IP):', error);
        });

    // Perform reverse IP lookup (this block remains mostly the same)
    fetch(`/api/reverse_ip_lookup.php?ip=${encodeURIComponent(ipAddressForLookup)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                reverseIpLookupError.textContent = data.error;
                reverseIpLookupError.style.display = 'block';
                reverseIpLookupResult.textContent = 'Error fetching reverse IP lookup.';
            } else {
                reverseIpLookupResult.innerHTML = `<p><strong>Hostname:</strong> ${data.hostname || 'N/A'}</p>`;
            }
        })
        .catch(error => {
            reverseIpLookupError.textContent = 'Failed to perform reverse IP lookup.';
            reverseIpLookupError.style.display = 'block';
            reverseIpLookupResult.textContent = 'Error fetching reverse IP lookup.';
            console.error('Error fetching reverse IP information (direct IP):', error);
        });
}


    // Fetch other data (MX, A, TXT, SPF, DMARC, SMTP, CNAME) in parallel
    Promise.allSettled([ // Use Promise.allSettled to ensure all promises run even if one fails
        fetch(`/api/mx_records.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    mxRecordsError.textContent = data.error;
                    mxRecordsError.style.display = 'block';
                    mxRecordsList.innerHTML = '<li>Error fetching MX records.</li>';
                } else {
                    mxRecordsError.style.display = 'none';
                    mxRecordsList.innerHTML = `
                        <li><h2 class="h6 fw-semibold text-dark mb-2">${data.domain}</h2></li>
                        <li><strong>Record:</strong> ${data.mx_record}</li>
                        <li><strong>IP:</strong> ${data.ip}</li>
                        <li><strong>Status:</strong> ${data.status}</li>
                        <li><strong>Test Duration:</strong> ${data.test_duration_ms} ms</li>
                        <li><strong>AS Number:</strong> ${data.as_number}</li>
                        <li><strong>Organization:</strong> ${data.organization}</li>
                        <li><strong>Country:</strong> ${data.country}</li>
                        <li><strong>Abuse Contact:</strong>
                            ${data.abuse_contact?.email ?? 'N/A'}<br>
                            ${data.abuse_contact?.address ?? ''}<br>
                            ${data.abuse_contact?.phone ?? ''}
                        </li>
                    `;
                }
            })
            .catch(error => {
                mxRecordsError.textContent = 'Failed to fetch MX records.';
                mxRecordsError.style.display = 'block';
                mxRecordsList.innerHTML = '<li>Error fetching MX records.</li>';
                console.error('Error fetching MX records:', error);
            }),


        fetch(`/api/a_records.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    aRecordsError.textContent = data.error;
                    aRecordsError.style.display = 'block';
                    aRecordsList.innerHTML = '<li>Error fetching A records.</li>';
                } else if (data.length === 0) {
                    aRecordsList.innerHTML = '<li>No A records found.</li>';
                } else {
                    aRecordsError.style.display = 'none';
                    aRecordsList.innerHTML = data.map(record => `
                        <li><strong>Record:</strong> ${record.record}</li>
                        <li><strong>Type:</strong> ${record.type}</li>
                        <li><strong>Value:</strong> ${record.value}</li>
                        <li><strong>TTL:</strong> ${record.ttl}</li>
                        <hr>
                    `).join('');
                }
            })
            .catch(error => {
                aRecordsError.textContent = 'Failed to fetch A records.';
                aRecordsError.style.display = 'block';
                aRecordsList.innerHTML = '<li>Error fetching A records.</li>';
                console.error('Error fetching A records:', error);
            }),


        fetch(`/api/txt_records.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    txtRecordsError.textContent = data.error;
                    txtRecordsError.style.display = 'block';
                    txtRecordsList.innerHTML = '<li>Error fetching TXT records.</li>';
                } else if (data.length === 0) {
                    txtRecordsList.innerHTML = '<li>No TXT records found.</li>';
                } else {
                    txtRecordsList.innerHTML = data.map(record => `<li>${record.txt}</li>`).join('');
                }
            })
            .catch(error => {
                txtRecordsError.textContent = 'Failed to fetch TXT records.';
                txtRecordsError.style.display = 'block';
                txtRecordsList.innerHTML = '<li>Error fetching TXT records.</li>';
                console.error('Error fetching TXT records:', error);
            }),

        fetch(`/api/spf_record.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    spfRecordError.textContent = data.error;
                    spfRecordError.style.display = 'block';
                    spfRecordValue.textContent = 'Error fetching SPF record.';
                } else if (!data.record) {
                    spfRecordValue.textContent = 'No SPF record found.';
                } else {
                    spfRecordValue.textContent = data.record;
                }
            })
            .catch(error => {
                spfRecordError.textContent = 'Failed to fetch SPF record.';
                spfRecordError.style.display = 'block';
                spfRecordValue.textContent = 'Error fetching SPF record.';
                console.error('Error fetching SPF record:', error);
            }),

        fetch(`/api/dmarc_record.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    dmarcRecordError.textContent = data.error;
                    dmarcRecordError.style.display = 'block';
                    dmarcRecordValue.textContent = 'Error fetching DMARC record.';
                } else if (!data.record) {
                    dmarcRecordValue.textContent = 'No DMARC record found.';
                } else {
                    dmarcRecordValue.textContent = data.record;
                }
            })
            .catch(error => {
                dmarcRecordError.textContent = 'Failed to fetch DMARC record.';
                dmarcRecordError.style.display = 'block';
                dmarcRecordValue.textContent = 'Error fetching DMARC record.';
                console.error('Error fetching DMARC record:', error);
            }),

        fetch(`/api/smtp_check.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    smtpCheckError.textContent = data.error;
                    smtpCheckError.style.display = 'block';
                    smtpCheckResult.textContent = 'Error during SMTP check.';
                } else {
                    smtpCheckResult.textContent = data.message;
                }
            })
            .catch(error => {
                smtpCheckError.textContent = 'Failed to perform SMTP check.';
                smtpCheckError.style.display = 'block';
                smtpCheckResult.textContent = 'Error during SMTP check.';
                console.error('Error fetching SMTP check:', error);
            }),

        // --- CNAME Record Fetch ---
        fetch(`/api/cname_record.php?domain=${encodeURIComponent(domain)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cnameRecordsError.style.display = 'none';
                    if (data.cname && data.cname.length > 0) {
                        cnameRecordsList.innerHTML = data.cname.map(record => `
                            <li><strong>Host:</strong> ${record.host}</li>
                            <li><strong>Target:</strong> ${record.target}</li>
                            <li><strong>TTL:</strong> ${record.ttl}</li>
                            <hr>
                        `).join('');
                    } else {
                        cnameRecordsList.innerHTML = `<li>No CNAME record found for ${domain}.</li>`;
                    }
                } else {
                    cnameRecordsError.textContent = data.message || 'An unknown error occurred fetching CNAME records.';
                    cnameRecordsError.style.display = 'block';
                    cnameRecordsList.innerHTML = ''; // Clear list if there's an error
                }
            })
            .catch(error => {
                cnameRecordsError.textContent = 'Failed to fetch CNAME records.';
                cnameRecordsError.style.display = 'block';
                cnameRecordsList.innerHTML = ''; // Clear list on fetch error
                console.error('Error fetching CNAME records:', error);
            })
    ])
        .then(() => {
            // All fetches completed (success or failure), you can add any post-processing here if needed.
            console.log('All DNS lookups attempted.');
        });
});