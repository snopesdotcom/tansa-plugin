<?php
/**
 * Plugin Name: Tansa
 * Plugin URI: "https://wordpress.org/plugins/tansa/"
 * Version: 5.0.1.12
 * Author: Tansa Systems AS
 * Author URI: https://www.tansa.com 
 * Description:TANSA IS AN ADVANCED text proofing system that can process thousands of words per second. Not only will it correct nearly all spelling, usage, style, punctuation and hyphenation errors in the blink of an eye, it also ensures that everyone in your organization follows a common set of rules.
 * License: GPLv2 or later */

$settingsSectionId = 'settings_section';
$settingsMenuSlugId = 'settings_page_slug';
$serverUrlSettingsFieldId = 'tansa_server_url';
$licenseKeySettingsFieldId = 'tansa_license_key';
$readUserNameOptionSettingsFieldId = 'tansa_user_name_option';
$tansaDevServerURL = 'https://d02.tansa.com/';
$emailOptionValue = 'email'; // user_email
$userNameOptionValue = 'username'; // user_login

/*
	Sends extension info to plugin.
*/
function register_tansa_extension_info() {
	global $serverUrlSettingsFieldId, $licenseKeySettingsFieldId, $readUserNameOptionSettingsFieldId, $tansaDevServerURL, $userNameOptionValue ;

	$tansaFolderName = 'Tansa'; 
	$licenseKey =  get_option($licenseKeySettingsFieldId); 
	$tansaServerURL = get_option($serverUrlSettingsFieldId);
	$readUserNameOption = get_option($readUserNameOptionSettingsFieldId);

	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$tansaIniFilePath = getenv('programdata') . DIRECTORY_SEPARATOR . $tansaFolderName . DIRECTORY_SEPARATOR . 'TS4.ini'; 
		if (file_exists ($tansaIniFilePath)) {
			$tansaIniFileData = parse_ini_file($tansaIniFilePath, true);
			$licenseKey = $tansaIniFileData['License']['key']; 
			$tansaServerURL = reset($tansaIniFileData['WebClient']); 
		}
	}

	if(empty($tansaServerURL)){
		$tansaServerURL = $tansaDevServerURL;
	}

	$current_user = wp_get_current_user();
	$wpUserId = $current_user -> data -> user_email; // default is email address.
	if($readUserNameOption == $userNameOptionValue){
		$wpUserId = $current_user -> data -> user_login;
	}
	
	wp_register_script('register_tansa_extension_info', null); 
	$variables = array (
		'wpVersion' => get_bloginfo('version'), 
		'version' => '5.0.1.12', 
		'parentAppLangCode' => get_locale(), 
		'wpUserId' => $wpUserId, 
		'licenseKey' => $licenseKey, 
		'tansaServerURL' => $tansaServerURL,
		'pluginPath' => plugins_url('dist', __FILE__));
	wp_localize_script('register_tansa_extension_info', 'tansaExtensionInfo', $variables); 
	wp_enqueue_script('register_tansa_extension_info'); 
}


/*
	Adds common js files for both tinymce and gutenberg extension.
*/
function register_common_js_tansa() {
	wp_enqueue_script('tansa-init-js', plugins_url('/dist/javascriptapp/init.js', __FILE__));
	wp_enqueue_style('tansa-main-css', plugins_url('/dist/javascriptapp/css/main.css', __FILE__));
}

