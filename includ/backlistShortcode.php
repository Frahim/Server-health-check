<?php
/* ============  SHORTCODE  ============ */
add_shortcode( 'blacklist_checker', 'render_blacklist_checker' );

function render_blacklist_checker() {
	ob_start(); ?>
	<form id="blc-form" class="formwrapper">
		<h2 class="title">Blacklists Checker</h2>
		<p>Enter your Domain Name</p>
		<div class="search">
			<input type="text" name="blc_input" class="searchTerm" placeholder="e.g., example.com" required>
			<button type="submit" class="searchButton twenty-one" id="showPopupBtn">Check</button>
		</div>
	</form>

	<!-- Popup -->
	<div id="blc-popup" class="popup-overlay" style="display:none;">
		<div class="popup-content">
			<span class="close-button" id="blc-close">&times;</span>
			<div id="blc-result" class="resultwrapper" style="margin-top:20px;"></div>
			 <div class="adds">
               <h3> Advertise display here</h3>
            </div>
		</div>
	</div>

	<script>
	/* ---------- Front‑end JS ---------- */
	const form        = document.getElementById('blc-form');
	const popup       = document.getElementById('blc-popup');
	const resultBox   = document.getElementById('blc-result');
	const closeBtn    = document.getElementById('blc-close');

	form.addEventListener('submit', e => {
		e.preventDefault();
		const input = form.querySelector('[name="blc_input"]').value.trim();
		if (!input) return;

		/* open popup + spinner */
		popup.style.display = 'flex';
		resultBox.innerHTML = '<div class="p10">Checking …</div>';

		fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>?action=check_blacklist&input=' + encodeURIComponent(input))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					resultBox.innerHTML = `<div class="p10" style="color:red;">${data.error}</div>`;
					return;
				}

				let html = `
					<div class="innerwrapper">
						<h3>Blacklist report for <em>${data.domain}</em> (IP: ${data.ip})</h3>
						<table style="width:100%;border-collapse:collapse;" border="1">
							<thead><tr><th>Blacklist</th><th>Status</th></tr></thead><tbody>
				`;

				data.results.forEach(row => {
					const color = row.status === 'Listed' ? 'red' : 'green';
					html += `<tr><td>${row.bl}</td><td style="color:${color};">${row.status}</td></tr>`;
				});

				html += '</tbody></table></div>';
				resultBox.innerHTML = html;
			})
			.catch(err => {
				resultBox.innerHTML = `<div class="p10" style="color:red;">Error: ${err}</div>`;
			});
	});

	/* close popup handlers */
	closeBtn.addEventListener('click', () => popup.style.display = 'none');
	popup.addEventListener('click', e => { if (e.target === popup) popup.style.display = 'none'; });
	</script>
<?php
	return ob_get_clean();
}

/* ============  AJAX HANDLER  ============ */
add_action( 'wp_ajax_check_blacklist',        'handle_blacklist_ajax' );
add_action( 'wp_ajax_nopriv_check_blacklist', 'handle_blacklist_ajax' );

function handle_blacklist_ajax() {
	header( 'Content-Type: application/json' );

	$input = isset( $_GET['input'] ) ? sanitize_text_field( $_GET['input'] ) : '';
	if ( empty( $input ) ) {
		echo wp_json_encode( [ 'error' => 'Domain or IP required.' ] );
		wp_die();
	}

	$ip = filter_var( $input, FILTER_VALIDATE_IP ) ? $input : gethostbyname( $input );
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		echo wp_json_encode( [ 'error' => 'Invalid IP or domain.' ] );
		wp_die();
	}

	/* Existing helper does the heavy lifting */
	$raw = check_dns_blacklists( $ip );

	$rows = [];
	foreach ( $raw as $bl => $status ) {
		$rows[] = [ 'bl' => $bl, 'status' => $status ];
	}

	echo wp_json_encode( [
		'domain'  => $input,
		'ip'      => $ip,
		'results' => $rows,
	] );
	wp_die();
}

/* ============  BLACKLIST LOOK‑UP FUNCTION (unchanged)  ============ */
function check_dns_blacklists( $ip ) {
	  $blacklists = [
        "all.s5h.net",
        "b.barracudacentral.org",
        "bl.0spam.org",
        "bl.spamcop.net",
        "dnsbl-1.uceprotect.net",
        "dnsbl-2.uceprotect.net",
        "dnsbl-3.uceprotect.net",
        "dyna.spamrats.com",
        "ips.backscatterer.org",
        "korea.services.net",
        "noptr.spamrats.com",
        "blacklist.woody.ch",
        "bogons.cymru.com",
        "combined.abuse.ch",
        "db.wpbl.info",
        "dnsbl.dronebl.org",
        "drone.abuse.ch",
        "duinv.aupads.org",
        "orvedb.aupads.org",
        "proxy.bl.gweep.ca",
        "psbl.surriel.com",
        "rbl.0spam.org",
        "relays.bl.gweep.ca",
        "relays.nether.net",
        "singular.ttk.pte.hu",
        "spam.abuse.ch",
        "spam.dnsbl.anonmails.de",
        "spam.spamrats.com",
        "spambot.bls.digibase.ca",
        "spamrbl.imp.ch",
        "spamsources.fabel.dk",
        "ubl.lashback.com",
        "ubl.unsubscore.com",
        "virus.rbl.jp",
        "wormrbl.imp.ch",
        "z.mailspike.net",
        "zen.spamhaus.org",
        "dnsbl.sorbs.net",
    ];

	$reverse_ip = implode( '.', array_reverse( explode( '.', $ip ) ) );
	$results    = [];

	foreach ( $blacklists as $bl ) {
		$lookup = $reverse_ip . '.' . $bl;
		$results[ $bl ] = checkdnsrr( $lookup, 'A' ) ? 'Listed' : 'Not Listed';
	}
	return $results;
}
