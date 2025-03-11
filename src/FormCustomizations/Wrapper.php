<?php

namespace Smolblog\WP\FormCustomizations;

use Bootstrap;
use Wrapper as GlobalWrapper;

class Wrapper extends GlobalWrapper {
	use Bootstrap;

	public static function wordpress_css($key = ''): array|string
	{
		# bootstrap 5 css classes

		$array = [
			'alert-e' => 'notice notice-error is-dismissible inline',
			'alert-i' => 'notice notice-info is-dismissible inline',
			'alert-s' => 'notice notice-success is-dismissible inline',
			'alert-w' => 'notice notice-warning is-dismissible inline',
			'button' => 'button-secondary',
			'button-danger' => 'button-secondary',
			'button-primary' => 'button-primary',
			'button-secondary' => 'button-secondary',
			'checkbox' => '',
			'checkbox-label' => '',
			'checkbox-inline' => '',
			'div' => '',
			'error' => 'invalid-feedback',
			'file' => 'form-control',
			'form-check-input' => 'form-check-input',
			'help' => 'form-text',
			'input' => 'form-control',
			'is-invalid' => 'is-invalid',
			'is-valid' => 'is-valid',
			'label' => 'form-label',
			'link' => 'alert-link',
			'list-dl' => 'list-unstyled',
			'list-ol' => 'list-unstyled',
			'list-ul' => 'list-unstyled',
			'radio' => 'form-check-input',
			'success' => 'has-success',
			'text-error' => 'text-danger',
			'warning' => 'has-warning',
		];

		if ($key) {
			return $array[$key] ?? '';
		}

		return $array;
	}

	public function wordpress($element = '', $data = ''): string {
		return $this->bootstrap($element, $data);
	}
}

class_alias(Wrapper::class, 'MyWrappers');