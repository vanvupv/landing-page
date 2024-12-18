<?php

/**
 * Plugin Name: Security setup
 * Description: Some basic security settings.
 * Version: 1.0.0
 * Author: Longpv
 * License: GPLv2
 */

// remove wp_version
function vf_remove_wp_version_strings($src)
{
    global $wp_version;
    parse_str(parse_url($src, PHP_URL_QUERY), $query);
    if (!empty($query['ver']) && $query['ver'] === $wp_version) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
add_filter('script_loader_src', 'vf_remove_wp_version_strings');
add_filter('style_loader_src', 'vf_remove_wp_version_strings');

// Hide WP version strings from generator meta tag
function vf_remove_version()
{
    return '';
}
add_filter('the_generator', 'vf_remove_version');

// Change default login error
function vf_login_errors()
{
    return 'Invalid user!';
}
add_filter('login_errors', 'vf_login_errors');

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// upload size limit 2MB
add_filter('upload_size_limit', 'PBP_increase_upload');
function PBP_increase_upload($bytes)
{
    return 524288 * 4 * 10;
}

// Remove the Comments menu from the Dashboard
function remove_comments_menu()
{
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'remove_comments_menu');

// Remove wp's default comment function
function disable_comments_and_pings_post_type()
{
    // Remove comments displayed in posts
    remove_post_type_support('post', 'comments');
    remove_post_type_support('post', 'trackbacks');
}
add_action('admin_init', 'disable_comments_and_pings_post_type');

// redirect wp-admin and wp-register.php to the homepage
add_action('init', 'custom_login_redirect');
function custom_login_redirect()
{
    // prevent users from entering the registration page
    if (strpos($_SERVER['REQUEST_URI'], 'wp-register.php') !== false) {
        wp_redirect(home_url('/404'));
        exit();
    }

    // Particularly, urls containing xmlrpc.php give status 403
    if (strpos($_SERVER['REQUEST_URI'], 'xmlrpc.php') !== false) {
        status_header(403);
        exit();
    }
}

// Apply a filter to the field value before saving
function custom_modify_text_field($value, $post_id, $field)
{
    $value_change = custom_replace_value($value);

    return $value_change;
}
add_filter('acf/update_value', 'custom_modify_text_field', 10, 3);

// Block CORS in WordPress
add_action('init', 'add_cors_http_header');
add_action('send_headers', 'add_cors_http_header');
function add_cors_http_header()
{
    header("Access-Control-Allow-Origin: *");
    header("X-Powered-By: none");
}

function cl_customize_rest_cors()
{
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Expose-Headers: Link', false);
        return $value;
    });
}
add_action('rest_api_init', 'cl_customize_rest_cors', 15);

// Removed scripts imported from editor in admin
add_filter('content_save_pre', 'custom_content_save');
function custom_content_save($content)
{
    $content = custom_replace_value($content);
    $content = preg_replace('/\b(?:o[nN][eE]?[rR][rR]?[oO][rR]?)\b/i', '', $content);

    return $content;
}

function custom_replace_value($text)
{
    $special_characters = [
        '<script>',
        '</script>',
        'alert(',
        '$(',
        '&lt;script&gt;',
        '&lt;/script&gt',
        'document.',
    ];
    $text = str_replace($special_characters, '', $text);

    return $text;
}

// Disable some endpoints for unauthenticated users
add_filter('rest_endpoints', 'disable_default_endpoints');
function disable_default_endpoints($endpoints)
{
    $endpoints_to_remove = array(
        '/oembed/1.0',
        '/wp/v2/media',
        '/wp/v2/types',
        '/wp/v2/statuses',
        '/wp/v2/taxonomies',
        '/wp/v2/tags',
        '/wp/v2/users',
        '/wp/v2/comments',
        '/wp/v2/settings',
        '/wp/v2/themes',
        '/wp/v2/blocks',
        '/wp/v2/oembed',
        '/wp/v2/posts',
        '/wp/v2/pages',
        '/wp/v2/block-renderer',
        '/wp/v2/search',
        '/wp/v2/categories'
    );

    if (!is_user_logged_in() && !is_admin()) {
        foreach ($endpoints_to_remove as $rem_endpoint) {
            foreach ($endpoints as $maybe_endpoint => $object) {
                if (stripos($maybe_endpoint, $rem_endpoint) !== false) {
                    unset($endpoints[$maybe_endpoint]);
                }
            }
        }
    }

    return $endpoints;
}

// Change the login logo for the entire site
function custom_login_logo()
{
    echo '<style type="text/css">
    #login h1 a {
      display: none !important;
    }
  </style>';
}
add_action('login_head', 'custom_login_logo');

// change login logo url
function custom_login_logo_url()
{
    return home_url();
}
add_filter('login_headerurl', 'custom_login_logo_url');


