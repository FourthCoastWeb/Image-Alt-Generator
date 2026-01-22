<?php

namespace Media_Meta_Generator;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class "Admin"
 * Handles the administrative interface and settings
 */
class Admin
{

	/**
	 * Initialize the admin hooks
	 */
	public function init()
	{
		add_action('admin_menu', [$this, 'add_tools_page']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('wp_ajax_media_meta_generator_test_connection', [$this, 'handle_test_connection']);
		add_filter('plugin_action_links_' . MEDIA_META_GENERATOR_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
	}

	/**
	 * Register the Tools submenu page
	 */
	public function add_tools_page()
	{
		add_management_page(
			__('Media Metadata Generator Configuration', 'media-meta-generator'),
			__('Media Metadata Generator', 'media-meta-generator'),
			'manage_options',
			'media-meta-generator-config',
			[$this, 'render_tools_page']
		);
	}

	/**
	 * Add "Plugin settings" link to the plugin action links
	 *
	 * @param array $links Existing plugin action links
	 * @return array Modified plugin action links
	 */
	public function add_plugin_action_links($links)
	{
		$settings_link = '<a href="' . esc_url(admin_url('tools.php?page=media-meta-generator-config')) . '">' . __('Settings', 'media-meta-generator') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Enqueue admin scripts for the Media Meta Generator admin page
	 * 
	 * @param string $hook The current admin page hook
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ('tools_page_media-meta-generator-config' !== $hook) {
			return;
		}

		wp_enqueue_script('jquery');
		wp_add_inline_script('jquery', '
			jQuery(document).ready(function($) {
				$(".media-meta-generator-test-connection").on("click", function(e) {
					e.preventDefault();
					var $btn = $(this);
					var $msg = $(".media-meta-generator-connection-msg");
					var apiKey = $("input[name=\'media_meta_generator_gemini_api_key\']").val();

					if (!apiKey) {
						$msg.css("color", "red").text("' . __('Please enter an API Key first.', 'media-meta-generator') . '");
						return;
					}

					$btn.prop("disabled", true).text("' . __('Testing...', 'media-meta-generator') . '");
					$msg.text("");

					$.post(ajaxurl, {
						action: "media_meta_generator_test_connection",
						api_key: apiKey,
						nonce: "' . wp_create_nonce('media_meta_generator_admin_nonce') . '"
					})
					.done(function(response) {
						if (response.success) {
							$msg.css("color", "green").text(response.data.message);
						} else {
							$msg.css("color", "red").text(response.data.message);
						}
					})
					.fail(function() {
						$msg.css("color", "red").text("' . __('Request failed.', 'media-meta-generator') . '");
					})
					.always(function() {
						$btn.prop("disabled", false).text("' . __('Test Connection', 'media-meta-generator') . '");
					});
				});
			});
		');
	}

	/**
	 * Render the Tools page HTML
	 */
	public function render_tools_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields('media_meta_generator_options_group');
				do_settings_sections('media-meta-generator-config');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings()
	{
		register_setting(
			'media_meta_generator_options_group',
			'media_meta_generator_gemini_api_key',
			[
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => '',
			]
		);

		add_settings_section(
			'media_meta_generator_api_section',
			__('API Configuration', 'media-meta-generator'),
			[$this, 'render_section_info'],
			'media-meta-generator-config'
		);

		add_settings_field(
			'media_meta_generator_gemini_api_key',
			__('Google Gemini API Key', 'media-meta-generator'),
			[$this, 'render_api_key_field'],
			'media-meta-generator-config',
			'media_meta_generator_api_section'
		);
	}

	/**
	 * Render section info
	 */
	public function render_section_info()
	{
		echo '<p>' . esc_html__('Enter your Google Gemini API Key below. This key is used to generate alt text and descriptions.', 'crest-ai') . '</p>';
	}

	/**
	 * Render the API key input field
	 */
	public function render_api_key_field()
	{
		$api_key = get_option('media_meta_generator_gemini_api_key');
		?>
		<input type="password" name="media_meta_generator_gemini_api_key" value="<?php echo esc_attr($api_key); ?>"
			class="regular-text" placeholder="QwAIza..." />
		<button type="button"
			class="button button-secondary media-meta-generator-test-connection"><?php esc_html_e('Test Connection', 'media-meta-generator'); ?></button>
		<p class="description">
			<?php $ai_studio_link = 'https://aistudio.google.com/app/apikey'; ?>
			<?php
			printf(
				/* translators: %s: Link to Google AI Studio */
				esc_html__('Your key is stored securely. Do not share it. You can get a free API key from %s.', 'media-meta-generator'),
				'<a href="' . esc_url($ai_studio_link) . '" target="_blank">Google AI Studio</a>'
			);
			?>
		</p>
		<p class="media-meta-generator-connection-msg" style="font-weight:bold; margin-top:5px;"></p>
				<?php
	}

	/**
	 * Handle AJAX request to test connection
	 */
	public function handle_test_connection()
	{
		check_ajax_referer('media_meta_generator_admin_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Permission denied.', 'media-meta-generator')]);
		}

		$api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

		if (empty($api_key)) {
			wp_send_json_error(['message' => __('API Key is empty.', 'media-meta-generator')]);
		}

		$client = new Gemini_Client();
		$result = $client->test_api_connection($api_key);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		wp_send_json_success(['message' => __('Connection successful! The API key works.', 'media-meta-generator')]);
	}
}