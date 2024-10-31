<?php
$current_screen = get_current_screen();
if ($current_screen->base === 'post') {
?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.11/clipboard.min.js"></script>
    <script>
        jQuery(document).ready(function($) {
            setTimeout(function() {
                $('#yst-replacevar__use-ai-button__yoast-google-preview-description-metabox').before(`
			<div style="display:flex;align-items: center;"><div class="openai-tools" style="display:flex; padding: 4px; border-radius: 5px; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%);"><?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?><a id="ai-meta-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a></div></div>`);
                $('#ai-meta-btn').on('click', function() {
                    var model = $('.yst-replacevar #ai-model').val();
                    var model_group = $('.yst-replacevar #ai-model').find('option:selected').parent().attr('label');
                    var post_id = <?php echo get_the_ID(); ?>;
                    $('#wpseo-metabox-root .openai-tools').before('<img id="ai-meta-loading" src="<?php echo OPENAI_TOOLS_URL . 'assets/img/loading.gif'; ?>" style="width: 28px; height: 28px; margin-right: 5px;">');
                    $('#ai-meta-btn').attr('disabled', 'disabled');
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ai_tools_generate_meta_description',
                            model: model,
                            model_group: model_group,
                            post_id: post_id
                        },
                        success: function(response) {
                            console.log(response);
                            if (response != null && response.value) {
                                const yoast_wpseo_metadesc = document.getElementById('yoast_wpseo_metadesc');
                                yoast_wpseo_metadesc.value = response.value;
                                // add a "Copy" button after .ai-meta-btn 
                                $('#ai-meta-btn').after(`<a id="copy-meta-btn" data-clipboard-target="#yoast_wpseo_metadesc" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">Copy</a>`);
                                var clipboard = new ClipboardJS('#copy-meta-btn');
                                // click on .openai-tools
                                clipboard.on('success', function(e) {
                                    console.log(e);
                                    e.clearSelection();
                                    $('#copy-meta-btn').remove();
                                });

                                $('.openai-tools').click();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log(error);
                            // Handle the error here
                        },
                        complete: function() {
                            $('#ai-meta-loading').remove();
                            $('#ai-meta-btn').removeAttr('disabled');
                        }
                    });
                });
            }, 3000);

        });
    </script>
<?php
}
