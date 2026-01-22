<?php

namespace Media_Meta_Generator;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class "Gemini_Client"
 * Handles interactions with the Google Gemini API
 */
class Gemini_Client
{
	const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Generates alt text and description for a given attachment
	 *
	 * @param int    $attachment_id      The ID of the media attachment
	 * @param string $keywords           Optional keywords to guide the AI
	 * @param bool   $update_alt         Whether to generate Alt Text
	 * @param bool   $update_title       Whether to generate Title
	 * @param bool   $update_description Whether to generate Description
	 * @return array|\WP_Error      Array with 'alt_text', 'title', and 'description' keys, or WP_Error on failure
	 */
	public function generate_alt_text($attachment_id, $keywords = '', $include_keywords_in_alt = false, $include_keywords_in_title = false, $include_keywords_in_desc = false)
	{
		$api_key = get_option('media_meta_generator_gemini_api_key');

		if (empty($api_key)) {
			return new \WP_Error('missing_key', __('Gemini API Key is missing. Please configure it in Tools > Media Meta Generator.', 'media-meta-generator'));
		}

		$file_path = get_attached_file($attachment_id);

		if (!$file_path || !file_exists($file_path)) {
			return new \WP_Error('file_not_found', __('Image file not found.', 'media-meta-generator'));
		}

		$mime_type = get_post_mime_type($attachment_id);

		// Fallback for missing MIME type
		if (!$mime_type) {
			$mime_type = mime_content_type($file_path) ?: 'image/jpeg';
		}

		// Check strict file size limits (Gemini has limits, but PHP memory is frequently the bottleneck)
		if (filesize($file_path) > 10 * 1024 * 1024) { // 10MB limit check
			return new \WP_Error('file_too_large', __('Image exceeds 10MB limit.', 'media-meta-generator'));
		}

		$image_data = file_get_contents($file_path);
		$base64_data = base64_encode($image_data);

		// Construct the prompt
		$prompt_parts = ["Analyze this image."];
		$sanitized_keywords = !empty($keywords) ? sanitize_text_field($keywords) : '';

		// Alt Text
		$alt_prompt = "Provide a concise, Answer Engine and SEO-friendly alternative text (max 15 words) suitable for screen readers.";
		if ($include_keywords_in_alt && $sanitized_keywords) {
			$alt_prompt .= " Ensure the keywords '{$sanitized_keywords}' are integrated into this text.";
		}
		$prompt_parts[] = $alt_prompt;

		// Title
		$title_prompt = "Provide a short, catchy title for this image (max 5 words).";
		if ($include_keywords_in_title && $sanitized_keywords) {
			$title_prompt .= " Ensure the keywords '{$sanitized_keywords}' are integrated into this title.";
		}
		$prompt_parts[] = $title_prompt;

		// Description
		$desc_prompt = "Provide a detailed visual description of this image (max 50 words).";
		if ($include_keywords_in_desc && $sanitized_keywords) {
			$desc_prompt .= " Ensure the keywords '{$sanitized_keywords}' are integrated into this description.";
		}
		$prompt_parts[] = $desc_prompt;

		$prompt_text = implode(" ", $prompt_parts);
		$prompt_text .= " Return the result strictly as a JSON object with keys: 'alt_text', 'title', 'description'.";

		// Build payload
		$body = [
			'contents' => [
				[
					'parts' => [
						['text' => $prompt_text],
						[
							'inline_data' => [
								'mime_type' => $mime_type,
								'data' => $base64_data,
							],
						],
					],
				],
			],
			'generationConfig' => [
				'response_mime_type' => 'application/json',
			],
		];

		return $this->send_request($body, $api_key);
	}

	/**
	 * Tests the API connection with a simple text prompt
	 *
	 * @param string $api_key The API key to test
	 * @return bool|\WP_Error True on success, WP_Error on failure
	 */
	public function test_api_connection($api_key)
	{
		if (empty($api_key)) {
			return new \WP_Error('missing_key', __('API Key is empty.', 'media-meta-generator'));
		}

		$body = [
			'contents' => [
				[
					'parts' => [
						['text' => 'Hello. Reply with "OK".'],
					],
				],
			],
		];

		$result = $this->send_request($body, $api_key, false); // False = no JSON parsing expected

		if (is_wp_error($result)) {
			return $result;
		}

		return true;
	}

	/**
	 * Sends the request to Gemini API
	 *
	 * @param array  $body        The request body
	 * @param string $api_key     The API key
	 * @param bool   $expect_json Whether to parse the response as specific JSON schema
	 * @return array|bool|\WP_Error
	 */
	private function send_request($body, $api_key, $expect_json = true)
	{
		$response = wp_remote_post(
			self::API_URL . '?key=' . $api_key,
			[
				'body' => json_encode($body),
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'timeout' => 30,
			]
		);

		if (is_wp_error($response)) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		// Specific error handling
		if ($response_code !== 200) {
			$error_message = 'Gemini API Error (' . $response_code . ')';

			$data = json_decode($response_body, true);
			if (!empty($data['error']['message'])) {
				$error_message .= ': ' . $data['error']['message'];
			}

			if ($response_code === 429) {
				$error_message = __('Quota exceeded. Please check your Google Cloud billing or wait.', 'media-meta-generator');
			} elseif ($response_code === 400) {
				$error_message = __('Bad Request. Please check your API key and input.', 'media-meta-generator');
			} elseif ($response_code === 403) {
				$error_message = __('Permission denied. API Key may be invalid or restricted.', 'media-meta-generator');
			}

			return new \WP_Error('api_error', $error_message);
		}

		// For the test connection, we just need 200 OK
		if (!$expect_json) {
			return true;
		}

		// For generation, we need the parsed JSON
		$data = json_decode($response_body, true);

		if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
			$raw_json = $data['candidates'][0]['content']['parts'][0]['text'];
			$parsed_json = json_decode($raw_json, true);

			if (json_last_error() === JSON_ERROR_NONE) {
				$result = [];
				if (isset($parsed_json['alt_text']))
					$result['alt_text'] = sanitize_text_field($parsed_json['alt_text']);
				if (isset($parsed_json['title']))
					$result['title'] = sanitize_text_field($parsed_json['title']);
				if (isset($parsed_json['description']))
					$result['description'] = sanitize_textarea_field($parsed_json['description']);
				return $result;
			} else {
				return new \WP_Error('json_parse_error', __('Failed to parse AI response. The model may have returned unstructured text.', 'media-meta-generator'));
			}
		}

		return new \WP_Error('empty_response', __('Gemini returned an empty response.', 'media-meta-generator'));
	}
}
