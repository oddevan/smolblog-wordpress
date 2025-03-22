<?php

namespace Smolblog\WP\AdminPage;

use Smolblog\Foundation\Value\Fields\Url;

class AdminEnvironment {
	public function getAdminUrl(string $key): Url {
		return new Url(get_admin_url(null, 'admin.php?page=' . $key));
	}
}