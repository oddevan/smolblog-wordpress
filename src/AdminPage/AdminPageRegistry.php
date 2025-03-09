<?php

namespace Smolblog\WP\AdminPage;

use Psr\Container\ContainerInterface;
use Smolblog\Foundation\Service\Registry\Registry;
use Smolblog\Foundation\Service\Registry\RegistryKit;

class AdminPageRegistry implements Registry {
	use RegistryKit;

	public static function getInterfaceToRegister(): string {
		return AdminPage::class;
	}

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function register(): void {
		/** @var AdminPageConfiguration */
		foreach ($this->configs as $pageConfig) {
			if (isset($pageConfig->parentKey)) {
				add_submenu_page(
					$pageConfig->parentKey,
					$pageConfig->pageTitle,
					$pageConfig->menuTitle,
					$pageConfig->wp_capability,
					$pageConfig->key,
					fn() => $this->get($pageConfig->key)->displayPage(),
					$pageConfig->position,
				);
				continue;
			}

			add_menu_page(
				$pageConfig->pageTitle,
				$pageConfig->menuTitle,
				$pageConfig->wp_capability,
				$pageConfig->key,
				fn() => $this->get($pageConfig->key)->displayPage(),
				$pageConfig->wp_icon,
				$pageConfig->position,
			);
		}
	}

	public function get(string $page): AdminPage {
		return $this->getService($page);
	}
}