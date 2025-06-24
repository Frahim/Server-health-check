<?php
/* Template Name: Mail Server Health Template */
//get_header(); // Uncomment this when integrating with a theme
?>
<?php wp_head(); ?>
<div class="bodywraper">
    <div class="mail-server-health-page-wrapper">
        <div id="domain-input-section" class="domain-input-section">
            <h2 class="form-title text-center">Mail Server Health checker</h2>

            <form id="domain-check-form" class="domain-input-form">
                <input type="text" name="domain" id="domain" placeholder="Type your domain (e.g. example.com)" class="domain-input-field">
                <button type="submit" class="scan-domain-button">Scan</button>
            </form>
        </div>

        <div id="result" class="result-box" style="display:none;">
            <div class="back-link-container">
                <a href="#" class="back-to-scan">← Scan another domain</a>
            </div>
            <div class="risk-assessment-summary">
                <strong>Risk Assessment Level:</strong> <span id="risk-level">Medium</span>
                <p class="risk-description">A medium security risk level signals unstable SPF, DKIM, and DMARC scores, posing a potential risk of email spoofing. Prompt remediation is recommended to strengthen overall security.</p>
            </div>

            <div class="overall-result-section">               
                <div class="security-details-grid"> 
                    <div class="security-check-item">
                        <div class="bg-light border rounded p-4 mb-3 col-md-12 col-12">
                            <h2 class="h6 fw-semibold text-dark mb-2">MX Records:</h2>
                            <ul id="mxRecordsList" class="ps-3"></ul>
                            <p id="mxRecordsError" class="text-danger small fst-italic mt-2" style="display: none;"></p>
                        </div>
                    </div>                   

                    <div class="security-check-item">
                        <strong class="check-title">SPF</strong>
                        <p class="check-description">Sender Policy Framework</p>
                    </div>
                    <div class="security-check-item">
                        <strong class="check-title">DKIM</strong>
                        <p class="check-description">DomainKeys Identified Mail</p>
                    </div>
                </div>

                <div id="recommendations-container" class="recommendations-section" style="display:none;">
                    <h3>Recommendations:</h3>
                </div>

                <div class="result-actions">
                    <button class="see-details-button">See Details</button>
                    <button class="start-dmarc-button">Start DMARC Journey</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php wp_footer(); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded. Script is running.');

        const domainFormSection = document.getElementById('domain-input-section');
        const resultBox = document.getElementById('result');
        const domainCheckForm = document.getElementById('domain-check-form');
        const backToScanButton = document.querySelector('.back-to-scan');
        const scanButton = document.querySelector('.scan-domain-button');

        // Add checks for element existence
        if (!domainFormSection) console.error("Error: Element #domain-input-section not found!");
        if (!resultBox) console.error("Error: Element #result not found!");
        if (!domainCheckForm) console.error("Error: Element #domain-check-form not found!");

        
        domainCheckForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted.');

            const domain = document.getElementById('domain').value;
            if (!domain) {
                alert('Please enter a domain name.');
                console.log('No domain entered, stopping.');
                return;
            }

            console.log('Domain entered:', domain);
            console.log('Attempting to hide form and show result div.');

            if(domainFormSection) {
                domainFormSection.style.display = 'none';
                console.log('#domain-input-section display set to none.');
            } else {
                console.error('#domain-input-section is null, cannot hide.');
            }

            if(resultBox) {
                resultBox.style.display = 'block';
                console.log('#result display set to block.');
            } else {
                console.error('#result is null, cannot show.');
            }

            scanButton.innerText = 'Scanning...';
            scanButton.disabled = true;

            console.log('mailServerHealth object:', typeof mailServerHealth, mailServerHealth); // Crucial check for localization

            if (typeof mailServerHealth === 'undefined' || !mailServerHealth.ajaxurl) {
                console.error("mailServerHealth is not defined or ajaxurl is missing. AJAX call will fail.");
                alert("Configuration error: AJAX setup incorrect. Check functions.php.");
                scanButton.innerText = 'Scan Domain';
                scanButton.disabled = false;
                if(domainFormSection) domainFormSection.style.display = 'block';
                if(resultBox) resultBox.style.display = 'none';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'mail_server_health_analyze_domain');
            formData.append('domain', domain);
            formData.append('nonce', mailServerHealth.nonce);

            fetch(mailServerHealth.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('AJAX response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('AJAX data parsed:', data);
                if (data.success) {
                    const results = data.data;
                    console.log('Analysis results:', results);

                    riskLevelSpan.innerText = results.risk_level;
                    riskLevelSpan.className = '';
                    if (results.risk_level === 'Low') {
                        riskLevelSpan.classList.add('risk-level-low');
                    } else if (results.risk_level === 'Medium') {
                        riskLevelSpan.classList.add('risk-level-medium');
                    } else {
                        riskLevelSpan.classList.add('risk-level-high');
                    }
                    riskDescriptionP.innerText = results.risk_description;

                    scoreSpan.innerText = results.score;
                    updateScoreCircle(results.score);

                    dmarcPolicyOverallSpan.innerText = results.dmarc.status;
                    dmarcPolicyOverallSpan.className = '';
                    if (results.dmarc.status === 'Valid') {
                        dmarcPolicyOverallSpan.classList.add('status-valid');
                    } else {
                        dmarcPolicyOverallSpan.classList.add('status-missing');
                    }

                    if (dmarcDetailsP) dmarcDetailsP.innerText = results.dmarc.details;
                    if (spfDetailsP) spfDetailsP.innerText = results.spf.details;
                    if (dkimDetailsP) dkimDetailsP.innerText = results.dkim.details;

                    // --- Display MX Records ---
                    if (mxRecordsList) {
                        mxRecordsList.innerHTML = ''; // Clear previous results
                        if (results.mx && results.mx.status === 'Valid' && results.mx.records.length > 0) {
                            mxRecordsError.style.display = 'none';
                            results.mx.records.forEach(record => {
                                const li = document.createElement('li');
                                li.innerHTML = `
                                    <strong>Priority:</strong> ${record.priority}<br>
                                    <strong>MX Record:</strong> ${record.mx_record}<br>
                                    <strong>IP Address:</strong> ${record.ip}<br>
                                    <strong>Status:</strong> ${record.status}<br>
                                    <strong>AS Number:</strong> ${record.as_number}<br>
                                    <strong>Organization:</strong> ${record.organization}<br>
                                    <strong>Country:</strong> ${record.country}
                                    <hr>
                                `;
                                mxRecordsList.appendChild(li);
                            });
                        } else {
                            mxRecordsError.innerText = results.mx.details || 'No MX records found or an error occurred.';
                            mxRecordsError.style.display = 'block';
                        }
                    }

                    // --- Display Recommendations ---
                    if (recommendationsContainer) {
                        recommendationsContainer.innerHTML = '';
                        if (results.recommendations && results.recommendations.length > 0) {
                            const ul = document.createElement('ul');
                            results.recommendations.forEach(rec => {
                                const li = document.createElement('li');
                                li.innerText = rec;
                                ul.appendChild(li);
                            });
                            recommendationsContainer.appendChild(ul);
                            recommendationsContainer.style.display = 'block';
                        } else {
                            recommendationsContainer.style.display = 'none';
                        }
                    }
                } else {
                    console.error('AJAX Success: false. Error:', data.data);
                    alert('Error: ' + (data.data || 'Could not analyze domain.'));
                    if(domainFormSection) domainFormSection.style.display = 'block';
                    if(resultBox) resultBox.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred during the analysis. Please try again. Check console for details.');
                if(domainFormSection) domainFormSection.style.display = 'block';
                if(resultBox) resultBox.style.display = 'none';
            })
            .finally(() => {
                scanButton.innerText = 'Scan Domain';
                scanButton.disabled = false;
                console.log('AJAX request finished. Button reset.');
            });
        });

        backToScanButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Back to scan button clicked.');
            if(resultBox) resultBox.style.display = 'none';
            if(domainFormSection) domainFormSection.style.display = 'block';
            domainCheckForm.reset();
            riskLevelSpan.className = '';
            dmarcPolicyOverallSpan.className = '';
            if (recommendationsContainer) {
                recommendationsContainer.innerHTML = '';
                recommendationsContainer.style.display = 'none';
            }
    

           
        });

        
    });
</script>