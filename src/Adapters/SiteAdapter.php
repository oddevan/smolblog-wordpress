<?php

namespace Smolblog\WP\Adapters;

use Smolblog\Core\Site\Data\SiteRepo;
use Smolblog\Core\Site\Entities\Site;
use Smolblog\Foundation\Exceptions\CodePathNotSupported;
use Smolblog\Foundation\Value\Fields\Identifier;
use Smolblog\Foundation\Value\Keypair;

/**
 * For single-site use. Multisite coming later.
 */
class SiteAdapter implements SiteRepo {
	public function __construct(private UserAdapter $users) {
		if (is_multisite()) {
			throw new CodePathNotSupported(__CLASS__ . ' cannot be used in a multisite environment.');
		}
	}

	/**
	 * Return true if a site with the given ID exists.
	 *
	 * @param Identifier $siteId ID to check.
	 * @return boolean
	 */
	public function hasSiteWithId(Identifier $siteId): bool {
		return $this->siteById($siteId) !== null;
	}

	/**
	 * Return true if a site with the given key exists.
	 *
	 * @param string $key Key to check.
	 * @return boolean
	 */
	public function hasSiteWithKey(string $key): bool {
		$thisSite = $this->currentSite();
		return $thisSite->key === $key;
	}

	/**
	 * Get the site object for the given ID.
	 *
	 * @param Identifier $siteId Site to retrieve.
	 * @return Site
	 */
	public function siteById(Identifier $siteId): ?Site {
		$thisSite = $this->currentSite();
		return $thisSite->id == $siteId ? $thisSite : null;
	}

	/**
	 * Get the keypair for the given site.
	 *
	 * @param Identifier $siteId Site whose keypair to retrieve.
	 * @return Keypair
	 */
	public function keypairForSite(Identifier $siteId): Keypair {
		$site = $this->siteById($siteId);
		return $site->keypair;
	}

	/**
	 * Get the IDs for users that have permissions for the given site.
	 *
	 * @param Identifier $siteId Site whose users to retrieve.
	 * @return Identifier[]
	 */
	public function userIdsForSite(Identifier $siteId): array {
		$site = $this->currentSite();
		if ($siteId != $site->id) {
			return [];
		}
		return array_map(fn($usr) => $this->users->userIdFromWordPressId($usr->ID), get_users());
	}

	/**
	 * Get the sites belonging to a given user.
	 *
	 * @param Identifier $userId User whose sites to retrieve.
	 * @return Site[]
	 */
	public function sitesForUser(Identifier $userId): array {
		$site = $this->currentSite();
		if (in_array($userId, $this->userIdsForSite($site->id))) {
			return [$site];
		}
		return [];
	}

	public function currentSite(): Site {
		return Site::fromJson(get_option('smolblog_site_obj'));
	}
}
