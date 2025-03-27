<?php

namespace Smolblog\WP;

use Psr\Container\ContainerInterface;
use Roots\WPConfig\Config;
use Smolblog\Core\Model as CoreModel;
use Smolblog\Core\Site\Commands\CreateSite;
use Smolblog\Core\Site\Entities\Site;
use Smolblog\CoreDataSql\DatabaseEnvironment;
use Smolblog\CoreDataSql\Model as CoreDataSqlModel;
use Smolblog\Foundation\Service\KeypairGenerator;
use Smolblog\Foundation\Value\Fields\RandomIdentifier;
use Smolblog\Infrastructure\AppKit;
use Smolblog\Infrastructure\Model as InfrastructureModel;
use Smolblog\Infrastructure\Registries\ServiceRegistry;
use Smolblog\WP\Adapters\UserAdapter;
use Smolblog\WP\AdminPage\AdminPageRegistry;
use Smolblog\WP\Model as WPModel;

final class App {
	use AppKit;

	public readonly ServiceRegistry $container;

	public function __construct()	{
		global $wpdb;

		$dependencyMap = $this->buildDependencyMap([
			CoreModel::class,
			CoreDataSqlModel::class,
			InfrastructureModel::class,
			WPModel::class,
		]);
		$dependencyMap[KeypairGenerator::class] = [];

		$dependencyMap[DatabaseEnvironment::class] = [
			'props' => fn() => [
				'dbname' => Config::get('DB_NAME'),
				'user' => Config::get('DB_USER'),
				'password' => Config::get('DB_PASSWORD'),
				'host' => Config::get('DB_HOST'),
				'driver' => 'pdo_mysql',
			],
			'tablePrefix' => fn() => $wpdb->base_prefix . 'smolblog_',
		];

		$needs = $this->getUnmetDependencies($dependencyMap);
		if ($needs) {
			$html = '<h2>Configuration error: missing service implementations</h2>';
			$html .= '<p>The following interfaces are required by services in the ServiceRegistry (dependency injection container).</p>';
			foreach ($needs as $req => $allNeededBy) {
				$html .= "<h3>{$req}</h3><p>Required by:</p><ul><li>";
				$html .= implode('</li><li>', $allNeededBy);
				$html .= '</li></ul>';
			}

			wp_die($html);
		}

		$this->container = new ServiceRegistry(
			configuration: $dependencyMap,
			supplements: $this->buildSupplementsForRegistries(array_keys($dependencyMap)),
		);

		if (!is_multisite() && get_option('smolblog_site_obj') === false) {
			$siteObj = new Site(
				id: new RandomIdentifier(),
				key: 'smolblog',
				displayName: get_bloginfo('name'),
				userId: $this->container->get(UserAdapter::class)->userIdFromWordPressId(1),
				keypair: $this->container->get(KeypairGenerator::class)->generate(),
			);

			add_option('smolblog_site_obj', $siteObj->toJson());
		}
	}

	private function getUnmetDependencies($dependencyMap, $skipContainers = false): ?array {
		$availableServices = array_keys($dependencyMap);
		if (!$skipContainers) {
			$availableServices[] = ServiceRegistry::class;
			$availableServices[] = ContainerInterface::class;
		}

		$prelim = array_filter(
			array_map(fn($deps) =>
				!is_array($deps) ? null : array_filter(
					$deps,
					fn($dep) => is_string($dep) && !in_array($dep, $availableServices)
				),
				$dependencyMap
			),
			fn($map) => !empty($map),
		);

		if (empty($prelim)) {
			return null;
		}

		$results = [];
		foreach ($prelim as $reqBy => $needs) {
			if (!is_array($needs)) {
				continue;
			}
			foreach($needs as $missing) {
				$results[$missing] ??= [];
				$results[$missing][] = $reqBy;
			}
		}

		return $results;
	}
}
