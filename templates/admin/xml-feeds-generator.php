<?php

add_action('admin_menu', 'openai_tools_xml_feeds_for_woocommerce_menu');

function openai_tools_xml_feeds_for_woocommerce_menu()
{
    add_options_page(
        __('Product Feeds', 'openai-tools'),
        __('Product Feeds', 'openai-tools'),
        'manage_options',
        'product-feeds',
        'openai_tools_product_feeds_page'
    );
    add_options_page(
        __('Review Feeds', 'openai-tools'),
        __('Review Feeds', 'openai-tools'),
        'manage_options',
        'review-feeds',
        'openai_tools_review_feeds_page'
    );
}


function openai_tools_product_feeds_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'openai-tools'));
    }
    $product_categories = get_terms('product_cat');
?>
    <div class="wrap">
        <h1><?php _e('Product Feeds', 'openai-tools'); ?></h1>
        <h2><?php _e('Product Feeds for Google Merchant Center', 'openai-tools'); ?></h2>
        <form id="product-feeds-form">
            <?php wp_nonce_field('xml_feeds_generate', 'xml_feeds_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="include_variations"><?php _e('Include Variations', 'openai-tools'); ?></label></th>
                    <td><input type="checkbox" name="include_variations" id="include_variations"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="exclude_categories"><?php _e('Exclude Categories', 'openai-tools'); ?></label></th>
                    <td>
                        <select name="exclude_categories[]" id="exclude_categories" multiple>
                            <?php foreach ($product_categories as $category) : ?>
                                <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="exclude_products"><?php _e('Exclude Products', 'openai-tools'); ?></label></th>
                    <td>
                        <input type="text" id="exclude_products_search" placeholder="<?php _e('Search for products...', 'openai-tools'); ?>">
                        <select name="exclude_products[]" id="exclude_products" multiple style="display: none;"></select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td><input type="submit" class="button button-primary" value="<?php _e('Generate Feed', 'openai-tools'); ?>"></td>
                </tr>
            </table>
        </form>
        <div id="xml-feeds-progress" style="display: none;"><?php _e('Generating XML feed...', 'openai-tools'); ?></div>
        <div id="xml-feeds-link" style="display: none;">
            <a href="" id="xml-feeds-download-link" target="_blank"><?php _e('Open XML Feed Url', 'openai-tools'); ?></a>
        </div>
    </div>
<?php
}

function openai_tools_review_feeds_page()
{
    $default_brand = get_option('review_feeds_default_brand', '');
?>
    <div class="wrap">
        <h1><?php _e('Review Feeds', 'openai-tools'); ?></h1>
        <h2><?php _e('Review Feeds for Google Merchant Center', 'openai-tools'); ?></h2>
        <form id="review-feeds-form">
            <?php wp_nonce_field('review_feeds_generate', 'review_feeds_nonce'); ?>
            <table class="form-table">
                <tr>
                    <td><input type="submit" class="button button-primary" value="<?php _e('Generate Feed', 'openai-tools'); ?>"></td>
                </tr>
            </table>
        </form>
        <div id="review-feeds-progress" style="display: none;"><?php _e('Generating XML feed...', 'openai-tools'); ?></div>
        <div id="review-feeds-link" style="display: none;">
            <a href="" id="review-feeds-download-link" target="_blank"><?php _e('Open XML Feed Url', 'openai-tools'); ?></a>
        </div>
    </div>
<?php
}

add_action('admin_footer', 'openai_tools_xml_feeds_ajax_script');

function openai_tools_xml_feeds_ajax_script()
{
?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#exclude_products_search').on('input', function() {
                var searchTerm = $(this).val();
                if (searchTerm.length < 3) {
                    return;
                }

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'search_products',
                        search_term: searchTerm,
                        nonce: $('#xml_feeds_nonce').val()
                    },
                    success: function(response) {
                        var $select = $('#exclude_products');
                        $select.empty();
                        response.forEach(function(product) {
                            $select.append($('<option>', {
                                value: product.id,
                                text: product.text
                            }));
                        });
                        $select.show();
                    }
                });
            });

            $('#product-feeds-form').on('submit', function(e) {
                e.preventDefault();
                var includeVariations = $('#include_variations').is(':checked') ? 'true' : 'false';
                var excludeCategories = $('#exclude_categories').val() || [];
                var excludeProducts = $('#exclude_products').val() || [];
                var nonce = $('#xml_feeds_nonce').val();

                $('#xml-feeds-progress').show();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_product_feed',
                        include_variations: includeVariations,
                        exclude_categories: excludeCategories,
                        exclude_products: excludeProducts,
                        nonce: nonce
                    },
                    success: function(response) {
                        $('#xml-feeds-progress').hide();
                        if (response.success) {
                            $('#xml-feeds-link').show();
                            $('#xml-feeds-download-link').attr('href', response.data);
                        } else {
                            alert('Error generating XML feed.');
                        }
                    },
                    error: function() {
                        $('#xml-feeds-progress').hide();
                        alert('Error generating XML feed.');
                    }
                });
            });

            $('#review-feeds-form').on('submit', function(e) {
                e.preventDefault();
                var nonce = $('#review_feeds_nonce').val();

                $('#review-feeds-progress').show();

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'generate_review_feed',
                        nonce: nonce
                    },
                    success: function(response) {
                        $('#review-feeds-progress').hide();
                        if (response.success) {
                            $('#review-feeds-link').show();
                            $('#review-feeds-download-link').attr('href', response.data);
                        } else {
                            alert('Error generating Review feed.');
                        }
                    },
                    error: function() {
                        $('#review-feeds-progress').hide();
                        alert('Error generating Review feed.');
                    }
                });
            });
        });
    </script>
<?php
}