function check_user_access() {
	// Check if the logged in WordPress User can edit Posts or Pages
	// Check if the logged in WordPress User has the Visual Editor enabled
	if ( (! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' )) || get_user_option( 'rich_editing' ) !== 'true'  ) {
		return false;
	}
	return true;
}

/**
 * Loads Tansa plugin for gutenberg
 */
function load_tansa_gutenberg() {

	if(check_user_access()){
		$blockPath = '/dist/gutenberg/sidebar.js'; 
		$stylePath = '/dist/gutenberg/sidebar.css'; 
	
		register_tansa_extension_info();
		register_common_js_tansa();
		// Enqueue the bundled block JS file
		wp_enqueue_script('tansa-block-js', 
			plugins_url($blockPath, __FILE__), 
			[ 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-api'], 
			filemtime(plugin_dir_path(__FILE__) . $blockPath)); 
	
		// Enqueue frontend and editor block styles
		wp_enqueue_style('tansa-block-css', 
			plugins_url($stylePath, __FILE__), 
			'', 
			filemtime(plugin_dir_path(__FILE__) . $stylePath)); 

	}
}

/**
* Check if the current user can edit Posts or Pages, and is using the Visual Editor
* If so, add some filters so we can register our plugin
*/
function load_tansa_tinymce() {

	if(check_user_access()){
		register_tansa_extension_info(); 
		add_filter('mce_external_plugins', 'add_tansa_plugin'); 
		add_filter('mce_buttons', 'add_tansa_toolbar_button');
	}
}

/**
* Adds a TinyMCE plugin compatible JS file to the TinyMCE Visual Editor instance *  
* @param array $plugin_array Array of registered TinyMCE Plugins 
* @return array Modified array of registered TinyMCE Plugins */
function add_tansa_plugin($plugin_array) {
	register_common_js_tansa(); 
	$plugin_array['tansa'] = plugin_dir_url(__FILE__) . 'dist/tinymce/plugin.min.js'; 
	return $plugin_array; 
}

/**
* Adds a button to the TinyMCE Visual Editor which the user can click 
* to insert a link with a custom CSS class .  *  
* @param array $buttons Array of registered TinyMCE Buttons 
* @return array Modified array of registered TinyMCE Buttons */
function add_tansa_toolbar_button($buttons) {
	array_push($buttons, 'tansaButton');
	return $buttons; 
}

function tansa_plugin_create_menu() {
	//create new top-level menu
	add_menu_page('Tansa Plugin Settings', 'Tansa Settings', 'manage_options', __FILE__, 'tansa_plugin_settings_page', plugins_url('dist/img/TS.png', __FILE__) );
	//call register settings function
	add_action( 'admin_init', 'register_tansa_plugin_settings' );
}

if ( is_admin() ) {
	add_action('admin_menu', 'tansa_plugin_create_menu');
	// Hook scripts function into block editor hook
	add_action('enqueue_block_assets', 'load_tansa_gutenberg');
	//Hook for tinyMCE editor
	add_action('init', 'load_tansa_tinymce');
}

function register_tansa_plugin_settings() {
	//register our settings
	global $settingsSectionId, $tansaDevServerURL, $userNameOptionValue, $emailOptionValue;

	add_settings_section( $settingsSectionId, '', false, $GLOBALS['settingsMenuSlugId']);
	$fields = array(
        array(
            'uid' => $GLOBALS['serverUrlSettingsFieldId'],
            'label' => 'Server URL',
            'section' => $settingsSectionId,
            'type' => 'text',
            'options' => false,
            'placeholder' => 'Tansa Server URL',
            'helper' => '',
            'supplemental' => 'Default value provided for development only. For production, please set the correct URL. This value is unique to each deployment environment.',
            'default' => $tansaDevServerURL
		),
		array(
            'uid' => $GLOBALS['readUserNameOptionSettingsFieldId'],
            'label' => 'Username',
            'section' => $settingsSectionId,
            'type' => 'radio',
            'options' => array(
				'Email Address' => $emailOptionValue,
				'Username' => $userNameOptionValue,
			),
            'placeholder' => 'Select Email address or username',
            'helper' => '',
            'supplemental' => 'Use currently logged in user email address or username.',
            'default' => $emailOptionValue
        ),
		array(
            'uid' => $GLOBALS['licenseKeySettingsFieldId'],
            'label' => 'License Key',
            'section' => $settingsSectionId,
            'type' => 'text',
            'options' => false,
            'placeholder' => 'License key provided by Tansa.',
            'helper' => '',
            'supplemental' => 'Please set the correct licenseKey. This value is unique to each deployment environment.',
            'default' => ''
        )
    );

	foreach( $fields as $field ){
		global $settingsMenuSlugId;
        add_settings_field( $field['uid'], $field['label'], 'field_callback', $settingsMenuSlugId, $field['section'], $field );
        register_setting( $settingsMenuSlugId, $field['uid'] );
    }
}

function field_callback( $arguments ) {
	//printf(json_encode($arguments));
    $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
    if( !$value ) { // If no value exists
        $value = $arguments['default']; // Set to our default
    }

    // Check which type of field we want
    switch( $arguments['type'] ){
		case 'text': // If it is a text field
			printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" title="%3$s" value="%4$s" class="regular-text" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
			break;
		case 'textarea': // If it is a textarea
			printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
			break;
		case 'select': // If it is a select dropdown
			if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
				$options_markup = ’;
				foreach( $arguments['options'] as $key => $label ){
					$options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
				}
				printf( '<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $options_markup );
			}
			break;
		case 'radio': // If it is a radio button
			if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
				$options_markup = '';
				foreach( $arguments['options'] as $key => $optionValue ){
					$options_markup .= sprintf('<li><input name="%1$s" type="%2$s" title="%3$s" value="%4$s" id="%4$s" %5$s /> <label for="%4$s" style="vertical-align: initial;" >%6$s</label> </li>', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $optionValue, checked($optionValue, $value, false), $key);	
				}
				printf('<ul style="margin:0px;">%1$s</ul>', $options_markup);
			}
			break;
	}

    // If there is help text
    if( $helper = $arguments['helper'] ){
        printf( '<span class="helper"> %s</span>', $helper ); // Show it
    }

    // If there is supplemental text
    if( $supplimental = $arguments['supplemental'] ){
        printf( '<p class="description">%s</p>', $supplimental ); // Show it
    }
}

function tansa_plugin_settings_page() {
	include 'settings.php';
}