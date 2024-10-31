<?php
add_action('init', 'init_openai_tools_ajax');
function init_openai_tools_ajax()
{
    add_action('wp_ajax_openai_tools_add_reviews', 'openai_tools_add_reviews');
    add_action('wp_ajax_openai_tools_add_comments', 'openai_tools_add_comments');

    add_action('wp_ajax_ai_tools_verify_api_key', 'ai_tools_verify_api_key');
    add_action('wp_ajax_ai_tools_verify_siliconflow_api_key', 'ai_tools_verify_siliconflow_api_key');

    add_action('wp_ajax_ai_tools_generate_reviews', 'ai_tools_generate_reviews');
    add_action('wp_ajax_openai_tools_generate_comments', 'openai_tools_generate_comments');
    add_action('wp_ajax_ai_tools_task', 'ai_tools_task');

    $enable_public_ai_tools_task = get_option('enable_public_ai_tools_task', '0');
    if ($enable_public_ai_tools_task === '1') {
        add_action('wp_ajax_nopriv_ai_tools_task', 'ai_tools_task');
    }

    add_action('wp_ajax_ai_tools_generate_meta_description', 'ai_tools_generate_meta_description');
    add_action('wp_ajax_ai_tools_generate_meta_title', 'ai_tools_generate_meta_title');
    add_action('wp_ajax_ai_tools_generate_short_description', 'ai_tools_generate_short_description');
    add_action('wp_ajax_ai_tools_generate_category_description', 'ai_tools_generate_category_description');

    add_action('wp_ajax_ai_tools_save_product_short_descriptions', 'ai_tools_save_short_descriptions');
    add_action('wp_ajax_ai_tools_save_product_category_descriptions', 'ai_tools_save_category_descriptions');
}

function ai_tools_task()
{
    $model = sanitize_text_field($_POST['model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $task = sanitize_text_field($_POST['task']);
    $enable_public_ai_tools_task = get_option('enable_public_ai_tools_task', '0');
    if ($enable_public_ai_tools_task !== '1') {
        $content = sanitize_text_field($_POST['content']);
    } else {
        $content = $_POST['content'];
    }

    ai_tools_run_task($model_group, $model, $task, $content);
}
function ai_tools_run_task($model_group, $model, $task, $content)
{
    $result = ai_tools_task_api($model_group, $model, $task, $content);
    $return_result = [];
    if (isset($result['id'])) {
        $return_result["success"] = true;
        $return_result["value"] = trim($result['choices'][0]['message']['content']);
        wp_send_json($return_result);
        wp_die();
    }
    $return_result["success"] = false;
    $return_result["value"] = $result;
    wp_send_json($return_result);
    wp_die();
}

function ai_tools_task_api($model_group, $model, $task, $content)
{
    $ai_domain = ai_tools_get_api_domain_by_model_group($model_group);
    $ai_api_key = ai_tools_get_api_key_by_model($model, $model_group);
    $url = 'https://' . $ai_domain . '/v1/chat/completions';

    $data = array(
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $task
            ],
            [
                'role' => 'user',
                'content' => $content
            ]
        ]
    );

    $response = wp_remote_post(
        $url,
        [
            'headers' =>
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $ai_api_key,
            ],
            'body' => wp_json_encode($data),
            'timeout' => 60,
        ]
    );

    $enable_ai_tools_logs = get_option('enable_ai_tools_logs', '0');
    if ($enable_ai_tools_logs === '1') {
        $logger = wc_get_logger();
        $logger->debug('AI Tools Task', [
            'ai_domain' => $ai_domain,
            'ai_model' => $model,
            'ai_model_group' => $model_group,
            'request' => $data,
            'response' => $response,
        ]);
    }

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $response = array(
            'id' => 'error',
            'success' => false,
            'message' => $error_message,
        );
        return $response;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
}

