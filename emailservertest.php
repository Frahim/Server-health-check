<?php
/*
Plugin Name: Mail server testing tool
Description: A plugin for test your mail server performance.
Version: 1.0
Author: MD Yeasir Arafat
*/


add_action('wp_enqueue_scripts', 'domain_tools_enqueue_frontend_styles');

function domain_tools_enqueue_frontend_styles()
{
    wp_enqueue_style(
        'domain-tools-frontend-css',
        plugin_dir_url(__FILE__) . 'css/frontend-style.css',
        [],
        filemtime(plugin_dir_path(__FILE__) . 'css/frontend-style.css')
    );
}


include plugin_dir_path(__FILE__) . 'includ/admin_menu.php';


// Enqueue editor specific styles
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_style('domain-tools-block-editor-style', plugin_dir_url(__FILE__) . 'css/editor-style.css');
});


add_action('admin_enqueue_scripts', 'domain_tools_admin_styles');
function domain_tools_admin_styles($hook)
{
    if ($hook != 'toplevel_page_domain-tools-settings') return;
    wp_enqueue_style('domain-tools-admin-css', plugin_dir_url(__FILE__) . 'css/admin-style.css');
}

include plugin_dir_path(__FILE__) . 'includ/mxShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/txtShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/spfShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/a_recordShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/dmarc_Shortcode.php';
include plugin_dir_path(__FILE__) . 'includ/dkimShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/smtpShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/sslShortcode.php';
include plugin_dir_path(__FILE__) . 'includ/ip_checker_shortcode.php';
include plugin_dir_path(__FILE__) . 'includ/email_deliverability_checker.php';
include plugin_dir_path(__FILE__) . 'includ/backlistShortcode.php';




/**  FOR Block */
// Hook to register the Gutenberg block
add_action('init', 'domain_tools_register_block');

function domain_tools_register_block()
{
    // Ensure the block editor assets are enqueued only in the editor
    if (function_exists('register_block_type')) {
        wp_register_script(
            'domain-tools-block',
            plugins_url('build/block.js', __FILE__), // Correct path to the JS file
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'wp-block-editor'], // Dependencies
            filemtime(plugin_dir_path(__FILE__) . 'build/block.js')
        );

        register_block_type('domain-tools/shortcode-selector', [
            'editor_script'   => 'domain-tools-block',
            'render_callback' => 'domain_tools_block_render_callback'
        ]);
    }
}



// Render callback for the dynamic block (this will run on the frontend)
function domain_tools_block_render_callback($attributes)
{
    $shortcodes = [
        'spf_checker' => '[spf_checker]',
        'mx_checker' => '[mx_checker]',
        'dkim_checker' => '[dkim_checker]',
        'smtp_checker' => '[smtp_checker]',
        'dmarc_checker' => '[dmarc_checker]',
        'a_record_checker' => '[a_record_checker]',
        'txt_checker' => '[txt_checker]',
        'ip_checker' => '[ip_checker]',
        'ssl_checker' => '[ssl_checker]',
        'email_deliverability_checker' => '[email_deliverability_checker]',
        'blacklist_checker' => '[blacklist_checker]'
    ];

    $key = isset($attributes['shortcodeKey']) ? $attributes['shortcodeKey'] : '';
    if (isset($shortcodes[$key])) {
        return do_shortcode($shortcodes[$key]);
    }

    return '<p>Please select a Domain Tool shortcode.</p>';
}

// REST API endpoint for previewing shortcodes in the editor
add_action('rest_api_init', function () {
    register_rest_route('domain-tools/v1', '/render-shortcode', [
        'methods' => 'GET',
        'callback' => 'domain_tools_render_shortcode',
        'permission_callback' => '__return_true' // Allow preview for all
    ]);
});

