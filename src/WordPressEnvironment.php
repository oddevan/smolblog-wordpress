<?php

namespace Smolblog\WP;

use Smolblog\Foundation\Value\Fields\{Identifier, RandomIdentifier, Url};
use Smolblog\WP\Adapters\SiteAdapter;
use Smolblog\WP\Adapters\UserAdapter;

class WordPressEnvironment {
	public function __construct(private UserAdapter $users, private SiteAdapter $sites) {
	}

	public function getAdminUrl(string $key): Url {
		return new Url(get_admin_url(null, 'admin.php?page=' . $key));
	}

	public function getUserId(?int $wordPressId = null): Identifier {
		$dbId = $wordPressId ?? get_current_user_id();
		return $this->users->userIdFromWordPressId($dbId);
	}

	public function getSiteId(?int $wordPressId = null): Identifier {
		if (is_multisite()) {
			return $this->getSiteIdMultisite($wordPressId);
		}
		return $this->getSiteIdSingle();
	}

	private function getSiteIdSingle(): Identifier {
		$site = $this->sites->currentSite();

		return $site->id;
	}

	private function getSiteIdMultisite(?int $wordPressId = null): Identifier {
		$dbId = $wordPressId ?? get_current_blog_id();
		if ($dbId < 1) {
			return Identifier::nil();
		}

		$optionValue = get_blog_option($dbId, 'smolblog_site_id', false);
		if ($optionValue === false) {
			// If the site does not have an ID, give it one.
			$new_id = new RandomIdentifier();
			add_blog_option($dbId, 'smolblog_site_id', $new_id->toString());

			return $new_id;
		}

		return Identifier::fromString($optionValue);
	}
}