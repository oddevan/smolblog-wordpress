<?php

namespace Smolblog\WP\AdminPage;

use Smolblog\Foundation\Service\Registry\ConfiguredRegisterable;

interface AdminPage extends ConfiguredRegisterable {
	/**
	 * Get the configuration for this page so it can be registered.
	 *
	 * @return AdminPageConfiguration
	 */
	public static function getConfiguration(): AdminPageConfiguration;

	/**
	 * Display the HTML for the page.
	 *
	 * @return void
	 */
	public function displayPage(): void;

	/**
	 * Handle a POST form on the page.
	 *
	 * @return void
	 */
	// public function handleForm(): void;
}