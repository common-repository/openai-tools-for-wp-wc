<?php

add_action('wp_ajax_search_products', 'search_products');

function search_products()
{
    check_ajax_referer('xml_feeds_generate', 'nonce');

    $search_term = sanitize_text_field($_POST['search_term']);
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 10,
        's' => $search_term,
    );

    $products = get_posts($args);
    $results = array();

    foreach ($products as $product) {
        $results[] = array(
            'id' => $product->ID,
            'text' => $product->post_title,
        );
    }

    wp_send_json($results);
}

add_action('wp_ajax_generate_product_feed', 'openai_tools_generate_product_feed');
add_action('wp_ajax_generate_review_feed', 'openai_tools_generate_review_feed');

function openai_tools_generate_product_feed()
{
    check_ajax_referer('xml_feeds_generate', 'nonce');
    try {
        $include_variations = isset($_POST['include_variations']) && $_POST['include_variations'] === 'true';
        $exclude_categories = isset($_POST['exclude_categories']) ? array_map('intval', $_POST['exclude_categories']) : array();
        $exclude_products = isset($_POST['exclude_products']) ? array_map('intval', $_POST['exclude_products']) : array();

        $args = array(
            'limit' => -1,
            'exclude' => $exclude_products,
        );

        if ($include_variations) {
            $args['type'] = array('simple', 'variation');
        } else {
            $args['type'] = array('simple', 'variable');
        }

        $products = wc_get_products($args);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0">';
        $site_title = get_bloginfo('name');
        $xml .= '<title>' . $site_title . '</title>';
        $xml .= '<link rel="self" href="' . get_site_url() . '"/>';
        $xml .= '<updated>' . gmdate('Y-m-d\TH:i:s\Z') . '</updated>';
        $xml .= '<author><name>XML Feeds for WooCommerce</name></author>';

        foreach ($products as $product) {
            if (in_array($product->get_id(), $exclude_products)) {
                continue;
            }

            if (!empty($exclude_categories)) {
                $product_categories = $product->get_category_ids();
                if (array_intersect($exclude_categories, $product_categories)) {
                    continue;
                }
            }

            if ($product->get_status() !== 'publish') {
                continue;
            }

            if (!$product->is_in_stock()) {
                continue;
            }

            $entry = '<entry>';
            $entry .= '<g:id>' . $product->get_id() . '</g:id>';
            $sku = $product->get_sku();
            $entry .= '<g:mpn>' . $sku . '</g:mpn>';
            $brand = $product->get_attribute('pa_brand');
            if (empty($brand)) {
                $brand = get_bloginfo('name');
            }
            $entry .= '<g:brand>' . $brand . '</g:brand>';
            $product_name = $product->get_name();
            $product_name = str_replace('&', '&amp;', $product_name);
            $product_name = str_replace('  ', ' ', $product_name);
            $product_name = str_replace('&nbsp;', ' ', $product_name);
            $entry .= '<g:title>' . $product_name . '</g:title>';
            $image = wp_get_attachment_image_src($product->get_image_id(), 'full');
            $entry .= '<g:image_link>' . $image[0] . '</g:image_link>';
            $entry .= '<g:condition>new</g:condition>';
            $entry .= '<g:availability>in stock</g:availability>';
            $additional_image_links = array();
            $product_gallery_ids = $product->get_gallery_image_ids();
            foreach ($product_gallery_ids as $gallery_id) {
                $gallery_image = wp_get_attachment_image_src($gallery_id, 'full');
                $additional_image_links[] = $gallery_image[0];
            }
            foreach ($additional_image_links as $additional_image_link) {
                $entry .= '<g:additional_image_link>' . $additional_image_link . '</g:additional_image_link>';
            }
            $short_description = $product->get_short_description();
            if (empty($short_description) && $product->get_type() === 'variation') {
                $parent_product = wc_get_product($product->get_parent_id());
                $short_description = $parent_product->get_short_description();
            }
            if (has_shortcode($short_description, 'button') || has_shortcode($short_description, 'lightbox') || has_shortcode($short_description, 'ux_products')) {
                $short_description = strip_shortcodes($short_description);
            }

            $short_description = str_replace('&nbsp;', ' ', $short_description);
            $short_description = str_replace(array("\r", "\n"), '', $short_description);
            $entry .= '<g:description>' . wp_strip_all_tags($short_description) . '</g:description>';
            $product_link = $product->get_permalink();
            $product_link = str_replace('&', '&amp;', $product_link);
            $entry .= '<g:link>' . $product_link . '</g:link>';
            $entry .= '<g:price>' . $product->get_price() . ' ' . get_woocommerce_currency() . '</g:price>';

            $product_type = '';
            if ($product->get_type() === 'variation') {
                $parent_product = wc_get_product($product->get_parent_id());
                $product_categories = $parent_product->get_category_ids();
            } else {
                $product_categories = $product->get_category_ids();
            }

            $product_categories = array_map(function ($category_id) {
                $product_category = get_term($category_id, 'product_cat');
                return $product_category->name;
            }, $product_categories);
            $product_type = implode(' > ', $product_categories);
            $entry .= '<g:product_type>' . $product_type . '</g:product_type>';

            $weight = $product->get_weight();
            if (!empty($weight)) {
                $entry .= '<g:shipping_weight>' . $weight . ' kg</g:shipping_weight>';
            }

            $length = $product->get_length();
            if (!empty($length)) {
                $entry .= '<g:shipping_length>' . $length . ' cm</g:shipping_length>';
            }

            $width = $product->get_width();
            if (!empty($width)) {
                $entry .= '<g:shipping_width>' . $width . ' cm</g:shipping_width>';
            }

            $height = $product->get_height();
            if (!empty($height)) {
                $entry .= '<g:shipping_height>' . $height . ' cm</g:shipping_height>';
            }

            $shipping_class = $product->get_shipping_class();
            if (!empty($shipping_class)) {
                $entry .= '<g:shipping_label>' . $shipping_class . '</g:shipping_label>';
            }

            $entry .= '</entry>';
            $xml .= $entry;
        }

        $xml .= '</feed>';

        $upload_dir = wp_upload_dir();
        $xml_file_path = $upload_dir['basedir'] . '/xml/product_feeds.xml';
        $xml_file_url = $upload_dir['baseurl'] . '/xml/product_feeds.xml';

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $wp_filesystem->put_contents($xml_file_path, $xml, FS_CHMOD_FILE);

        wp_send_json_success($xml_file_url);
    } catch (\Throwable $th) {
        wp_send_json_error(array('message' => $th->getMessage()));
    }
}

