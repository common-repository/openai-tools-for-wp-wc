<?php
$current_screen = get_current_screen();
if ($current_screen->base === 'post') {
?>
    <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $('#postexcerpt .handle-actions').first().prepend(`
                    <div style="padding: 6px;"><div style="display:flex;align-items: center;"><div class="openai-tools" style="display:flex; padding: 4px; border-radius: 5px; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%);">
                        <input id="ai-shortdesc-prompt" placeholder="Prompt for short description" style="min-width: 200px;border-radius: 3px;border: solid 1px grey;">
                        <?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?>
                        <a id="ai-desc-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a>
                    </div></div></div>
                `);
                $('#ai-desc-btn').on('click', function() {
                    $('#postexcerpt .handle-actions .openai-tools').before('<img id="ai-desc-loading" src="<?php echo OPENAI_TOOLS_URL . 'assets/img/loading.gif'; ?>" style="width: 28px; height: 28px; margin-right: 5px;">');
                    $('#ai-desc-btn').attr('disabled', 'disabled');
                    // get optgroup label
                    var model_group = $('#ai-model').find('option:selected').parent().attr('label');
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ai_tools_generate_short_description',
                            model: $('#postexcerpt #ai-model').val(),
                            model_group: model_group,
                            prompt: $('#ai-shortdesc-prompt').val(),
                            title: $('#title').val(),
                        },
                        success: function(response) {
                            console.log(response);
                            if (response.error) {
                                alert(response.error.message);
                            }
                            if (response.value) {
                                var textarea = document.querySelector('#wp-excerpt-wrap.html-active textarea');
                                if (textarea) {
                                    textarea.value = response.value;
                                } else {
                                    var iframe = document.querySelector('#excerpt_ifr');
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
                        },
                        complete: function() {
                            $('#ai-desc-loading').remove();
                            $('#ai-desc-btn').removeAttr('disabled');
                        }
                    });
                });
            }, 3000);

        });
    </script>
    <style>
        .postbox-header .handle-actions {
            display: flex;
            align-items: center;
        }
    </style>
<?php
}