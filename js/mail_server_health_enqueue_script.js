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

    const riskLevelSpan = document.getElementById('risk-level');
    const scoreSpan = document.getElementById('score');
    const dmarcPolicyOverallSpan = document.getElementById('dmarc-policy-overall');

    const dmarcDetailsP = document.querySelector('.security-check-item:nth-child(2) .check-description');
    const spfDetailsP = document.querySelector('.security-check-item:nth-child(3) .check-description');
    const dkimDetailsP = document.querySelector('.security-check-item:nth-child(4) .check-description');
    const riskDescriptionP = document.querySelector('.risk-description');
    const recommendationsContainer = document.getElementById('recommendations-container');

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

        // Debugging point: Check if these elements exist right before changing display
        if(domainFormSection) {
            domainFormSection.style.display = 'none';
            console.log('#domain-input-section display set to none.');
        } else {
            console.error('#domain-input-section is null, cannot hide.');
        }

        // Debugging point: This is the crucial line
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
            if(domainFormSection) domainFormSection.style.display = 'block'; // Show form if AJAX config is bad
            if(resultBox) resultBox.style.display = 'none'; // Ensure result is hidden
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
                // resultBox.style.display = 'block'; // This was already set above, no need to repeat
            } else {
                console.error('AJAX Success: false. Error:', data.data);
                alert('Error: ' + (data.data || 'Could not analyze domain.'));
                if(domainFormSection) domainFormSection.style.display = 'block'; // Show form if backend error
                if(resultBox) resultBox.style.display = 'none'; // Ensure result is hidden
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred during the analysis. Please try again. Check console for details.');
            if(domainFormSection) domainFormSection.style.display = 'block'; // Show form if network error
            if(resultBox) resultBox.style.display = 'none'; // Ensure result is hidden
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
        const scoreCircle = document.querySelector('.score-circle');
        if (scoreCircle) {
            scoreCircle.style.background = ''; // Reset background
            scoreCircle.classList.remove('score-valid', 'score-medium', 'score-high');
        }
    });

    function updateScoreCircle(score) {
        const scoreCircle = document.querySelector('.score-circle');
        if (!scoreCircle) {
            console.error("Score circle element not found for update.");
            return;
        }
        const percentage = (score / 10) * 100;
        let gradientColor = '#ff4500'; // Default to red for low scores

        scoreCircle.classList.remove('score-valid', 'score-medium', 'score-high');

        if (score >= 8) {
            gradientColor = '#28a745'; // Green for high scores (valid)
            scoreCircle.classList.add('score-valid');
        } else if (score >= 4) {
            gradientColor = '#ffa702'; // Yellow for medium scores
            scoreCircle.classList.add('score-medium');
        } else {
            scoreCircle.classList.add('score-high'); // Red for low scores
        }

        scoreCircle.style.background = `conic-gradient(
            ${gradientColor} 0% ${percentage}%,
            rgba(255, 255, 255, 0.05) ${percentage}% 100%
        )`;
      //  console.log(`Score circle updated: score=${score}, percentage=${percentage}%, color=${gradientColor}`);
    }
});