function ai_tools_save_short_descriptions()
{
    $product_ids = $_POST['product_ids'];
    $short_descriptions = $_POST['short_descriptions'];

    foreach ($product_ids as $index => $product_id) {
        $product = wc_get_product($product_id);
        $product->set_short_description($short_descriptions[$index]);
        $product->save();
    }

    wp_send_json(array('success' => true));
}
add_filter('term_description', 'wp_kses_post');
function ai_tools_save_category_descriptions()
{
    $category_ids = $_POST['category_ids'];
    $cat_descriptions = $_POST['cat_descriptions'];
    global $wpdb;
    foreach ($category_ids as $index => $category_id) {
        $term = get_term($category_id, 'product_cat');
        if (is_wp_error($term)) {
            continue;
        }
        $wpdb->update($wpdb->term_taxonomy, array('description' => $cat_descriptions[$index]), array('term_id' => $category_id));
        wp_cache_delete($category_id, 'product_cat');
    }

    wp_send_json(array('success' => true));
}

function ai_tools_verify_api_key()
{
    $api_key = sanitize_text_field($_POST['api_key']);
    $api_domain = sanitize_text_field($_POST['api_domain']);
    if (strpos($api_domain, 'http') === false) {
        $api_domain = 'https://' . $api_domain;
    }

    $url = $api_domain . '/v1/models';
    $response = wp_remote_get(
        $url,
        [
            'headers' =>
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'timeout' => 10,
        ]
    );

    //get status code
    $status_code = wp_remote_retrieve_response_code($response);
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $response = array(
            'id' => 'error',
            'success' => false,
            'error' => array(
                'message' => $error_message,
            ),
            'status' => $status_code,
        );
        wp_send_json($response);
        wp_die();
    } else {
        $response_body = wp_remote_retrieve_body($response);
        $response = json_decode($response_body, true);
        if (isset($response['errors'])) {
            $response = array(
                'id' => 'error',
                'success' => false,
                'message' => $response['error']['message'],
                'status' => $status_code,
            );
            wp_send_json($response);
            wp_die();
        }
        $response['success'] = true;
        $response['status'] = $status_code;
        wp_send_json($response);
        wp_die();
    }
}

