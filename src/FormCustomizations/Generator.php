<?php

namespace Smolblog\WP\FormCustomizations;

class Generator {
	/**
	 * Generate a form definition from a value type.
	 *
	 * @param class-string<\Smolblog\Foundation\Value> $className Class to use.
	 * @return array
	 */
	public function generateFromValueType(string $className): array {
		$formDef = [];
		return $formDef;
	}
}