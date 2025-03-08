<?php

namespace Smolblog\WP;

use Smolblog\Core\Model as CoreModel;
use Smolblog\CoreDataSql\Model as CoreDataSqlModel;
use Smolblog\Infrastructure\AppKit;
use Smolblog\Infrastructure\Model as InfrastructureModel;
use Smolblog\Infrastructure\Registries\ServiceRegistry;
use Smolblog\WP\Model as WPModel;

final class App {
	use AppKit;

	public readonly ServiceRegistry $container;

	public function __construct()	{
		$dependencyMap = $this->buildDependencyMap([
			CoreModel::class,
			CoreDataSqlModel::class,
			InfrastructureModel::class,
			WPModel::class,
		]);

		$this->container = new ServiceRegistry(
			configuration: $dependencyMap,
			supplements: $this->buildSupplementsForRegistries(array_keys($dependencyMap)),
		);
	}
}