function ai_tools_verify_siliconflow_api_key()
{
    $api_key = sanitize_text_field($_POST['api_key']);
    $model = sanitize_text_field($_POST['model']);

    $url = 'https://api.siliconflow.cn/v1/chat/completions';
    $data = array(
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful AI assistant. You can generate content for me.',
            ],
            [
                'role' => 'user',
                'content' => 'Hi there',
            ],
        ]
    );
    $response = wp_remote_post(
        $url,
        [
            'headers' =>
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'accept' => 'application/json',
            ],
            'body' => wp_json_encode($data),
            'timeout' => 60,
        ]
    );

    //get status code
    $status_code = wp_remote_retrieve_response_code($response);
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $response = array(
            'id' => 'error',
            'success' => false,
            'error' => array(
                'message' => $error_message,
            ),
            'status' => $status_code,
        );
        wp_send_json($response);
        wp_die();
    } else {
        $response_body = wp_remote_retrieve_body($response);
        $response = json_decode($response_body, true);
        if (isset($response['errors'])) {
            $response = array(
                'id' => 'error',
                'success' => false,
                'message' => $response['error']['message'],
                'status' => $status_code,
            );
            wp_send_json($response);
            wp_die();
        }
        $response['success'] = true;
        $response['status'] = $status_code;
        wp_send_json($response);
        wp_die();
    }
}
function openai_tools_add_comments()
{
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'openai_tools_add_comments')) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid nonce',
        ));
        wp_die();
    }
    $comments = ai_tools_sanitize_comments($_POST['comments']);
    $post_id = intval($_POST['post_id']);

    $site_url = get_site_url();
    $domain_name = parse_url($site_url, PHP_URL_HOST);

    $result = array(
        'comment_ids' => [],
        'msg' => 'success'
    );

    $comment_ids = array();
    foreach ($comments as $comment) {
        $comment_content = $comment['comment'];
        $comment_author = $comment['commenter'];
        $comment_date = $comment['date'];
        $comment_author_email = 'commenter_' . rand(1, 100000) . '@' . $domain_name;
        $commentdata = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_author_email,
            'comment_author_url' => '',
            'comment_content' => $comment_content,
            'comment_type' => '',
            'comment_parent' => 0,
            'user_id' => 0,
            'comment_author_IP' => '',
            'comment_agent' => '',
            'comment_date' => $comment_date,
            'comment_approved' => 1,
        );

        $comment_id = wp_insert_comment($commentdata);
        $comment_ids[] = $comment_id;
    }

    $result['comment_ids'] = $comment_ids;
    wp_send_json($result);
    wp_die();
}
function openai_tools_generate_comments()
{
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'openai_tools_generate_comments')) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid nonce',
        ));
        wp_die();
    }

    $ai_model = sanitize_text_field($_POST['ai_model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $keywords = sanitize_text_field($_POST['keywords']);
    $count = intval($_POST['count']);
    if ($count) {
        update_option('openai_tools_comment_count', $count);
    } else {
        $count = 5;
    }

    $languages = sanitize_text_field($_POST['languages']);
    if (empty($languages)) {
        $languages = 'en';
    }

    $post_id = intval($_POST['post_id']);

    $result = openai_tools_post_comment_generator_api($model_group, $ai_model, $post_id, $count, $start_date, $end_date, $keywords, $languages);
    if (isset($result['id'])) {
        $content = $result['choices'][0]['message']['content'];
        $content = json_decode($content);
        wp_send_json($content);
        wp_die();
    }

    $result = array(
        'comment' => "openai_comment",
        'openai_result' => $result,
    );

    wp_send_json($result);
    wp_die();
}

function openai_tools_post_comment_generator_api($model_group, $model, $post_id, $count, $start_date, $end_date, $keywords, $languages)
{
    $post = get_post($post_id);
    $post_title = $post->post_title;

    $count = $count ? $count : 5;
    $start_date = $start_date ? $start_date : date('Y-m-d', strtotime('-30 days'));
    $end_date = $end_date ? $end_date : date('Y-m-d');
    $keywords = $keywords ? $keywords : 'Thank you';

    $content = "Post title: $post_title\nComments Count: $count\nStart Date: $start_date\nEnd Date: $end_date\nKeywords: $keywords\nLanguages: $languages\n\n";

    $ai_domain = ai_tools_get_api_domain_by_model_group($model_group);
    $ai_api_key = ai_tools_get_api_key_by_model($model, $model_group);
    $url = 'https://' . $ai_domain . '/v1/chat/completions';

    $data = array(
        'model' => $model,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Generate a list of made-up comments of posts. The value of the commenter key includes the first and second name. The second name only shows the first letter and a dot if it is an English name. The value of the detail key has 2 or 3 sentences. The comment detail includes topics. Only return json format: {"success": true, "data": [{"id":1,"commenter":"Samantha T.","detail":"Thank you for the detailed guide on the product. I found it very helpful and easy to follow. I look forward to more articles like this!","date":"2022-12-05"}]}',
            ],
            [
                'role' => 'user',
                'content' => $content,
            ],
        ]
    );

    $response = wp_remote_post(
        $url,
        [
            'headers' =>
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $ai_api_key,
            ],
            'body' => wp_json_encode($data),
            'timeout' => 60,
        ]
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $response = array(
            'id' => 'error',
            'success' => false,
            'message' => $error_message,
        );
        return $response;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
}

