<?php

namespace Smolblog\WP\AdminPage;

use Smolblog\Foundation\Exceptions\InvalidValueProperties;
use Smolblog\Foundation\Value\Traits\ServiceConfiguration;
use Smolblog\Foundation\Value\Traits\ServiceConfigurationKit;

readonly class AdminPageConfiguration implements ServiceConfiguration {
	use ServiceConfigurationKit;

	public function __construct(
		string $key,
		public string $pageTitle,
		public string $menuTitle,
		public string $wp_capability = 'manage_options',
		public ?string $parentKey = null,
		public ?string $wp_icon = null,
		public ?int $position = null,
	) {
		if (!isset($parentKey) && !isset($wp_icon)) {
			throw new InvalidValueProperties('Icons are required for top-level pages.');
		}
		if (isset($parentKey) && isset($wp_icon)) {
			throw new InvalidValueProperties('Submenu pages cannot have icons.');
		}
		$this->key = $key;
	}
}