<?php

namespace Smolblog\WP;

use Smolblog\Foundation\DomainModel;

class Model extends DomainModel {
	const AUTO_SERVICES = [
		AdminPage\AdminPageRegistry::class,
		AdminPage\BasePage::class,
	];
}