function ai_tools_generate_meta_description()
{
    $model = sanitize_text_field($_POST['model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $prompt = sanitize_text_field($_POST['prompt']);
    $post_id = intval($_POST['post_id']);
    $title = sanitize_text_field($_POST['title']);
    if (isset($_POST['post_id'])) {
        $post = get_post($post_id);
        $post_title = $post->post_title;
        $post_short_description = $post->post_excerpt;

        $content = "Title: $post_title\nDescription: $post_short_description\n";
    } else {
        $content = "Product Title: $title";
    }

    $task = 'Generate the meta description for the content. The meta description should be SEO friendly and accurate to the content information. The content language type is the same as the product title. Only return the answer without quote. ' . $prompt;
    ai_tools_run_task($model_group, $model, $task, $content);
}

function ai_tools_generate_meta_title()
{
    $model = sanitize_text_field($_POST['model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $prompt = sanitize_text_field($_POST['prompt']);
    $task = "Generate the meta title depending on the information. Make it SEO friendly. Only return the answer. No quote outside. " . $prompt;
    $title = sanitize_text_field($_POST['title']);
    $content = "Product Title: $title";
    ai_tools_run_task($model_group, $model, $task, $content);
}

function ai_tools_generate_category_description()
{
    $model = sanitize_text_field($_POST['model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $title = sanitize_text_field($_POST['title']);
    $prompt = sanitize_text_field($_POST['prompt']);
    $content = "Product category name: $title. Prompt: " . $prompt;
    $task = "Generate the category description depending on the information. Make it SEO friendly and accurate to the product information. Generated content language type is the same as Product title. Only return the answer. No quote outside.";

    ai_tools_run_task($model_group, $model, $task, $content);
}

function ai_tools_generate_short_description()
{
    $model = sanitize_text_field($_POST['model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $title = sanitize_text_field($_POST['title']);
    $prompt = sanitize_text_field($_POST['prompt']);

    $content = "Product title: $title. Prompt: " . $prompt;
    $task = "Generate the short description depending on the information. Make it SEO friendly and accurate to the product information. Generated content language type is the same as Product title. Only return the answer without quote.";
    ai_tools_run_task($model_group, $model, $task, $content);
}

function ai_tools_sanitize_comments($comments)
{
    $_comments = array();
    foreach ($comments as $comment) {
        $comment['comment'] = sanitize_text_field($comment['comment']);
        $comment['commenter'] = sanitize_text_field($comment['commenter']);
        $comment['date'] = sanitize_text_field($comment['date']);
        $_comments[] = $comment;
    }
    return $_comments;
}

function ai_tools_sanitize_reviews($revivews)
{
    $_reviews = array();
    foreach ($revivews as $review) {
        $review['rating'] = intval($review['rating']);
        $review['review'] = sanitize_text_field($review['review']);
        $review['reviewer'] = sanitize_text_field($review['reviewer']);
        $review['date'] = sanitize_text_field($review['date']);
        $_reviews[] = $review;
    }
    return $_reviews;
}

function openai_tools_add_reviews()
{
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'openai_tools_add_reviews')) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid nonce',
        ));
        wp_die();
    }
    $reviews = ai_tools_sanitize_reviews($_POST['reviews']);
    $product_id = intval($_POST['product_id']);

    $site_url = get_site_url();
    $domain_name = parse_url($site_url, PHP_URL_HOST);

    foreach ($reviews as $review) {
        $rating = $review['rating'] > 5 ? 5 : $review['rating'];
        $random_author_email = 'reviewer' . rand(1, 100000) . '@' . $domain_name;
        $commentdata = array(
            'comment_post_ID' => $product_id,
            'comment_author' => $review['reviewer'],
            'comment_author_email' => $random_author_email,
            'comment_author_url' => '',
            'comment_content' => $review['review'],
            'comment_type' => 'review',
            'comment_parent' => 0,
            'user_id' => 0,
            'comment_author_IP' => '',
            'comment_agent' => '',
            'comment_date' => $review['date'],
            'comment_approved' => 1
        );
        $comment_id = wp_insert_comment($commentdata);

        if ($comment_id) {
            update_comment_meta($comment_id, 'rating', $rating);
            update_comment_meta($comment_id, 'verified', 1);
        }
    }
    wp_send_json($reviews);
    wp_die();
}

function ai_tools_generate_reviews()
{
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'ai_tools_generate_reviews')) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid nonce',
        ));
        wp_die();
    }

    $count = intval($_POST['count']);
    if ($count) {
        update_option('openai_tools_review_count', $count);
    } else {
        $count = 5;
    }

    $ai_model = sanitize_text_field($_POST['ai_model']);
    $model_group = sanitize_text_field($_POST['model_group']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $avg_rating = sanitize_text_field($_POST['avg_rating']);
    $topics = sanitize_text_field($_POST['topics']);
    $product_id = intval($_POST['product_id']);
    $languages = sanitize_text_field($_POST['languages']);
    if (empty($languages)) {
        $languages = 'en';
    }
    $reviews = ai_tools_request_reviews_api($model_group, $ai_model, $count, $start_date, $end_date, $avg_rating, $topics, $languages, $product_id);
    if (isset($reviews['id'])) {
        $content = $reviews['choices'][0]['message']['content'];
        $content = str_replace('\n', '', $content);
        $content = json_decode($content);
        wp_send_json($content);
        wp_die();
    }
    wp_send_json([$reviews]);
    wp_die();
}
function ai_tools_request_reviews_api($model_group, $model, $count, $start_date, $end_date, $avg_rating, $topics, $languages, $product_id)
{
    $count = $count ? $count : 5;
    $start_date = $start_date ? $start_date : date('Y-m-d', strtotime('-30 days'));
    $end_date = $end_date ? $end_date : date('Y-m-d');
    $avg_rating = $avg_rating ? $avg_rating : 5;
    $topics = $topics ? $topics : 'Easy to use, Good quality';
    $product_id = $product_id ? $product_id : 1;
    $product = wc_get_product($product_id);
    $product_name = $product->get_name();

    $content = "Product: $product_name\nCount: $count\nRating: $avg_rating\nDate: $start_date to $end_date\nTopics: $topics\nLanguage: $languages";

    $ai_domain = ai_tools_get_api_domain_by_model_group($model_group);
    $ai_api_key = ai_tools_get_api_key_by_model($model, $model_group);

    $url = 'https://' . $ai_domain . '/v1/chat/completions';

    $data = array(
        'model' => $model,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Generate a list of made-up reviews of products. The value of the reviewer key includes the first and second name. The second name only shows the first letter and a dot if it is an English name. The value of the detail key has 2 or 3 sentences. The review content includes topics. Only return json format: {"success": true, "data":[{"reviewer":"Sam D.","title":"Excellent product","detail":"This product exceeded my expectations. Easy to install and works great. Recommand","rating":5,"date":"2023-03-01"}]}'
            ],
            [
                'role' => 'user',
                'content' => $content,
            ]
        ],
    );

    $response = wp_remote_post(
        $url,
        [
            'headers' =>
            [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $ai_api_key,
            ],
            'body' => wp_json_encode($data),
            'timeout' => 60,
        ]
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        $response = array(
            'id' => 'error',
            'success' => false,
            'message' => $error_message,
        );
        return $response;
    } else {
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
}

