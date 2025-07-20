<?php

namespace Smolblog\WP\Adapters;

use Smolblog\Core\Permissions\GlobalPermissionsService;
use Smolblog\Core\Permissions\SitePermissionsService;
use Smolblog\Core\User\InternalSystemUser;
use Smolblog\Foundation\Value\Fields\Identifier;

/**
 * For single-site use. Multisite coming later.
 */
class PermissionsAdapter implements SitePermissionsService, GlobalPermissionsService {
	public function __construct(private UserAdapter $users, private SiteAdapter $sites) {}

	/**
	 * Can the given user create content on the given site?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canCreateContent(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'edit_posts');
	}

	/**
	 * Can the given user edit all content on the given site (not just their own)?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canEditAllContent(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'edit_others_posts');
	}

	/**
	 * Can the given user add and remove channels for the given site?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canManageChannels(Identifier $userId, Identifier $siteId): bool {
		if (strval($userId) == InternalSystemUser::ID) {
			return true;
		}

		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'manage_options');
	}

	/**
	 * Can the given user upload media to the given site?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canUploadMedia(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'upload_files');
	}

	/**
	 * Can the given user edit all media on the given site (not just their own)?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canEditAllMedia(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'upload_files');
	}

	/**
	 * Can the given user push content to channels?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canPushContent(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'publish_posts');
	}

	/**
	 * Can the given user set user permissions?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canManagePermissions(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'promote_users');
	}

	/**
	 * Can the given user change site settings?
	 *
	 * @param Identifier $userId User to check.
	 * @param Identifier $siteId Site to check.
	 * @return boolean
	 */
	public function canManageSettings(Identifier $userId, Identifier $siteId): bool {
		$wpId = $this->wordPressUserOrFalse($userId, $siteId);
		return $wpId !== false && user_can($wpId, 'manage_options');
	}

	/**
	 * Can the given user create a new site?
	 *
	 * @param Identifier $userId User to check.
	 * @return false Creating new sites is not supported in single-site mode.
	 */
	public function canCreateSite(Identifier $userId): bool { return false; }

	public function canManageOtherConnections(Identifier $userId): bool
	{
		return false;
	}

	private function wordPressUserOrFalse(Identifier $userId, Identifier $siteId): int|false {
		if (!$this->sites->hasSiteWithId($siteId)) {
			// echo "No WP site for id $siteId";
			return false;
		}
		return $this->users->wordPressIdFromUserId($userId);
	}
}