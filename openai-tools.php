<?php
/*
Plugin Name: OpenAI Tools
Plugin URI:  https://woocat.app
Donate link: https://woocat.app/donate
Description: OpenAI Tools - Generate meta descriptions, meta titles, fake review generator, and fake comment generator with OpenAI API.
Version:     2.1.3
Author:      WooCat
Author URI:  https://woocat.app
License:     GPLs
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wporg
Domain Path: /languages
Requires Plugins: woocommerce
*/

if (!defined('WPINC')) {
    die;
}

define('OPENAI_TOOLS_DIR', plugin_dir_path(__FILE__));
define('OPENAI_TOOLS_URL', plugin_dir_url(__FILE__));

function openai_tools_load_textdomain()
{
    load_plugin_textdomain('openai-tools', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'openai_tools_load_textdomain');

function openai_tools_add_metabox()
{
    add_meta_box(
        'ai_reviwer',
        __("AI Reviewer", 'openai-tools'),
        'ai_tools_reviwer_callback',
        'product',
        'side',
        'default'
    );

    add_meta_box(
        'openai_post_comment_generator',
        __("AI Commenter", 'openai-tools'),
        'openai_tools_post_comment_generator_callback',
        'post',
        'side',
        'default'
    );
}

function ai_tools_reviwer_callback()
{
    include OPENAI_TOOLS_DIR . 'templates/product/review-generator.php';
}

add_action('add_meta_boxes', 'openai_tools_add_metabox');

function openai_tools_post_comment_generator_callback()
{
    include OPENAI_TOOLS_DIR . 'templates/post/post-comment-generator.php';
}

function add_features_on_product_editting_page()
{
    include OPENAI_TOOLS_DIR . 'templates/yoast/meta-description-tools.php';
    include OPENAI_TOOLS_DIR . 'templates/product/short-description-tools.php';
}
add_action('woocommerce_product_options_general_product_data', 'add_features_on_product_editting_page');

function add_features_on_category_editting_page()
{
    include OPENAI_TOOLS_DIR . 'templates/category/description-tools.php';
}

add_action('product_cat_edit_form_fields', 'add_features_on_category_editting_page');
function add_features_on_wpseo_tools_page()
{
    if (isset($_GET['page']) && $_GET['page'] === 'wpseo_tools' && isset($_GET['tool']) && $_GET['tool'] === 'bulk-editor') {
        include OPENAI_TOOLS_DIR . 'templates/yoast/meta-bulk-editor-tools.php';
    }
}

add_action('admin_init', 'add_features_on_wpseo_tools_page');

function woocommerce_webhook_deliver_async_disable()
{
    $woocommerce_webhook_deliver_async_disable = get_option('woocommerce_webhook_deliver_async_disable', "0");
    if ($woocommerce_webhook_deliver_async_disable === "1") {
        add_filter('woocommerce_webhook_deliver_async', '__return_false');
    }
}

add_action('init', 'woocommerce_webhook_deliver_async_disable');

include_once OPENAI_TOOLS_DIR . 'modules/api.php';

include_once OPENAI_TOOLS_DIR . 'templates/admin/product-description-bulk-editor.php';
include_once OPENAI_TOOLS_DIR . 'templates/admin/category-description-bulk-editor.php';
include_once OPENAI_TOOLS_DIR . 'templates/admin/openai-settings-page.php';

$enable_xml_feeds_generator = get_option('enable_xml_feeds_generator', '1');
if ($enable_xml_feeds_generator === '1') {
    include_once OPENAI_TOOLS_DIR . 'templates/admin/xml-feeds-generator.php';
    require_once OPENAI_TOOLS_DIR . 'modules/ajax-handlers.php';
}