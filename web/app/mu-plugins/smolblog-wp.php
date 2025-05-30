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
use Smolblog\WP\AdminPage\AdminPageRegistry;
use Smolblog\WP\App;

add_filter('admin_footer_text', fn() => '<span style="color: #9cd398; font-weight: bold">Smolblog 0.4.0</span>', 2000);

add_filter('wds_required_plugins', fn($required) => array_merge($required, [
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

	/**
	 * Initilize any necessary hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Load Admin pages.
		add_action('admin_menu', fn() => self::get(AdminPageRegistry::class)->register());
		// Load media library scripts.
		add_action('admin_enqueue_scripts', function() {
			wp_enqueue_media();
			wp_enqueue_script(
				'jquery.repeater',
				'https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js',
				['jquery'],
				null,
			);

			wp_add_inline_script('jquery.repeater', <<<EOF
				jQuery(document).ready(function () {
						jQuery('.repeater').repeater({
								initEmpty: true,
								show: function () {
										jQuery(this).slideDown();
								},
								hide: function (deleteElement) {
										if(confirm('Are you sure you want to delete this element?')) {
												jQuery(this).slideUp(deleteElement);
										}
								},
						})
				});
			EOF);
		});
	}

	/**
	 * Get a service from the ServiceRegistry (DI container).
	 *
	 * @template SRV
	 * @param class-string<SRV> $service Fully-qualified class name to retrieve.
	 * @return SRV Entry.
	 */
	public static function get(string $service): mixed {
		return self::instance()->container->get($service);
	}

	/**
	 * Dispatch an event object.
	 * 
	 * @template EVN
	 * @param EVN $event Event to dispatch.
	 * @return EVN $event after dispatch.
	 */
	public static function dispatch(mixed $event): mixed {
		return self::get(EventDispatcherInterface::class)->dispatch($event);
	}

	/**
	 * Execute a command object
	 *
	 * @param Command $command Command to execute.
	 * @return mixed Return value of $command if any.
	 */
	public static function execute(Command $command): mixed {
		return self::get(CommandBus::class)->execute($command);
	}
}

Smolblog::init();