function openai_tools_generate_review_feed()
{
    try {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );

        $products = new WP_Query($args);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<feed xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.google.com/shopping/reviews/schema/product/2.3/product_reviews.xsd">';
        $xml .= '<version>2.3</version>';
        $xml .= '<publisher>';
        $xml .= '<name>' . get_bloginfo('name') . '</name>';
        $xml .= '</publisher>';
        $xml .= '<reviews>';

        while ($products->have_posts()) {
            $products->the_post();
            $product_id = get_the_ID();
            $comments = get_comments(array(
                'post_id' => $product_id,
                'status' => 'approve',
                'type' => 'review',
            ));
            $product = wc_get_product($product_id);
            foreach ($comments as $comment) {
                $review = '<review>';
                $review .= '<review_id>' . $comment->comment_ID . '</review_id>';
                $review .= '<reviewer>';
                $review .= '<name is_anonymous="false">' . $comment->comment_author . '</name>';
                $review .= '<reviewer_id>' . $comment->user_id . '</reviewer_id>';
                $review .= '</reviewer>';
                $review_timestamp = strtotime($comment->comment_date_gmt);
                // format into 2024-07-12T00:00:00Z
                $review_timestamp = gmdate('Y-m-d\TH:i:s\Z', $review_timestamp);
                $review .= '<review_timestamp>' . $review_timestamp . '</review_timestamp>';
                $review_content = $comment->comment_content;
                // remove html tags
                $review_content = wp_strip_all_tags($review_content);
                $review_content = str_replace('&', '&amp;', $review_content);
                // if review content is empty, use the product name
                if (empty($review_content)) {
                    $review_content = $product->get_name();
                }
                $review .= '<content>' . esc_html($review_content) . '</content>';
                $review .= '<review_url type="group">' . esc_url(get_comment_link($comment)) . '</review_url>';
                $review .= '<ratings>';
                $review .= '<overall min="1" max="5">' . intval(get_comment_meta($comment->comment_ID, 'rating', true)) . '</overall>';
                $review .= '</ratings>';
                $review .= '<products>';
                $review .= '<product>';
                $review .= '<product_ids>';
                $review .= '<skus>';
                $review .= '<sku>' . esc_html($product->get_sku()) . '</sku>';
                $review .= '</skus>';
                $review .= '<mpns>';
                $review .= '<mpn>' . esc_html($product->get_sku()) . '</mpn>';
                $review .= '</mpns>';
                $review .= '<brands>';
                $review .= '<brand>';
                $product_brand = $product->get_attribute('pa_brand');
                if (empty($product_brand)) {
                    $product_brand = get_bloginfo('name');
                }
                $review .= esc_html($product_brand);
                $review .= '</brand>';
                $review .= '</brands>';
                $review .= '</product_ids>';
                $product_name = $product->get_name();
                $product_name = str_replace('&', '&amp;', $product_name);
                $review .= '<product_name>' . esc_html($product_name) . '</product_name>';
                // product_url
                $review .= '<product_url>' . get_permalink($product_id) . '</product_url>';
                $review .= '</product>';
                $review .= '</products>';
                // is_spam
                $review .= '<is_spam>false</is_spam>';
                $review .= '</review>';
                $xml .= $review;
            }
        }
        $xml .= '</reviews>';
        $xml .= '</feed>';

        $upload_dir = wp_upload_dir();
        $xml_file_path = $upload_dir['basedir'] . '/xml/review_feeds.xml';
        $xml_file_url = $upload_dir['baseurl'] . '/xml/review_feeds.xml';

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $wp_filesystem->put_contents($xml_file_path, $xml, FS_CHMOD_FILE);

        wp_send_json_success(esc_url($xml_file_url));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => esc_html($e->getMessage())));
    }
}

function openai_tools_ensure_xml_directory()
{
    $upload_dir = wp_upload_dir();
    $xml_dir = $upload_dir['basedir'] . '/xml/';

    if (!file_exists($xml_dir)) {
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $wp_filesystem->mkdir($xml_dir, 0755);
    }
}
add_action('admin_init', 'openai_tools_ensure_xml_directory');

function woocommerce_not_active_notice()
{
?>
    <div class="notice notice-error is-dismissible">
        <p>WooCommerce is not active. Please activate WooCommerce to use this plugin.</p>
    </div>
<?php
}
