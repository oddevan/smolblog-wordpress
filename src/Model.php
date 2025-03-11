<?php

namespace Smolblog\WP;

use Formr\Formr;
use Smolblog\Foundation\DomainModel;
use Smolblog\WP\FormCustomizations\Wrapper;

class Model extends DomainModel {
	const AUTO_SERVICES = [
		AdminPage\AdminPageRegistry::class,
		AdminPage\BasePage::class,
	];

	public static function getDependencyMap(): array {
		$load = Wrapper::wordpress_css();
		return [
			...parent::getDependencyMap(),
			Formr::class => fn() => new Formr('wordpress')
		];
	}
}