function domain_tools_render_shortcode($request)
{
    $shortcodes = [
        'spf_checker' => '[spf_checker]',
        'mx_checker' => '[mx_checker]',
        'dkim_checker' => '[dkim_checker]',
        'smtp_checker' => '[smtp_checker]',
        'dmarc_checker' => '[dmarc_checker]',
        'a_record_checker' => '[a_record_checker]',
        'txt_checker' => '[txt_checker]',
        'ip_checker' => '[ip_checker]',
        'ssl_checker' => '[ssl_checker]',
        'email_deliverability_checker' => '[email_deliverability_checker]',
        'blacklist_checker' => '[blacklist_checker]'
    ];
    $code = sanitize_text_field($request->get_param('code'));
    if (!isset($shortcodes[$code])) {
        return new WP_REST_Response('<p>Invalid shortcode.</p>', 400);
    }
    return do_shortcode($shortcodes[$code]);

    // Capture the output of do_shortcode
    ob_start();
    do_shortcode($shortcodes[$code]);
    return ob_get_clean();
}



/**  Test  */

add_action('wp_head', 'domain_tools_output_custom_styles');
function domain_tools_output_custom_styles()
{
    $font_color = get_option('domain_tools_font_color', '#000000');
    $font_size = get_option('domain_tools_font_size', '16px');
    $bg_color = get_option('domain_tools_bg_color', '#fff');
?>
    <style>
        .resultwrwpper {
            color: <?php echo esc_attr($font_color); ?>;
            font-size: <?php echo esc_attr($font_size); ?>;
            background-color: <?php echo esc_attr($bg_color); ?>;
        }
    </style>
<?php
}


/**  Page template  */
add_filter('theme_page_templates', 'cpt_add_new_template');
add_filter('template_include', 'cpt_load_custom_template');
//add_filter('wp_insert_post_data', 'cpt_force_template_slug', 10, 2);

define('CPT_TEMPLATE_PATH', plugin_dir_path(__FILE__) . 'templates/');

// Add template to dropdown
function cpt_add_new_template($templates)
{
    $templates['server-helth-template.php'] = 'My Custom Template';
    $templates['template-dns-checker.php'] = 'Check Template';
    return $templates;
}

