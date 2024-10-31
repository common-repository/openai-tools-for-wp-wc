<?php

use WPMailSMTP\Vendor\Monolog\Logger;

add_action('admin_menu', 'openai_tools_add_category_description_editor_menu');
function openai_tools_add_category_description_editor_menu()
{
    add_submenu_page(
        'edit.php?post_type=product',
        'Category Descriptions Bulb Editor',
        'Category Desc Editor',
        'manage_options',
        'category_description_bulk_editor',
        'category_description_bulk_editor_page'
    );
}

add_filter('bulk_actions-edit-product_cat', 'openai_tools_add_category_bulk_actions');
function openai_tools_add_category_bulk_actions($actions)
{
    $actions['edit_category_description'] = __('Edit Description with AI');
    return $actions;
}

add_action('load-edit.php', 'openai_tools_handle_category_description_bulk_actions');
add_action('load-edit-tags.php', 'openai_tools_handle_category_description_bulk_actions');
function openai_tools_handle_category_description_bulk_actions()
{
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit_category_description') {
        $category_ids = isset($_REQUEST['delete_tags']) ? $_REQUEST['delete_tags'] : [];
        if (empty($category_ids)) {
            return;
        }

        $category_ids = array_map('intval', $category_ids);
        // $category_ids = array_filter($category_ids, function ($product_id) {
        //     return get_post_type($product_id) === 'product_cat';
        // });

        if (empty($category_ids)) {
            return;
        }

        $redirect_url = admin_url('admin.php?page=category_description_bulk_editor&category_ids=' . implode(',', $category_ids));
        wp_redirect($redirect_url);
        exit;
    }
}

function category_description_bulk_editor_page()
{
    $category_ids = isset($_GET['category_ids']) ? explode(',', urldecode($_GET['category_ids'])) : [];
    $category_ids = array_map('intval', $category_ids);

    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'include' => $category_ids,
    ]);

    if (empty($category_ids) || empty($categories)) {
?>
        <div class="wrap intro">
            <h1>Descriptions Bulk Editor</h1>
            <p>
            <ul>
                <li class="intro-step">
                    View the <a href="/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product">Category List</a> and select categories you want to edit
                </li>
                <li class="intro-step">Select <span style=" background: white; border: solid 1px; border-color: #8c8f94; box-shadow: none; border-radius: 3px; padding: 0 24px 0 8px; line-height: 2; color: black; ">Edit Description with AI</span> from the <select>
                        <option value="-1">Bulk actions</option>
                        <option value="trash">Delete</option>
                        <option value="edit_category_description">Edit Description with AI</option>
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

        $category_items = [];
        foreach ($categories as $category) {
            $category_items[] = get_term($category->term_id, 'product_cat');
        }
            ?><div class="wrap"><h1>Category Descriptions Bulk Editor</h1><form method="post" action=""><table class="openai-table wp-list-table widefat fixed striped"><thead><tr><th style="width: 100px; text-align: center;"><input type="checkbox" id="select-all" style="margin: 0 10px 0 0;">ID</th><th>Category</th><th>Description</th><th><div class="openai-tools" style="padding: 4px;width: fit-content;border-radius: 5px;background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%);"><input id="ai-catdesc-prompt" placeholder="Prompt for new content" style="border-radius: 3px;border: solid 1px grey;width: 100%;margin: 0 0 6px 0;padding-inline: 6px;height: 36px;"><div><?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?><a id="ai-cat-desc-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a></div></div></th></tr></thead><tbody><?php foreach ($category_items as $category) : ?><tr><td style="width: 100px; text-align: center;" class="category-id"><input type="checkbox" name="category_ids[]" value="<?php echo $category->term_id; ?>"><span><?php echo $category->term_id; ?></span></td><td class="category-name"><span class="name"><?php echo $category->name; ?></span><div class="action-buttons" style="margin-top: 6px;"><a href="<?php echo get_term_link($category->term_id); ?>" target="_blank" class="button">View</a><a href="<?php echo get_edit_term_link($category->term_id, 'product_cat'); ?>" target="_blank" class="button" style="margin-left: 5px;">Edit</a></div></td><td><?php echo $category->description; ?></td><td><textarea class="description-textarea" name="category_description_<?php echo $category->term_id; ?>" id="category_description_<?php echo $category->term_id; ?>" rows="6" style="width: 100%";

            ></textarea></td></tr><?php endforeach; ?></tbody></table></form><a class="button" id="save-button" style="margin-top: 20px;">Save</a></div><style>#ai-model {
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
                $('#ai-cat-desc-btn').on('click', generate_cat_descriptions);
                var i = 0;
                var isStop = false;

                function generate_cat_descriptions() {
                    i = 0;
                    var stopBtn = $('<a id="ai-stop-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(255 98 98) 0%, rgb(0 20 24) 100%); height: 16px !important;">Stop</a>');
                    $('#ai-cat-desc-btn').after(stopBtn);
                    $('#ai-cat-desc-btn').hide();

                    $('#ai-stop-btn').on('click', function() {
                        isStop = true;
                        $('#ai-cat-desc-btn').removeAttr('disabled');
                        $('#ai-stop-btn').remove();
                        $('#ai-cat-desc-btn').show();
                    });
                    processNextCatDescriptionRow();
                }

                function processNextCatDescriptionRow() {
                    if (isStop) {
                        isStop = false;
                        return;
                    }
                    var model = $('#ai-model').val();
                    var $rows = $('.openai-table tbody tr');
                    var $currentRow = $rows.eq(i);
                    var category_name = $currentRow.find('.category-name .name').text();
                    var prompt = $('#ai-catdesc-prompt').val();
                    var $cat_description_textarea = $currentRow.find('.description-textarea');
                    $cat_description_textarea.addClass('active');
                    var model_group = $('#ai-model').find('option:selected').parent().attr('label');

                    if (category_name) {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'ai_tools_generate_short_description',
                                model: model,
                                prompt: prompt,
                                title: category_name,
                                model_group: model_group
                            },
                            success: function(response) {
                                console.log(response);
                                if (response.success && response.value) {
                                    $cat_description_textarea.val(response.value);
                                }
                                $cat_description_textarea.removeClass('active');
                            },
                            complete: function() {
                                i++;
                                if (i < $rows.length) {
                                    setTimeout(() => {
                                        processNextCatDescriptionRow();
                                    }, 500);
                                } else {
                                    $('#ai-desc-loading').remove();
                                    $('#ai-cat-desc-btn').show();
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
                        action: 'ai_tools_save_product_category_descriptions',
                        category_ids: [],
                        cat_descriptions: []
                    };
                    $('.description-textarea').each(function() {
                        if (!$(this).closest('tr').find('.category-id input').is(':checked') || !$(this).val()) {
                            return;
                        }
                        data.category_ids.push($(this).closest('tr').find('.category-id input').val());
                        data.cat_descriptions.push($(this).val());
                    });

                    if (data.category_ids.length === 0) {
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
                            alert('Failed to save category descriptions');
                        }
                    });
                });

                $('#select-all').on('change', function() {
                    var isChecked = $(this).prop('checked');
                    $('.category-id input').prop('checked', isChecked);
                });
            });
        </script>
    <?php

}
