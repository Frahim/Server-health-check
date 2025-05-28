<?php
add_shortcode('blacklist_checker', 'render_blacklist_checker');

function render_blacklist_checker()
{
    ob_start();
?>
    <form method="post">

        <div class="search">
            <input type="text" name="blc_input" class="searchTerm" placeholder="Enter IP address or domain" required>
            <button type="submit" class="searchButton">Check Blacklists</button>
        </div>

    </form>
<?php

    if (isset($_POST['blc_input'])) {
        $input = trim(sanitize_text_field($_POST['blc_input']));
        $ip = filter_var($input, FILTER_VALIDATE_IP) ? $input : gethostbyname($input);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            echo "<p style='color:red;'>Invalid IP or domain.</p>";
        } else {
            $results = check_dns_blacklists($ip);
            echo "<div  class='resultwrwpper' style='margin-top: 20px;'><div class='innerwrapper'>";
            echo "<h3>Blacklist Report for <em>" . esc_html($input) . "</em> (IP: $ip)</h3>";
            echo "<table style='width:100%; border-collapse: collapse;' border='1'>";
            echo "<thead><tr><th>Blacklist</th><th>Status</th></tr></thead><tbody>";

            foreach ($results as $bl => $status) {
                $color = ($status === 'Listed') ? 'red' : 'green';
                echo "<tr><td>$bl</td><td style='color:$color;'>$status</td></tr>";
            }

            echo "</tbody></table>";
            echo "</div></div>";
        }
    }

    return ob_get_clean();
}

function check_dns_blacklists($ip)
{
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

    $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
    $results = [];

    foreach ($blacklists as $bl) {
        $lookup = $reverse_ip . '.' . $bl;
        if (checkdnsrr($lookup, "A")) {
            $results[$bl] = "Listed";
        } else {
            $results[$bl] = "Not Listed";
        }
    }

    return $results;
}
