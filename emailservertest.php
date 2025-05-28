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
function domain_tools_block_render_callback($attributes) {
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
