<?php
add_action('admin_menu', 'openai_tools_add_page');
function openai_tools_add_page()
{
    add_options_page(
        __('AI Tools Settings', 'openai-tools'),
        __('AI Tools', 'openai-tools'),
        'manage_options',
        'ai-tools-settings',
        'ai_tools_settings_page'
    );
}

function ai_tools_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'openai-tools'));
    }

    if (isset($_POST['openai_token'])) {
        update_option('openai_token', $_POST['openai_token']);
        echo "<div class='notice notice-success is-dismissible'><p>Settings saved!</p></div>";
    }

    $openai_api_key = get_option('openai_api_key');
    $custom_openai_domain = get_option('custom_openai_domain', 'api.openai.com');
    $groq_api_key = get_option('groq_api_key');
    $siliconflow_api_key = get_option('siliconflow_api_key');
    $woocommerce_webhook_deliver_async_disable = get_option('woocommerce_webhook_deliver_async_disable', "0");
    $enable_xml_feeds_generator = get_option('enable_xml_feeds_generator', "1");
    $enable_public_ai_tools_task = get_option('enable_public_ai_tools_task', "0");
    $enable_ai_tools_logs = get_option('enable_ai_tools_logs', "0");
    $siliconflow_custom_model = get_option('siliconflow_custom_model', 'meta-llama/Meta-Llama-3.1-70B-Instruct');