// prevent access to the author page to get information
add_action('template_redirect', 'redirect_author_pages');
function redirect_author_pages()
{
    if (is_author()) {
        wp_redirect(home_url('/404'));
        exit();
    }
}

// allow script iframe tag within posts
function allow_iframe_script_tags($allowedposttags)
{
    // Allow iframe embed tags exclusively
    $allowedposttags["iframe"] = array(
        "src" => true,
        "width" => true,
        "height" => true,
        "class" => true,
        "frameborder" => true,
        "webkitAllowFullScreen" => true,
        "mozallowfullscreen" => true,
        "allowFullScreen" => true,
        "allow" => true,
    );

    // tag's allowable attribute
    $allowed_atts = array(
        'type' => array(),
        'align' => array(),
        'class' => array(),
        'id' => array(),
        'dir' => array(),
        'lang' => array(),
        'style' => array(),
        'xml:lang' => array(),
        'src' => array(),
        'alt' => array(),
        'href' => array(),
        'rel' => array(),
        'rev' => array(),
        'target' => array(),
        'novalidate' => array(),
        'value' => array(),
        'name' => array(),
        'tabindex' => array(),
        'action' => array(),
        'method' => array(),
        'for' => array(),
        'width' => array(),
        'height' => array(),
        'data' => array(),
        'title' => array(),
    );

    // list of tags saved to db
    $allowedposttags["center"] = $allowed_atts;
    $allowedposttags['form'] = $allowed_atts;
    $allowedposttags['label'] = $allowed_atts;
    $allowedposttags['input'] = $allowed_atts;
    $allowedposttags['textarea'] = $allowed_atts;
    $allowedposttags['iframe'] = $allowed_atts;
    $allowedposttags['script'] = $allowed_atts;
    $allowedposttags['style'] = $allowed_atts;
    $allowedposttags['strong'] = $allowed_atts;
    $allowedposttags['small'] = $allowed_atts;
    $allowedposttags['table'] = $allowed_atts;
    $allowedposttags['span'] = $allowed_atts;
    $allowedposttags['abbr'] = $allowed_atts;
    $allowedposttags['code'] = $allowed_atts;
    $allowedposttags['pre'] = $allowed_atts;
    $allowedposttags['div'] = $allowed_atts;
    $allowedposttags['img'] = $allowed_atts;
    $allowedposttags['h1'] = $allowed_atts;
    $allowedposttags['h2'] = $allowed_atts;
    $allowedposttags['h3'] = $allowed_atts;
    $allowedposttags['h4'] = $allowed_atts;
    $allowedposttags['h5'] = $allowed_atts;
    $allowedposttags['h6'] = $allowed_atts;
    $allowedposttags['ol'] = $allowed_atts;
    $allowedposttags['ul'] = $allowed_atts;
    $allowedposttags['li'] = $allowed_atts;
    $allowedposttags['em'] = $allowed_atts;
    $allowedposttags['hr'] = $allowed_atts;
    $allowedposttags['br'] = $allowed_atts;
    $allowedposttags['tr'] = $allowed_atts;
    $allowedposttags['td'] = $allowed_atts;
    $allowedposttags['p'] = $allowed_atts;
    $allowedposttags['a'] = $allowed_atts;
    $allowedposttags['b'] = $allowed_atts;
    $allowedposttags['i'] = $allowed_atts;

    return $allowedposttags;
}
add_filter("wp_kses_allowed_html", "allow_iframe_script_tags", 1);

// setting image in content editor
function set_default_image_settings_on_login($user_login, $user)
{
    global $wpdb;

    $user_id = $user->ID;
    $prefix = $wpdb->prefix;
    $meta_key = $prefix . 'user-settings';
    $current_settings = get_user_meta($user_id, $meta_key, true);

    if (strpos($current_settings, '&align=') !== false) {
        $current_settings = preg_replace('/&align=([^"]*)/', '&align=center', $current_settings);
    } else {
        $current_settings .= '&align=center';
    }

    if (strpos($current_settings, '&imgsize=') !== false) {
        $current_settings = preg_replace('/&imgsize=([^"]*)/', '&imgsize=center', $current_settings);
    } else {
        $current_settings .= '&imgsize=center';
    }

    update_user_meta($user_id, $meta_key, $current_settings);
}

add_action('wp_login', 'set_default_image_settings_on_login', 10, 2);

function remove_styles_and_scripts_from_content($content)
{
    $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
    $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);

    return $content;
}
add_filter('the_content', 'remove_styles_and_scripts_from_content', 99);

function add_dropdown_arrow_to_menu($items, $args)
{
    if ($args->theme_location) {
        $items = preg_replace('/(<a.*?>)(.*)<\/a>/i', '<span class="dropdown-arrow"></span>$1$2</a>', $items);
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'add_dropdown_arrow_to_menu', 10, 2);