// Load the template from plugin
function cpt_load_custom_template($template)
{
    if (is_page()) {
        $current_template = get_page_template_slug(get_queried_object_id());
        if ($current_template == 'server-helth-template.php') {
            $plugin_template = CPT_TEMPLATE_PATH . 'server-helth-template.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
         if ($current_template == 'template-dns-checker.php') {
            $plugin_template = CPT_TEMPLATE_PATH . 'template-dns-checker.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    return $template;
}





add_action('wp_enqueue_scripts', 'cpt_enqueue_jquery');
function cpt_enqueue_jquery()
{
    // Check if jQuery is already registered
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    
}

// === A Record ===
add_action('wp_ajax_check_a_record', 'tmhandletm_a_record_ajax');
add_action('wp_ajax_nopriv_check_a_record', 'tmhandletm_a_record_ajax');
function tmhandletm_a_record_ajax() {
    $domain = sanitize_text_field($_GET['domain']);
    if (!$domain) wp_send_json_error('Domain is required');
    $records = dns_get_record($domain, DNS_A);
    if (!$records) wp_send_json_error('Failed to retrieve A records');
    wp_send_json($records);
}

// === MX Record ===
add_action('wp_ajax_check_mx_record', 'tmhandletm_mx_ajax');
add_action('wp_ajax_nopriv_check_mx_record', 'tmhandletm_mx_ajax');
function tmhandletm_mx_ajax() {
    $domain = sanitize_text_field($_GET['domain']);
    if (!$domain) wp_send_json_error('Domain is required');
    $records = dns_get_record($domain, DNS_MX);
    if (!$records) wp_send_json_error('Failed to retrieve MX records');
    $enhanced = [];
    foreach ($records as $r) {
        $ip = gethostbyname($r['target']);
        $info = json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,org,as,query"), true);
        $enhanced[] = array_merge($r, ['ip' => $ip, 'geo' => $info]);
    }
    wp_send_json($enhanced);
}

// === SPF Record (TXT lookup) ===
add_action('wp_ajax_check_spf_record', 'tmhandletm_spf_ajax');
add_action('wp_ajax_nopriv_check_spf_record', 'tmhandletm_spf_ajax');
function tmhandletm_spf_ajax() {
    $domain = sanitize_text_field($_GET['domain']);
    if (!$domain) wp_send_json_error('Domain is required');
    $txts = dns_get_record($domain, DNS_TXT);
    $spf = array_filter($txts, fn($r) => str_starts_with($r['txt'], 'v=spf1'));
    wp_send_json(array_values($spf));
}

// === IP Info (of domain) ===
add_action('wp_ajax_check_ip_record', 'tmhandletm_ip_ajax');
add_action('wp_ajax_nopriv_check_ip_record', 'tmhandletm_ip_ajax');
function tmhandletm_ip_ajax() {
    $domain = sanitize_text_field($_GET['domain']);
    if (!$domain) wp_send_json_error('Domain is required');
    $ip = gethostbyname($domain);
    $info = json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,org,as,query"), true);
    wp_send_json(['ip' => $ip, 'geo' => $info]);
}


add_action('wp_ajax_check_txt_record','handletm_txt_ajax');
add_action('wp_ajax_nopriv_check_txt_record','handletm_txt_ajax');
function handletm_txt_ajax() {
    header('Content-Type: application/json');
    $domain = sanitize_text_field($_GET['domain'] ?? '');
    $txts = dns_get_record($domain, DNS_TXT);
    if (!$txts) echo json_encode(['records'=>[]]);
    else echo json_encode(['records'=>array_column($txts,'txt')]);
    wp_die();
}

add_action('wp_ajax_check_dkim_record','handletm_dkim_ajax');
add_action('wp_ajax_nopriv_check_dkim_record','handletm_dkim_ajax');
function handletm_dkim_ajax(){
    header('Content-Type: application/json');
    $record = dns_get_record("default._domainkey." . sanitize_text_field($_GET['domain']), DNS_TXT);
    echo json_encode(['record'=> $record[0]['txt'] ?? 'Not found']);
    wp_die();
}

add_action('wp_ajax_check_dmarc_record','handletm_dmarc_ajax');
add_action('wp_ajax_nopriv_check_dmarc_record','handletm_dmarc_ajax');
function handletm_dmarc_ajax(){
    header('Content-Type: application/json');
    $record = dns_get_record("_dmarc." . sanitize_text_field($_GET['domain']), DNS_TXT);
    echo json_encode(['record'=> $record[0]['txt'] ?? 'Not found']);
    wp_die();
}

add_action('wp_ajax_check_smtp_record','handletm_smtp_ajax');
add_action('wp_ajax_nopriv_check_smtp_record','handletm_smtp_ajax');
function handletm_smtp_ajax(){
    header('Content-Type: application/json');
    $mx = dns_get_record(sanitize_text_field($_GET['domain']), DNS_MX);
    if (!$mx) echo json_encode(['error'=>'No MX record']);
    else {
      $host=$mx[0]['target'];
      $conn = @fsockopen($host,25,$e,$s,5);
      echo $conn ? json_encode(['status'=>'Reachable']) : json_encode(['error'=>'Not reachable']);
      if ($conn) fclose($conn);
    }
    wp_die();
}

add_action('wp_ajax_check_ssl_record','handletm_ssl_ajax');
add_action('wp_ajax_nopriv_check_ssl_record','handletm_ssl_ajax');
function handletm_ssl_ajax(){
    header('Content-Type: application/json');
    $domain = sanitize_text_field($_GET['domain']);
    $c = @stream_socket_client("ssl://{$domain}:443", $e, $s, 15);
    if (!$c) echo json_encode(['error'=>'SSL connect failed']);
    else {
      $p = stream_context_get_params($c);
      $cert = openssl_x509_parse($p['options']['ssl']['peer_certificate']);
      echo json_encode([
        'issuer'=>$cert['issuer']['CN'] ?? '',
        'validFrom'=>date('Y-m-d',$cert['validFrom_time_t']),
        'validTo'=>date('Y-m-d',$cert['validTo_time_t'])
      ]);
    }
    wp_die();
}
