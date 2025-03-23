<?php

namespace Smolblog\WP;

use Formr\Formr;
use Smolblog\Core;
use Smolblog\Core\Permissions\{GlobalPermissionsService, SitePermissionsService};
use Smolblog\CoreDataSql\{ChannelProjection, ConnectionProjection, ContentProjection, MediaProjection};
use Smolblog\Foundation\DomainModel;
use Smolblog\WP\FormCustomizations\Wrapper;

class Model extends DomainModel {
	const AUTO_SERVICES = [
		AdminPage\AdminPageRegistry::class,
		AdminPage\BasePage::class,
		Adapters\AuthRequestStateAdapter::class,
		Adapters\PermissionsAdapter::class,
		Adapters\SiteAdapter::class,
		Adapters\UserAdapter::class,
		WordPressEnvironment::class,
	];

	const SERVICES = [
		Core\Channel\Data\ChannelRepo::class => ChannelProjection::class,
		Core\Connection\Data\AuthRequestStateRepo::class => Adapters\AuthRequestStateAdapter::class,
		Core\Connection\Data\ConnectionRepo::class => ConnectionProjection::class,
		Core\Content\Data\ContentRepo::class => ContentProjection::class,
		Core\Content\Data\ContentStateManager::class => ContentProjection::class,
		Core\Media\Data\MediaRepo::class => MediaProjection::class,
		Core\Site\Data\SiteRepo::class => Adapters\SiteAdapter::class,
		SitePermissionsService::class => Adapters\PermissionsAdapter::class,
		GlobalPermissionsService::class => Adapters\PermissionsAdapter::class,
	];

	public static function getDependencyMap(): array {
		$load = Wrapper::wordpress_css();
		return [
			...parent::getDependencyMap(),
			Formr::class => fn() => new Formr('wordpress'),
		];
	}
}
