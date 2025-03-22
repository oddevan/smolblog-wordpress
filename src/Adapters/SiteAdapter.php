<?php

namespace Smolblog\WP\Adapters;

use Smolblog\Core\Site\Data\SiteRepo;
use Smolblog\Core\Site\Entities\Site;
use Smolblog\Foundation\Value\Fields\Identifier;
use Smolblog\Foundation\Value\Keypair;

class SiteAdapter implements SiteRepo {
	public readonly bool $isMultisite;

	public function __construct(private UserAdapter $users, ?bool $isMultisite = null) {
		$this->isMultisite = $isMultisite ?? is_multisite();
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
	public function hasSiteWithKey(string $key): bool;

	/**
	 * Get the site object for the given ID.
	 *
	 * @param Identifier $siteId Site to retrieve.
	 * @return Site
	 */
	public function siteById(Identifier $siteId): Site;

	/**
	 * Get the keypair for the given site.
	 *
	 * @param Identifier $siteId Site whose keypair to retrieve.
	 * @return Keypair
	 */
	public function keypairForSite(Identifier $siteId): Keypair;

	/**
	 * Get the IDs for users that have permissions for the given site.
	 *
	 * @param Identifier $siteId Site whose users to retrieve.
	 * @return Identifier[]
	 */
	public function userIdsForSite(Identifier $siteId): array;

	/**
	 * Get the sites belonging to a given user.
	 *
	 * @param Identifier $userId User whose sites to retrieve.
	 * @return Site[]
	 */
	public function sitesForUser(Identifier $userId): array;

	private function maybeSiteById(Identifier $siteId): ?Site {
		if (!$this->isMultisite) {
			$thisSite = $this->currentSite();
			return $thisSite->id == $siteId ? $thisSite : null;
		}
	}

	public function currentSite(): Site {
		return Site::fromJson(get_option('smolblog_site_obj'));
	}
}
