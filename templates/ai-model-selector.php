<?php
$disable_submit = (get_option('openai_api_key', '') == '' && get_option('groq_api_key', '') == '') ? 'disabled' : '';
$diable_openai = get_option('openai_api_key', '') == '' ? 'disabled' : '';
$diable_groq = get_option('groq_api_key', '') == '' ? 'disabled' : '';
$diable_siliconflow = get_option('siliconflow_api_key', '') == '' ? 'disabled' : '';
$siliconflow_api_key = get_option('siliconflow_api_key', '');
$siliconflow_custom_model = get_option('siliconflow_custom_model', 'meta-llama/Meta-Llama-3-70B-Instruct');
$default_model = get_option('ai_tools_default_model', 'gp-3.5-turbo');
?>
<select id="ai-model" name="ai-model">
    <optgroup label="OpenAI" <?php echo $diable_openai; ?>>
        <option value="gpt-3.5-turbo" <?php echo ($default_model == 'gpt-3.5-turbo') ? 'selected' : ''; ?>>GPT-3.5-Turbo</option>
        <option value="gpt-4o-mini" <?php echo ($default_model == 'gpt-4o-mini') ? 'selected' : ''; ?>>GPT-4o mini</option>
        <option value="gpt-4" <?php echo ($default_model == 'gpt-4') ? 'selected' : ''; ?>>GPT-4</option>
        <option value="gpt-4o" <?php echo ($default_model == 'gpt-4o') ? 'selected' : ''; ?>>GPT-4o</option>
    </optgroup>
    <optgroup label="Groq" <?php echo $diable_groq; ?>>
        <option value="llama3-70b-8192" <?php echo ($default_model == 'llama3-70b-8192') ? 'selected' : ''; ?>>LLaMA3 70b</option>
        <option value="mixtral-8x7b-32768" <?php echo ($default_model == 'mixtral-8x7b-32768') ? 'selected' : ''; ?>>Mixtral 8x7b</option>
        <option value="gemma-7b-it" <?php echo ($default_model == 'gemma-7b-it') ? 'selected' : ''; ?>>Gemma 7b </option>
        <option value="gemma2-9b-it" <?php echo ($default_model == 'gemma2-9b-it') ? 'selected' : ''; ?>>Gemma2 9b </option>
    </optgroup>
    <optgroup label="Siliconflow" <?php echo $diable_siliconflow; ?>>
        <option value="<?php echo $siliconflow_custom_model; ?>" <?php echo ($default_model == $siliconflow_custom_model) ? 'selected' : ''; ?>><?php echo $siliconflow_custom_model; ?></option>
    </optgroup>
</select>