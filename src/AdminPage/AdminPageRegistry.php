<?php

namespace Smolblog\WP\AdminPage;

use Formr\Formr;
use Psr\Container\ContainerInterface;
use Smolblog\Foundation\Service\Registry\Registry;
use Smolblog\Foundation\Service\Registry\RegistryKit;

class AdminPageRegistry implements Registry {
	use RegistryKit;

	public static function getInterfaceToRegister(): string {
		return AdminPage::class;
	}

	public function __construct(ContainerInterface $container, private Formr $form) {
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
					fn() => $this->showPage($pageConfig->key, title: $pageConfig->pageTitle),
					$pageConfig->position,
				);
				continue;
			}

			add_menu_page(
				$pageConfig->pageTitle,
				$pageConfig->menuTitle,
				$pageConfig->wp_capability,
				$pageConfig->key,
				fn() => $this->showPage($pageConfig->key, title: $pageConfig->pageTitle),
				$pageConfig->wp_icon,
				$pageConfig->position,
			);
		}
	}

	public function get(string $page): AdminPage {
		return $this->getService($page);
	}

	public function showPage(string $key, ?string $title = null): void {
		if ($this->form->submitted()) {
			$this->get($key)->handleForm();
		}

		if (isset($title)) {
			echo "<h1 class='wp-heading-inline'>{$title}</h1>\n<hr class='wp-heading-end'>\n";
		}

		$this->form->messages();
		$this->form->action = $_SERVER['REQUEST_URI'];

		$this->get($key)->displayPage();
	}
}