function ai_tools_get_domain_by_model($model)
{
    $domain = 'api.openai.com';
    if (strpos($model, 'gpt') !== false) {
        $domain = 'api.openai.com';
        $custom_openai_domain = get_option('custom_openai_domain', 'api.openai.com');
        $domain = $custom_openai_domain;
    } else if (strpos($model, 'llama') !== false || strpos($model, 'mixtral') !== false || strpos($model, 'gemma') !== false) {
        $domain = 'api.groq.com/openai';
    } else if (strpos($model, 'deepseek') !== false) {
        return 'api.siliconflow.cn';
    }
    return $domain;
}

function ai_tools_get_api_domain_by_model_group($model_group)
{
    if ($model_group == 'Siliconflow') {
        return 'api.siliconflow.cn';
    } else if ($model_group == 'OpenAI') {
        $domain = 'api.openai.com';
        $custom_openai_domain = get_option('custom_openai_domain', 'api.openai.com');
        $domain = $custom_openai_domain;
    } else if ($model_group == 'Groq') {
        $domain = 'api.groq.com/openai';
    } else {
        $domain = 'api.siliconflow.cn';
    }
    return $domain;
}

function ai_tools_get_api_key_by_model($model, $model_group = 'OpenAI')
{
    if (strpos($model_group, 'Siliconflow') !== false) {
        return get_option('siliconflow_api_key');
    } else if (strpos($model, 'llama') !== false || strpos($model, 'mixtral') !== false || strpos($model, 'gemma') !== false) {
        return get_option('groq_api_key');
    } else {
        return get_option('openai_api_key');
    }
}
