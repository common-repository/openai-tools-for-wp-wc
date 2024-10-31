<?php
$is_category_edit = false;
if (isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'product_cat') {
    $is_category_edit = true;
}
if ($is_category_edit) {
?>
    <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $('#wp-description-wrap .wp-editor-tabs').first().prepend(`
                    <div class="openai-tools" style="display:flex; padding: 4px; border-radius: 5px; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%);">
                        <input id="ai-desc-prompt" placeholder="Prompt for description" style="min-width: fit-content;border-radius: 3px;border: solid 1px grey;">
                        <?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?>
                        <a id="ai-desc-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a>
                    </div>
                `);
                $('#ai-desc-btn').on('click', function() {
                    $('#postexcerpt .handle-actions .openai-tools').before('<img id="ai-desc-loading" src="<?php echo OPENAI_TOOLS_URL . 'assets/img/loading.gif'; ?>" style="width: 28px; height: 28px; margin-right: 5px;">');
                    $('#ai-desc-btn').attr('disabled', 'disabled');
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ai_tools_generate_category_description',
                            model: $('#ai-model').val(),
                            prompt: $('#ai-desc-prompt').val(),
                            title: $('input#name').val(),
                            model_group: $('#ai-model').find('option:selected').parent().attr('label'),
                        },
                        success: function(response) {
                            console.log(response);
                            if (response.value) {
                                var textarea = document.querySelector('#wp-description-wrap.html-active textarea');
                                if (textarea) {
                                    textarea.value = response.value;
                                } else {
                                    var iframe = document.querySelector('#description_ifr');
                                    if (iframe) {
                                        var tinymce = iframe.contentWindow.document.querySelector('#tinymce');
                                        if (tinymce) {
                                            tinymce.innerText = response.value;
                                        }
                                    }
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log(error);
                            x
                        },
                        complete: function() {
                            $('#ai-desc-loading').remove();
                            $('#ai-desc-btn').removeAttr('disabled');
                        }
                    });
                });
            }, 1000);

        });
    </script>
    <style>
        .postbox-header .handle-actions {
            display: flex;
            align-items: center;
        }

        select#ai-model {
            width: 120px;
        }

        #wp-description-wrap .wp-editor-tabs {
            display: flex;
            padding-bottom: 4px;
        }

        textarea.active {
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
<?php
}
