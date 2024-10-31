<?php
add_filter('bulk_actions-edit-product', 'openai_tools_add_product_bulk_actions');
function openai_tools_add_product_bulk_actions($actions)
{
    $actions['edit_product_description'] = 'Edit Description with AI';
    return $actions;
}

add_action('load-edit.php', 'openai_tools_handle_product_description_bulk_actions');
function openai_tools_handle_product_description_bulk_actions()
{
    $current_screen = get_current_screen();
    if ($current_screen->id !== 'edit-product') {
        return;
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit_product_description') {
        $product_ids = isset($_REQUEST['post']) ? $_REQUEST['post'] : [];
        if (empty($product_ids)) {
            return;
        }

        $product_ids = array_map('intval', $product_ids);
        $product_ids = array_filter($product_ids, function ($product_id) {
            return get_post_type($product_id) === 'product';
        });

        if (empty($product_ids)) {
            return;
        }

        $redirect_url = admin_url('admin.php?page=product_description_bulk_editor&product_ids=' . implode(',', $product_ids));
        wp_redirect($redirect_url);
        exit;
    }
}

// add a menu "Description Editor" under Products
add_action('admin_menu', 'openai_tools_add_description_editor_menu');
function openai_tools_add_description_editor_menu()
{
    add_submenu_page(
        'edit.php?post_type=product',
        'Product Descriptions Bulb Editor',
        'Product Desc Editor',
        'manage_options',
        'product_description_bulk_editor',
        'product_description_bulk_editor_page'
    );
}

