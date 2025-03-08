<?php
/**
 * Smolblog
 *
 * An interface between the core Smolblog library and WordPress.
 *
 * @package Smolblog\WP
 *
 * @wordpress-plugin
 * Plugin Name:       Smolblog
 * Plugin URI:        http://github.com/smolblog/smolblog
 * Description:       WordPress + Smolblog
 * Version:           1.0.0
 * Author:            Smolblog
 * Author URI:        http://smolblog.org
 * License:           AGPL-3.0+
 * License URI:       https://www.gnu.org/licenses/agpl.html
 * Text Domain:       smolblog
 * Domain Path:       /languages
 */

use Psr\EventDispatcher\EventDispatcherInterface;
use Smolblog\Foundation\Service\Command\CommandBus;
use Smolblog\Foundation\Value\Messages\Command;
use Smolblog\WP\App;

add_filter('admin_footer_text', fn() => '<span style="color: #9cd398; font-weight: bold">Smolblog 0.4.0</span>', 2000);

add_filter('wds_required_plugins', fn($required) => array_merge($required, [
	'advanced-custom-fields/acf.php',
	'disable-comments/disable-comments.php',
]));

/**
 * A global facade for accessing Smolblog functions.
 */
class Smolblog {
	private static App $app;

	private static function instance() {
		self::$app ??= new App();
		return self::$app;
	}

	public function dispatch(mixed $event): mixed {
		return self::instance()->container->get(EventDispatcherInterface::class)->dispatch($event);
	}

	public function execute(Command $command): mixed {
		return self::instance()->container->get(CommandBus::class)->execute($command);
	}
}