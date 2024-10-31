<?php
$language = get_option('openai_language', 'en');

$language_options = array(
	'en' => 'English',
	'es' => 'Español',
	'fr' => 'Français',
	'de' => 'Deutsch',
	'it' => 'Italiano',
	'ja' => '日本語',
	'ko' => '한국어',
	'pt' => 'Português',
	'po' => 'Polski',
	'ru' => 'Русский',
	'tr' => 'Türkçe',
	'ar' => 'العربية',
	'hi' => 'हिन्दी',
	'th' => 'ไทย',
	'vi' => 'Tiếng Việt',
	'id' => 'Bahasa Indonesia',
	'ms' => 'Bahasa Melayu',
	'bn' => 'বাংলা',
	'gu' => 'ગુજરાતી',
	'kn' => 'ಕನ್ನಡ',
	'ml' => 'മലയാളം',
	'pa' => 'ਪੰਜਾਬੀ',
	'ta' => 'தமிழ்',
	'te' => 'తెలుగు',
	'ur' => 'اردو',
	'fa' => 'فارسی',
	'mr' => 'मराठी',
	'ne' => 'नेपाली',
	'si' => 'සිංහල',
	'sw' => 'Kiswahili',
	'am' => 'አማርኛ',
	'km' => 'ភាសាខ្មែរ',
	'lo' => 'ພາສາລາວ',
	'my' => 'ဗမာစာ',
	'zh-cn' => '简体中文',
	'zh-tw' => '繁體中文',
);

?>
<?php foreach ($language_options as $key => $value) : ?>
	<option value="<?php echo esc_attr($key); ?>" <?php selected($language, $key); ?>><?php echo esc_html($value); ?></option>
<?php endforeach; ?>