?>
    <div class="wrap">
        <h1>AI Settings</h1>
        <hr>
        <form method="post" action="options.php">
            <?php
            settings_fields('openai_settings');
            do_settings_sections('openai_settings');
            ?>
            <h2>OpenAI</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td style=" display: flex; flex-direction: row; align-items: center; "><input type="password" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" />
                        <a class="button" id="verify-openai" style=" margin: 0 0 0 10px; ">Verify</a>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Domain</th>
                    <td>
                        <input type="text" name="custom_openai_domain" placeholder="api.openai.com" value="<?php echo esc_html($custom_openai_domain) ?>" />
                    </td>
                </tr>
            </table>
            <hr>
            <h2>Groq</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td style=" display: flex; flex-direction: row; align-items: center; "><input type="password" name="groq_api_key" value="<?php echo esc_attr($groq_api_key); ?>" />
                        <a class="button" id="verify-groq" style=" margin: 0 0 0 10px; ">Verify</a>
                    </td>
                </tr>
            </table>
            <h2>Siliconflow</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td style=" display: flex; flex-direction: row; align-items: center; "><input type="password" name="siliconflow_api_key" value="<?php echo esc_attr($siliconflow_api_key); ?>" />
                        <a class="button" id="verify-siliconflow" style=" margin: 0 0 0 10px; ">Verify</a>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Model Name</th>
                    <td>
                        <input type="text" name="siliconflow_custom_model" value="<?php echo esc_attr($siliconflow_custom_model); ?>" />
                    </td>
            </table>
            <hr>
            <h2><?php _e('Preferance', 'openai-tools'); ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Default Language', 'openai-tools'); ?></th>
                    <td>
                        <select name="openai_language">
                            <?php include OPENAI_TOOLS_DIR . "templates/language-options.php" ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e("Enable XML Feeds Generator", "openai-tools"); ?></th>
                    <td>
                        <input type="checkbox" name="enable_xml_feeds_generator" value="1" <?php checked($enable_xml_feeds_generator, 1); ?> />
                        <span>
                            <?php _e("Generate product feeds and review feeds", "openai-tools"); ?>
                        </span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Disable Webhook Deliver Async', 'openai-tools'); ?></th>
                    <td>
                        <input type="checkbox" name="woocommerce_webhook_deliver_async_disable" value="1" <?php checked($woocommerce_webhook_deliver_async_disable, 1); ?> />
                        <span>
                            <?php echo sprintf(__('Checked for %s instant order notification', 'openai-tools'), '<a target="_blank" href="https://woocat.app/">WooCat</a>'); ?>
                        </span>

                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Public AI Tools Task', 'openai-tools'); ?></th>
                    <td>
                        <input type="checkbox" name="enable_public_ai_tools_task" value="1" <?php checked($enable_public_ai_tools_task, 1); ?> />
                        <span>
                            <?php _e('AI task can be requested without login.', 'openai-tools'); ?>
                            <?php _e('Learn More', 'openai-tools'); ?>
                        </span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Debugging Logs', 'openai-tools'); ?></th>
                    <td>
                        <input type="checkbox" name="enable_ai_tools_logs" value="1" <?php checked($enable_ai_tools_logs, 1); ?> />
                        <span>
                            <?php _e('WooCommerce > Status > Logs ', 'openai-tools'); ?>
                        </span>
                    </td>
                </tr>
            </table>
            <?php
            submit_button(__('Save Changes', 'openai-tools'));
            ?>
        </form>
    </div>
    <style>
        @media screen and (min-width: 768px) {
            .progress-bar {
                max-width: 180px;
            }
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #d7d7d7;
            border-radius: 5px;
            margin: 18px 0;
            box-shadow: inset 2px 2px 10px 1px #9f9f9f;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: #0073aa;
            border-radius: 5px;
            transition: width 0.2s ease-in;
        }

        .process-bar-text {
            position: relative;
            top: -18px;
            display: flex;
            justify-content: space-between;
        }

        .form-table th,
        .form-table td {
            padding: 10px;
        }
    </style>
    <script>
        window.alert_success = function(message) {
            const alert = document.createElement('div');
            alert.innerHTML = message;
            alert.style.position = 'fixed';
            alert.style.top = '50%';
            alert.style.left = '50%';
            alert.style.transform = 'translate(-50%, -50%)';
            alert.style.zIndex = '9999';
            alert.style.padding = '20px';
            alert.style.backgroundColor = '#fff';
            alert.style.color = 'green';
            alert.style.border = '1px solid green';
            alert.style.borderRadius = '5px';
            document.body.appendChild(alert);
            setTimeout(() => {
                alert.remove();
            }, 1200);

            return true;
        }

        window.alert_fail = function(message) {
            const alert = document.createElement('div');
            alert.innerHTML = message;
            alert.style.position = 'fixed';
            alert.style.top = '50%';
            alert.style.left = '50%';
            alert.style.transform = 'translate(-50%, -50%)';
            alert.style.zIndex = '9999';
            alert.style.padding = '20px';
            alert.style.backgroundColor = '#fff';
            alert.style.color = 'red';
            alert.style.border = '1px solid red';
            alert.style.borderRadius = '5px';
            document.body.appendChild(alert);
            setTimeout(() => {
                alert.remove();
            }, 3000);

            return true;
        }

        jQuery(document).ready(function($) {
            $(document).on('click', '#verify-openai', function() {
                try {
                    var openaiApiKey = $('input[name="openai_api_key"]').val();
                    if (!openaiApiKey) {
                        alert_fail('Please enter OpenAI API Key');
                        return;
                    }
                    var custom_openai_domain = $('input[name="custom_openai_domain"]').val();
                    if (!custom_openai_domain) {
                        custom_openai_domain = 'api.openai.com';
                    }
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        method: 'POST',
                        data: {
                            action: 'ai_tools_verify_api_key',
                            api_key: openaiApiKey,
                            api_domain: custom_openai_domain,
                        },
                        success: function(response) {
                            if (response.status === 200) {
                                alert_success('Verify OpenAI Success');
                            } else {
                                alert_fail(response.error.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('An error occurred:', error);
                        }
                    });
                } catch (error) {
                    console.error('An error occurred:', error);
                }
            });
            $('#verify-groq').on('click', async function() {
                try {
                    var groqApiKey = $('input[name="groq_api_key"]').val();
                    if (!groqApiKey) {
                        alert_fail('Please enter Groq API Key');
                        return;
                    }
                    var custom_groq_domain = 'api.groq.com/openai';
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        method: 'POST',
                        data: {
                            action: 'ai_tools_verify_api_key',
                            api_key: groqApiKey,
                            api_domain: custom_groq_domain,
                        },
                        success: function(response) {
                            if (response.status === 200) {
                                alert_success('Verify Groq Success');
                            } else {
                                alert_fail(response.error.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('An error occurred:', error);
                        }
                    });
                } catch (error) {
                    console.error('An error occurred:', error);
                }
            });
            $('#verify-siliconflow').on('click', async function() {
                try {
                    var siliconflowApiKey = $('input[name="siliconflow_api_key"]').val();
                    if (!siliconflowApiKey) {
                        alert_fail('Please enter Siliconflow API Key');
                        return;
                    }
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        method: 'POST',
                        data: {
                            action: 'ai_tools_verify_siliconflow_api_key',
                            model: $('input[name="siliconflow_custom_model"]').val(),
                            api_key: siliconflowApiKey
                        },
                        success: function(response) {
                            console.log(response)
                            if (response.status === 200) {
                                alert_success('Verify Siliconflow Success');
                            } else {
                                alert_fail(response.error.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('An error occurred:', error);
                        }
                    });
                } catch (error) {
                    console.error('An error occurred:', error);
                }
            });
        });
    </script>
<?php
}

add_action('admin_init', 'openai_tools_settings');
function openai_tools_settings()
{
    register_setting('openai_settings', 'openai_api_key');
    register_setting('openai_settings', 'custom_openai_domain');
    register_setting('openai_settings', 'groq_api_key');
    register_setting('openai_settings', 'siliconflow_api_key');
    register_setting('openai_settings', 'siliconflow_custom_model');
    register_setting('openai_settings', 'openai_language');
    register_setting('openai_settings', 'woocommerce_webhook_deliver_async_disable');
    register_setting('openai_settings', 'enable_xml_feeds_generator');
    register_setting('openai_settings', 'enable_ai_tools_logs');
    register_setting('openai_settings', 'enable_public_ai_tools_task');
}
