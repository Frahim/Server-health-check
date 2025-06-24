<?php
/* Template Name: DNS Security Checker */
get_header();
?>
<div class="bodywraper">
    <div class="dns-checker-wrapper mail-server-health-page-wrapper">
        <div id="domain-input-section" class="domain-input-section">
            <h2 class="form-title text-center">Mail Server Health checker</h2>
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
    <div id="ip-info" class="security-check-item"></div>
  </div>
  <div class="result-actions">
    <button id="backToScan" class="scan-again-button">Scan Again</button>
  </div>
</div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('dns-check-form');
  const domainInput = document.getElementById('domainInput');
  const scannedDomain = document.getElementById('scanned-domain');
  const resultSection = document.getElementById('dns-results');
  const inputSection = document.getElementById('domain-input-section');
  const backBtn = document.getElementById('backToScan');

  const showLoader = el => el.innerHTML = '<div class="loader"></div>';
  const formatRows = rows => rows.map(r => `<div class="row"><div class="column left"><p>${r.label}</p></div><div class="column right"><p>${r.value}</p></div></div>`).join('');

  form.addEventListener('submit', e => {
    e.preventDefault();
    const domain = domainInput.value.trim();
    if (!domain) return alert("Please enter a domain");

    scannedDomain.innerText = domain;
    inputSection.style.display = 'none';
    resultSection.style.display = 'block';

    const sections = [
      { id: 'a-records', action: 'check_a_record' },
      { id: 'mx-records', action: 'check_mx_record' },
      { id: 'spf-records', action: 'check_spf_record' },
      { id: 'txt-records', action: 'check_txt_record' },
      { id: 'dkim-records', action: 'check_dkim_record&selector=default' },
      { id: 'dmarc-records', action: 'check_dmarc_record' },
      { id: 'smtp-records', action: 'check_smtp_record' },
      { id: 'ssl-records', action: 'check_ssl_record' }
    ];

    // Fetch non-IP records
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
              data.forEach(d => rows.push({ label: 'IP', value: d.value }, { label: 'TTL', value: d.ttl }));
              break;
            case 'mx-records':
              data.records.forEach(r =>
                rows.push({ label: 'Priority', value: r.priority }, { label: 'MX', value: r.mx_record }, { label: 'IP', value: r.ip }, { label: 'Org', value: r.organization })
              );
              break;
            case 'spf-records': rows.push({ label: 'SPF', value: data.record }); break;
            case 'txt-records':
  if (Array.isArray(data.records) && data.records.length > 0) {
    data.records.forEach(txt => rows.push({ label: 'TXT', value: txt }));
  } else {
    rows.push({ label: 'TXT', value: data.record || 'No TXT records found' });
  }
  break;
            case 'dkim-records': rows.push({ label: 'DKIM', value: data.record }); break;
            case 'dmarc-records': rows.push({ label: 'DMARC', value: data.record }); break;
            case 'smtp-records': rows.push({ label: 'SMTP', value: data.status }); break;
            case 'ssl-records': rows.push({ label: 'Issuer', value: data.issuer }, { label: 'Valid From', value: data.validFrom }, { label: 'Valid To', value: data.validTo }); break;
          }
          el.innerHTML = `<h4>${s.id.replace(/-/g, ' ').toUpperCase()}</h4>` + formatRows(rows);
        })
        .catch(err => {
          document.getElementById(s.id).innerHTML = `<p style="color:red;">Error: ${err}</p>`;
        });
    });

    // IP Check (resolve + lookup)
    const ipContainer = document.getElementById('ip-info');
    showLoader(ipContainer);
    fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=resolve_ip_ajax&domain=${domain}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) throw data.error;
        return fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=ip_checker_ajax&ip=${data.ip}`);
      })
      .then(r => r.json())
      .then(res => {
        if (!res.success) throw res.error;
        const d = res.ip_details;
        const rows = [
          { label: 'IP', value: d.ip },
          { label: 'City', value: d.city },
          { label: 'Region', value: d.region },
          { label: 'Country', value: d.country },
          { label: 'Org', value: d.org }
        ];
        ipContainer.innerHTML = `<h4>IP Info</h4>` + formatRows(rows);
      })
      .catch(err => ipContainer.innerHTML = `<p style="color:red;">IP Error: ${err}</p>`);
  });

  // Back Button
  if (backBtn) {
    backBtn.addEventListener('click', () => {
      resultSection.style.display = 'none';
      inputSection.style.display = 'block';
      form.reset();
      [...document.querySelectorAll('.security-check-item')].forEach(d => d.innerHTML = '');
    });
  }
});
</script>



<!-- <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formSection = document.getElementById('domain-input-section');
        const resultSection = document.getElementById('dns-results');
        const backBtn = document.getElementById('backToScan');
        const domainForm = document.getElementById('dns-check-form');

        domainForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const domain = document.getElementById('domainInput').value.trim();
            if (!domain) return alert("Please enter a valid domain.");

            document.getElementById('scanned-domain').innerText = domain;
            formSection.style.display = 'none';
            resultSection.style.display = 'block';

            const endpoints = ['a', 'mx', 'spf', 'ip'];
            endpoints.forEach(type => {
                fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=check_${type}_record&domain=${domain}`)
                    .then(res => res.json())
                    .then(data => {
                        let container = document.getElementById(`${type}-records`) || document.getElementById('ip-info');
                        if (data.error) {
                            container.innerHTML = `<h4>${type.toUpperCase()} Record</h4><p style="color:red">${data.error}</p>`;
                        } else {
                            let content = `<h4>${type.toUpperCase()} Record</h4>`;
                            if (type === 'a') {                               
                                data.forEach(record => {
                                    content += `<div class="row">
                                        <div class="column left">
                                            <p>IP</p>                                        
                                        </div>
                                        <div class="column right">                                       
                                            <p>${record.value}</p>
                                        </div>
                                        <div class="column left">
                                            <p>TTL</p>                                        
                                        </div>
                                        <div class="column right">                                       
                                            <p>${record.ttl}</p>
                                        </div>
                                    </div>`;
                                });
                               
                            } else if (type === 'mx') {
                                data.records.forEach(record => {
                                    content += `
                                <div class="row">
                                    <div class="column left">
                                        <p>Priority</p>                                        
                                    </div>
                                    <div class="column right">                                       
                                        <p>${record.priority}</p>
                                    </div>
                                     <div class="column left">
                                        <p>MX</p>                                        
                                    </div>
                                    <div class="column right">                                       
                                        <p>${record.mx_record}</p>
                                    </div>
                                     <div class="column left">
                                        <p>IP</p>                                        
                                    </div>
                                    <div class="column right">                                       
                                        <p>${record.ip}</p>
                                    </div>
                                    <div class="column left">
                                        <p>Org</p>                                        
                                    </div>
                                    <div class="column right">                                       
                                        <p>${record.organization || 'N/A'}</p>
                                    </div>
                                </div>                                   
                                    `;
                                });

                            } else if (type === 'spf') {
                                content += `<div class="row">
                                                <div class="column left">
                                                    <p>SPF Record</p>                                        
                                                </div>
                                                <div class="column right">                                       
                                                    <p>${data.record || 'Not found'}</p>
                                                </div>
                                            </div>`;
                            } else if (type === 'ip') {
                                content += `<div class="row">
                                                <div class="column left"
                                                    <p>IP</p>                                        
                                                </div>
                                                <div class="column right">                                       
                                                    <p>${data.query}</p>
                                                </div>
                                                <div class="column left">
                                                    <p>Country</p>                                        
                                                </div>
                                                <div class="column right">                                       
                                                    <p>${data.country}</p>
                                                </div>
                                                <div class="column left">
                                                    <p>ISP</p>                                        
                                                </div>
                                                <div class="column right">                                       
                                                    <p>${data.org}</p>
                                                </div>
                                            </div>`;
                            }
                            container.innerHTML = content;
                        }
                    })
                    .catch(err => {
                        container.innerHTML = `<p style="color:red;">Failed to fetch ${type.toUpperCase()} data</p>`;
                    });
            });
        });

        backBtn.addEventListener('click', function() {
            resultSection.style.display = 'none';
            formSection.style.display = 'block';
            domainForm.reset();

            ['mx-records', 'a-records', 'spf-records', 'ip-info'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '';
            });
        });
    });
</script> -->


<?php get_footer(); ?>