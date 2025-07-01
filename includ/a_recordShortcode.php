<?php
function a_record_checker_shortcode($atts = []) // <-- MODIFIED
{
    $atts = shortcode_atts([
        'img' => '',
		'img2' => '',
        'url' => ''
    ], $atts);             

    ob_start(); ?>

    <form id="a-record-check-form" class="formwrapper">
        <h2 class="title">A Record Checker</h2>
        <p>Enter your Domain Name</p>
        <div class="search">
            <input type="text" id="a-record-domain" class="searchTerm" placeholder="e.g., example.com">
            <button id="showPopupBtn" type="submit" class="searchButton twenty-one">
                Check
            </button>
        </div>
    </form>

    <div id="a-result-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <span class="close-button" id="closePopupBtn">&times;</span>
            <div id="a-record-result" class="resultwrapper" style="margin-top: 20px;"></div>
            
           <?php if (!empty($atts['img']) && !empty($atts['url'])) : ?> <!-- NEW -->
                <div class="adds"> <!-- NEW -->
                    <a href="<?php echo esc_url($atts['url']); ?>" target="_blank"> <!-- NEW -->
                        <img class="addimage" src="<?php echo esc_url($atts['img']); ?>" alt="Advertisement"/> <!-- NEW -->
                    </a> <!-- NEW -->
					<a href="<?php echo esc_url($atts['url']); ?>" target="_blank"> <!-- NEW -->
                        <img class="addimage" src="<?php echo esc_url($atts['img2']); ?>" alt="Advertisement"/> <!-- NEW -->
                    </a> <!-- NEW -->
                </div> <!-- NEW -->   
            <?php endif; ?> <!-- NEW -->
        </div>
    </div>

    <script>
        document.getElementById('a-record-check-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('a-record-domain').value;
            const resultBox = document.getElementById('a-record-result');
            const popup = document.getElementById('a-result-popup');
            popup.style.display = 'flex';
            resultBox.innerHTML = '<div class="p10">Checking...</div>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=check_a_record&domain=' + encodeURIComponent(domain))
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultBox.innerHTML = `<div style="color:red;">Error: ${data.error}</div>`;
                        return;
                    }
                    let output = `<div class="innerwrapper">
                        <h3 style="margin-top: 0;">A Records for <strong>${domain}</strong></h3> 
                        <table style="width:100%; border-collapse: collapse;">
                            <tr><th>Type</th><th>IP Address</th><th>TTL</th></tr>`;
                    data.forEach(item => {
                        output += `<tr>
                            <td>${item.type}</td>
                            <td>${item.value}</td>
                            <td>${item.ttl}</td>
                        </tr>`;
                    });
                    output += '</table></div>';
                    resultBox.innerHTML = output;
                })
                .catch(err => {
                    resultBox.innerHTML = 'Error: ' + err;
                });
        });

        document.getElementById('closePopupBtn').addEventListener('click', function() {
            document.getElementById('a-result-popup').style.display = 'none';
        });

        document.getElementById('a-result-popup').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('a_record_checker', 'a_record_checker_shortcode');
