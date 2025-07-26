<?php

namespace Smolblog\WP;

use Formr\Formr;
use Smolblog\Core;
use Smolblog\Core\Permissions\{GlobalPermissionsService, SitePermissionsService};
use Smolblog\Foundation\DomainModel;
use Smolblog\Markdown\SmolblogMarkdown;
use Smolblog\WP\FormCustomizations\Wrapper;

class Model extends DomainModel {
	const AUTO_SERVICES = [
		AdminPage\AdminPageRegistry::class,
		AdminPage\BasePage::class,
		AdminPage\ContentForm::class,
		Adapters\AuthRequestStateAdapter::class,
		Adapters\PermissionsAdapter::class,
		Adapters\SiteAdapter::class,
		Adapters\UserAdapter::class,
		Channel\WordPressChannelHandler::class,
		WordPressEnvironment::class,
		FormBuilder::class,
	];

	const SERVICES = [
		Core\Connection\Data\AuthRequestStateRepo::class => Adapters\AuthRequestStateAdapter::class,
		Core\Site\Data\SiteRepo::class => Adapters\SiteAdapter::class,
		SitePermissionsService::class => Adapters\PermissionsAdapter::class,
		GlobalPermissionsService::class => Adapters\PermissionsAdapter::class,
		SmolblogMarkdown::class => [],
	];

	public static function getDependencyMap(): array {
		$load = Wrapper::wordpress_css();
		return [
			...parent::getDependencyMap(),
			Formr::class => fn() => new Formr('wordpress'),
		];
	}
}
