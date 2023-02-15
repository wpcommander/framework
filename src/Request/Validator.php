<?php

namespace WpCommander\Request;

use WP_REST_Request;

defined('ABSPATH') || exit;

class Validator
{
	public $errors = array();

	public function make(WP_REST_Request $wp_rest_request, array $rules, bool $return = false)
	{
		$files = $wp_rest_request->get_file_params();

		foreach ($rules as $input => $rule) {

			$exploded_rules = explode('|', $rule);
			$input_value = $wp_rest_request->get_param($input);
			$input_message_text = str_replace('_', ' ', ucfirst($input));

			foreach ($exploded_rules as $single_rule) {

				$single_rule = explode(':', $single_rule);

				switch ($single_rule[0]) {

					case 'required':
						if (empty($input_value)) {
							$this->errors[$input][] = $input_message_text . ' field is required';
						}
						continue 2; // It's not only breaking the switch case also continue the loop.

					case 'min':
						if (!empty($input_value)) {
							if (strlen($input_value) < $single_rule[1]) {
								$this->errors[$input][] = $input_message_text . ' must be a minimum of ' . $single_rule[1] . ' characters';
							}
						}
						continue 2;

					case 'max':
						if (!empty($input_value)) {
							if (strlen($input_value) > $single_rule[1]) {
								$this->errors[$input][] = $input_message_text . ' must be a maximum of ' . $single_rule[1] . ' characters';
							}
						}
						continue 2;

					case 'bool':
						if (!empty($input_value)) {
							if (!is_bool($input_value)) {
								$this->errors[$input][] = $input_message_text . ' field is must be boolean';
							}
						}

						continue 2;

					case 'file':
						if (empty($files[$input]['tmp_name'])) {
							$this->errors[$input][] = $input_message_text . ' is must be a file';
						} elseif (!empty($single_rule[1])) {
							$input_value = $files[$input];
							$allowed_extensions = explode(',', $single_rule[1]);
							$type = explode('/', $input_value['type']);
							if (empty($type[1]) || !in_array($type[1], $allowed_extensions)) {
								$this->errors[$input][] = $input_message_text . 'file allowed types are ' . $single_rule[1];
							}
						}

						continue 2;
					case 'size':
						if (!empty($files[$input]['tmp_name'])) {
							$size = $files[$input]['size'] / 1024;
							if ($size > floatval($single_rule[1])) {
								$this->errors[$input][] = $input_message_text . ' file must be less than or equal to ' . $single_rule[1] . 'kb';
							}
						}
					default:
				}
			}
		}

		if (!$return && !empty($this->errors)) {
			wp_send_json( ['errors' => $this->errors], 422 );
		}

		return $this;
	}

	public function fails()
	{
		return !empty($this->errors);
	}

	public function errors()
	{
		return $this->errors;
	}
}