function product_description_bulk_editor_page()
{
    $product_ids = isset($_GET['product_ids']) ? explode(',', $_GET['product_ids']) : [];
    $product_ids = array_map('intval', $product_ids);
    $product_ids = array_filter($product_ids, function ($product_id) {
        return get_post_type($product_id) === 'product';
    });

    if (empty($product_ids)) {

?>
        <div class="wrap intro">
            <h1>Descriptions Bulk Editor</h1>
            <p>
            <ul>
                <li class="intro-step">
                    View the <a href="/wp-admin/edit.php?post_type=product">Product List</a> and select products you want to edit
                </li>
                <li class="intro-step">Select <span style=" background: white; border: solid 1px; border-color: #8c8f94; box-shadow: none; border-radius: 3px; padding: 0 24px 0 8px; line-height: 2; color: black; ">Edit Description with AI</span> from the <select>
                        <option value="-1">Bulk actions</option>
                        <option value="edit" class="hide-if-no-js">Edit</option>
                        <option value="trash">Move to Trash</option>
                        <option value="edit_product_description">Edit Description with AI</option>
                    </select> dropdown</li>
                <li class="intro-step">Click <input type="submit" class="button" value="Apply"></li>
            </ul>
            </p>
        </div>

        <style>
            .intro {
                max-width: 800px;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }

            .intro-step {
                margin-bottom: 10px;
                display: flex;
                gap: 6px;
                align-items: center;
            }

            <?php
            return;
        }

        $products = [];
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            $products[] = [
                'id' => $product_id,
                'sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'short_description' => $product->get_short_description(),
                'description' => $product->get_description(),
            ];
        } ?><div class="wrap"><h1>Product Descriptions Bulk Editor</h1><table class="openai-table wp-list-table widefat fixed striped"><thead><tr><th><input type="checkbox" id="select-all" style="margin: 0 10px 0 0;">Product ID </th><th>Name</th><th>Short Description</th><th><div class="openai-tools" style="padding: 4px;width: fit-content;border-radius: 5px;background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%);"><input id="ai-shortdesc-prompt" placeholder="Prompt for new content" style="border-radius: 3px;border: solid 1px grey;width: 100%;margin: 0 0 6px 0;padding-inline: 6px;height: 36px;"><div><?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?><a id="ai-short-desc-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a></div></div></th></tr></thead><tbody><?php foreach ($products as $product) : ?><tr><td class="product-id"><input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>"><?php echo $product['id']; ?></td><td><span class="product-name"><?php echo $product['name']; ?></span><br>SKU: <span class="product-sku"><?php echo $product['sku'];  ?></span><br><a class="button" style="margin: 0 6px 0 6px;" href="<?php echo get_edit_post_link($product['id']); ?>" target="_blank">Edit</a><a href="<?php echo get_permalink($product['id']); ?>" class="button" style="margin: 0 6px 0 0;" target="_blank">View</a></td><td><?php echo $product['short_description']; ?></td><td><textarea name="short_description[<?php echo $product['id']; ?>]" class="description-textarea"></textarea></td></tr><?php endforeach; ?></tbody></table><a class="button" id="save-button" style="margin-top: 20px;">Save</a></div><style>.description-textarea {
                width: 100%;
                height: 100px;
            }

            #ai-model {
                width: max-content;
            }

            span.product-name {
                font-size: 1.1em;
                font-weight: bold;
                line-height: 2em;
            }

            :root {
                --loading-deg: 0;
            }

            .openai-table textarea.active {
                border-image-slice: 1;
                border-image-source: linear-gradient(45deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                animation: rotating 2s linear infinite;
                border-width: 4px;
            }

            @keyframes rotating {
                0% {
                    border-image-source: linear-gradient(45deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                }

                25% {
                    border-image-source: linear-gradient(90deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                }

                50% {
                    border-image-source: linear-gradient(135deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                }

                75% {
                    border-image-source: linear-gradient(180deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                }

                90% {
                    border-image-source: linear-gradient(225deg, rgb(107 98 255), rgba(0, 212, 255, 1));
                }
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                $('#ai-short-desc-btn').on('click', generate_short_descriptions);
                var i = 0;
                var isStop = false;

                function generate_short_descriptions() {
                    i = 0;
                    var stopBtn = $('<a id="ai-stop-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(255 98 98) 0%, rgb(0 20 24) 100%); height: 16px !important;">Stop</a>');
                    $('#ai-short-desc-btn').after(stopBtn);
                    $('#ai-short-desc-btn').hide();

                    $('#ai-stop-btn').on('click', function() {
                        isStop = true;
                        $('#ai-short-desc-btn').removeAttr('disabled');
                        $('#ai-stop-btn').remove();
                        $('#ai-short-desc-btn').show();
                    });
                    processNextShortDescriptionRow();
                }

                function processNextShortDescriptionRow() {
                    if (isStop) {
                        isStop = false;
                        return;
                    }
                    var model = $('#ai-model').val();
                    var model_group = $('#ai-model').find('option:selected').parent().attr('label');
                    var $rows = $('.openai-table tbody tr');
                    var $currentRow = $rows.eq(i);
                    var product_title = $currentRow.find('.product-name').text();
                    var product_sku = $currentRow.find('.product-sku').text();
                    product_title = product_title + '. SKU: ' + product_sku;
                    var prompt = $('#ai-shortdesc-prompt').val();
                    var $shortDescriptionTextarea = $currentRow.find('.description-textarea');
                    $shortDescriptionTextarea.addClass('active');

                    if (product_title) {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'ai_tools_generate_short_description',
                                model: model,
                                prompt: prompt,
                                title: product_title,
                                model_group: model_group
                            },
                            success: function(response) {
                                console.log(response);
                                if (response.success && response.value) {
                                    $shortDescriptionTextarea.val(response.value);
                                }
                                $shortDescriptionTextarea.removeClass('active');
                            },
                            complete: function() {
                                i++;
                                if (i < $rows.length) {
                                    setTimeout(() => {
                                        processNextShortDescriptionRow();
                                    }, 500);
                                } else {
                                    $('#ai-desc-loading').remove();
                                    $('#ai-short-desc-btn').show();
                                    $('#ai-stop-btn').remove();
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(error);
                            }
                        });
                    }
                }

                $('#save-button').on('click', function() {
                    var data = {
                        action: 'ai_tools_save_product_short_descriptions',
                        product_ids: [],
                        short_descriptions: []
                    };
                    $('.description-textarea').each(function() {
                        if (!$(this).closest('tr').find('.product-id input').is(':checked') || !$(this).val()) {
                            return;
                        }
                        var product_id = $(this).closest('tr').find('.product-id').text().split(' - ')[0].trim();
                        data.product_ids.push(product_id);
                        data.short_descriptions.push($(this).val());
                    });

                    if (data.product_ids.length === 0) {
                        alert('Please select products and fill in the short description');
                        return;
                    }

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: data,
                        success: function(response) {
                            console.log(response);
                            if (response.success) {
                                setTimeout(() => {
                                    location.reload();
                                }, 500);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log(error);
                            alert('Failed to save short descriptions');
                        }
                    });
                });

                $('#select-all').on('change', function() {
                    var isChecked = $(this).prop('checked');
                    $('.product-id input').prop('checked', isChecked);
                });
            });
        </script>
    <?php
}
