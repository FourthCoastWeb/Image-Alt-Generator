<?php

namespace Media_Meta_Generator;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class "Media_Hooks"
 * Handles frontend assets and AJAX requests
 */
class Media_Hooks
{

	/**
	 * Initialize hooks
	 */
	public function init()
	{
		add_action('admin_enqueue_scripts', [$this, 'enqueue_media_assets']);
		add_action('wp_ajax_media_meta_generator_generate_alt', [$this, 'handle_ajax_generation']);
	}

	/**
	 * Enqueue Backbone extension script
	 */
	public function enqueue_media_assets()
	{
		// Only enqueue on screens where the media modal is likely to be used
		// Ideally we check get_current_screen(), but media modal can be loaded almost anywhere
		if (!wp_script_is('media-views')) {
			wp_enqueue_media();
		}

		wp_enqueue_script(
			'media-meta-generator-media',
			MEDIA_META_GENERATOR_URL . 'assets/js/media.min.js',
			['media-views'], // This dependency is crucial
			MEDIA_META_GENERATOR_VERSION,
			true
		);

		// Localize data for JavaScript
		wp_localize_script(
			'media-meta-generator-media',
			'media_meta_generator_vars',
			[
				'nonce' => wp_create_nonce('media_meta_generator_media_nonce'),
				'strings' => [
					'generating' => __('Just a sec...', 'media-meta-generator'),
					'generate' => __('Generate Alt, Title, and Description', 'media-meta-generator'),
					'error' => __('Error: ', 'media-meta-generator'),
					'invalid_filetype' => __('Metadata can only be generated for images with these filetypes: .jpg, .jpeg, .png, .webp, .avif, .gif', 'media-meta-generator'),
				],
				'pluginUrl' => MEDIA_META_GENERATOR_URL
			]
		);

		wp_enqueue_style(
			'media-meta-generator-media-css',
			MEDIA_META_GENERATOR_URL . 'assets/css/media.min.css',
			[],
			MEDIA_META_GENERATOR_VERSION
		);
	}

	/**
	 * Handle AJAX request for generation
	 */
	public function handle_ajax_generation()
	{
		check_ajax_referer('media_meta_generator_media_nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Permission denied.', 'media-meta-generator')]);
		}

		$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
		$keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';

		// Get inclusion flags (control keyword injection only)
		$include_keywords_in_alt = isset($_POST['include_keywords_in_alt']) ? filter_var($_POST['include_keywords_in_alt'], FILTER_VALIDATE_BOOLEAN) : false;
		$include_keywords_in_title = isset($_POST['include_keywords_in_title']) ? filter_var($_POST['include_keywords_in_title'], FILTER_VALIDATE_BOOLEAN) : false;
		$include_keywords_in_desc = isset($_POST['include_keywords_in_description']) ? filter_var($_POST['include_keywords_in_description'], FILTER_VALIDATE_BOOLEAN) : false;

		if (!$attachment_id) {
			wp_send_json_error(['message' => __('Invalid Attachment ID.', 'media-meta-generator')]);
		}

		// Instantiate client and generate
		$client = new Gemini_Client();
		// Always generate all fields, but pass flags for keyword injection
		$result = $client->generate_alt_text($attachment_id, $keywords, $include_keywords_in_alt, $include_keywords_in_title, $include_keywords_in_desc);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		// Update the attachment metadata in WordPress immediately (server-side persistence)

		if (!empty($result['alt_text'])) {
			update_post_meta($attachment_id, '_wp_attachment_image_alt', $result['alt_text']);
		}

		$post_update = ['ID' => $attachment_id];
		$should_update = false;

		if (!empty($result['description'])) {
			$post_update['post_content'] = $result['description'];
			$should_update = true;
		}

		if (!empty($result['title'])) {
			$post_update['post_title'] = $result['title'];
			$should_update = true;
		}

		if ($should_update) {
			wp_update_post($post_update);
		}

		wp_send_json_success($result);
	}
}