<?php
/**
 * Plugin Name: Image Metadata Generator
 * Plugin URI:  https://fourthcoastweb.com
 * Description: Immediately generates accessibility-compliant alt text, titles, and descriptions for media library images, using the latest Google Gemini Flash models.
 * Version:     1.0.0
 * Author:      Fourth Coast Web
 * Author URI:  https://fourthcoastweb.com
 * Architect/Engineer: Andrew Hickman
 * Portfolio: https://andrewhickman.me/
 * Text Domain: media-meta-generator
 * License:     MIT
 */

namespace Media_Meta_Generator;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Constants
define('MEDIA_META_GENERATOR_VERSION', '1.0.0');
define('MEDIA_META_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('MEDIA_META_GENERATOR_URL', plugin_dir_url(__FILE__));
define('MEDIA_META_GENERATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Included classes
require_once MEDIA_META_GENERATOR_PATH . 'includes/class-media-meta-generator-admin.php';
require_once MEDIA_META_GENERATOR_PATH . 'includes/class-media-meta-generator-gemini-client.php';
require_once MEDIA_META_GENERATOR_PATH . 'includes/class-media-meta-generator-media-hooks.php';

/**
 ** Initialize the plugin
 */
function init()
{
	// Initialize admin
	if (is_admin()) {
		$admin = new Admin();
		$admin->init();

		// Initialize frontend hooks (AJAX & Enqueue)
		$media_hooks = new Media_Hooks();
		$media_hooks->init();
	}

	// Initialize Gemini client (Service Layer)
	$client = new Gemini_Client();
}
add_action('plugins_loaded', __NAMESPACE__ . '\init');

