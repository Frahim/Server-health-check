document.getElementById('cnameLookupForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const domainInput = document.getElementById('domainInput');
            const domain = domainInput.value.trim();

            const cnameRecordsDiv = document.getElementById('cnameRecords');
            const cnameRecordsList = document.getElementById('cnameRecordsList');
            const cnameRecordsError = document.getElementById('cnameRecordsError');

            // Reset previous results
            cnameRecordsList.innerHTML = '';
            cnameRecordsError.style.display = 'none';
            cnameRecordsDiv.style.display = 'none';

            if (!domain) {
                cnameRecordsError.textContent = 'Please enter a domain.';
                cnameRecordsError.style.display = 'block';
                return;
            }

            // Show the records section, but hide the error initially
            cnameRecordsDiv.style.display = 'block';
            cnameRecordsError.style.display = 'none';
            cnameRecordsList.innerHTML = '<li>Loading CNAME records...</li>';


            // Fetch CNAME records
            fetch(`/api/cname_record.php?domain=${encodeURIComponent(domain)}`) // Use encodeURIComponent for safety
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (data.cname && data.cname.length > 0) {
                            cnameRecordsList.innerHTML = data.cname.map(record => `
                                <li><strong>Host:</strong> ${record.host}</li>
                                <li><strong>Target:</strong> ${record.target}</li>
                                <li><strong>TTL:</strong> ${record.ttl}</li>
                                <hr>
                            `).join('');
                        } else {
                            // This case should ideally be handled by the PHP success:false, but good to have
                            cnameRecordsList.innerHTML = `<li>No CNAME record found for ${domain}.</li>`;
                        }
                    } else {
                        // Handle success: false from PHP
                        cnameRecordsError.textContent = data.message || 'An unknown error occurred.';
                        cnameRecordsError.style.display = 'block';
                        cnameRecordsList.innerHTML = ''; // Clear list if there's an error
                    }
                })
                .catch(error => {
                    console.error('Error fetching CNAME records:', error);
                    cnameRecordsError.textContent = 'Failed to fetch CNAME records: ' + error.message;
                    cnameRecordsError.style.display = 'block';
                    cnameRecordsList.innerHTML = ''; // Clear list on fetch error
                });
        });