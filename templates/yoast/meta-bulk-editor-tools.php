<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    jQuery(document).ready(function($) {
        setTimeout(function() {
            $('#description #col_row_action').html(`
			<div style="display:flex;align-items: center;"><div class="openai-tools" style="display:flex; padding: 4px; border-radius: 5px; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%); flex-direction: column;">
            <input id="ai-meta-desc-prompt" placeholder="Prompt" style="border-radius: 3px; border: solid 1px grey; margin: 0 0 4px 0 !important; padding: 0 2px; width: fit-content;height: 28px;">
            <div style="display: flex; justify-content: space-between;">
            <?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?>
            <a id="ai-meta-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a></div></div>
            </div>`);
            $('#description #ai-meta-btn').on('click', generate_meta_descriptions);
        }, 500);
        setTimeout(function() {
            $('#title #col_row_action').html(`
			<div style="display:flex;align-items: center;"><div class="openai-tools" style="display:flex; padding: 4px; border-radius: 5px; background: linear-gradient(90deg, rgb(107 98 255) 0%, rgba(0, 212, 255, 1) 100%); flex-direction: column;">
            <input id="ai-meta-title-prompt" placeholder="Prompt" style="border-radius: 3px; border: solid 1px grey; margin: 0 0 4px 0 !important; padding: 0 2px; width: fit-content;height: 28px;">
            <div style="display: flex; justify-content: space-between;">
            <?php include OPENAI_TOOLS_DIR . 'templates/ai-model-selector.php'; ?><a id="ai-meta-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(149 142 255) 0%, rgb(0 20 24) 100%); height: 16px !important;">AI</a></div></div>
            </div>`);
            $('#title #ai-meta-btn').on('click', generate_meta_titles)
        }, 500);
    });

    var i = 1;
    var isStop = false;

    function generate_meta_descriptions() {
        var $stopBtn = $('<a id="ai-stop-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(255 98 98) 0%, rgb(0 20 24) 100%); height: 16px !important;">Stop</a>');
        $('#description #ai-meta-btn').after($stopBtn);
        $('#description #ai-meta-btn').hide();
        $('#ai-stop-btn').on('click', function() {
            isStop = true;
            $('#description #ai-meta-btn').removeAttr('disabled');
            $('#ai-stop-btn').remove();
            $('#description #ai-meta-btn').show();
        });
        $('#description #ai-meta-btn').removeAttr('disabled');
        processNextDescriptionRow();
    }

    function generate_meta_titles() {
        var $stopBtn = $('<a id="ai-stop-btn" class="ai button" style="margin-left: 5px;height: fit-content; border-radius: 5px;color: white; font-weight: bold; border: none; background: linear-gradient(90deg, rgb(255 98 98) 0%, rgb(0 20 24) 100%); height: 16px !important;">Stop</a>');
        $('#title #ai-meta-btn').after($stopBtn);
        $('#title #ai-meta-btn').hide();
        $('#ai-stop-btn').on('click', function() {
            isStop = true;
            $('#title #ai-meta-btn').removeAttr('disabled');
            $('#ai-stop-btn').remove();
            $('#title #ai-meta-btn').show();
        });
        i = 1;
        $('#title #ai-meta-btn').removeAttr('disabled');
        processNextTitleRow();
    }

    function processNextTitleRow() {
        if (isStop) {
            isStop = false;
            return;
        }
        var model = $('#title #col_row_action #ai-model').val();
        var model_group = $('#title #col_row_action #ai-model').find('option:selected').parent().attr('label');
        var $titles = $('.wpseo_bulk_titles tr');
        var $currentTitle = $titles.eq(i);
        var title = $currentTitle.find('.col_page_title strong').text();
        var $newTitleInput = $currentTitle.find('.col_new_yoast_seo_title input');

        $newTitleInput.addClass('active');
        if (title) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'ai_tools_generate_meta_title',
                    prompt: $('#title #ai-meta-title-prompt').val(),
                    model: model,
                    model_group: model_group,
                    title: title
                },
                success: function(response) {
                    if (response && response.value) {
                        $newTitleInput.val(response.value);
                    }
                },
                error: function(xhr, status, error) {
                    console.log(error);
                },
                complete: function() {
                    $newTitleInput.removeClass('active');
                    i++;
                    if (i < $titles.length) {
                        setTimeout(processNextTitleRow, 200);
                    }
                }
            });
        } else {
            $('#title #ai-meta-btn').removeAttr('disabled');
            $('#title #ai-meta-btn').show();
            $('#ai-stop-btn').remove();
        }
    }

    function processNextDescriptionRow() {
        if (isStop) {
            isStop = false;
            return;
        }
        var model = $('#description #col_row_action #ai-model').val();
        var model_group = $('#description #col_row_action #ai-model').find('option:selected').parent().attr('label');
        var $descriptions = $('.wpseo_bulk_descriptions tr');
        var $currentDescription = $descriptions.eq(i);
        var title = $currentDescription.find('.col_page_title strong').text();
        var $newMetadescTextarea = $currentDescription.find('.col_new_yoast_seo_metadesc textarea');

        $newMetadescTextarea.addClass('active');
        if (title) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'ai_tools_generate_meta_description',
                    prompt: $('#description #ai-meta-desc-prompt').val(),
                    model: model,
                    model_group: model_group,
                    title: title
                },
                success: function(response) {
                    if (response && response.value) {
                        $newMetadescTextarea.val(response.value);
                    }
                },
                error: function(xhr, status, error) {
                    console.log(error);
                },
                complete: function() {
                    $newMetadescTextarea.removeClass('active');
                    i++;
                    if (i < $descriptions.length) {
                        setTimeout(processNextDescriptionRow, 200);
                    }
                }
            });
        } else {
            $('#description #ai-meta-btn').removeAttr('disabled');
            $('#description #ai-meta-btn').show();
            $('#ai-stop-btn').remove();
        }
    }
</script>

<style>
    #ai-model {
        max-width: 120px;
    }

    #sidebar-container.wpseo_content_cell,
    .yoast_premium_upsell,
    div#yoast-helpscout-beacon {
        display: none !important;
    }

    :root {
        --loading-deg: 0;
    }

    .wpseo_bulk_titles .col_new_yoast_seo_title input.active,
    .wpseo_bulk_descriptions .col_new_yoast_seo_metadesc textarea.active {
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