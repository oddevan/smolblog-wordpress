<?php

namespace Smolblog\WP\Adapters;

use Smolblog\Foundation\Value\Fields\Identifier;
use Smolblog\Foundation\Value\Fields\RandomIdentifier;

class UserAdapter {
	public function userIdFromWordPressId(int $wordPressId): Identifier {
		if ($wordPressId < 1) {
			return Identifier::nil();
		}

		$meta_value = get_user_meta( $wordPressId, 'smolblog_user_id', true );

		if (empty($meta_value)) {
			$new_id = new RandomIdentifier();
			update_user_meta($wordPressId, 'smolblog_user_id', $new_id->toString());

			return $new_id;
		}

		return Identifier::fromString($meta_value);
	}

	public function wordPressIdFromUserId(Identifier $userId): int {
		$results = get_users([
			'meta_key' => 'smolblog_user_id',
			'meta_value' => $userId->toString(),
		]);

		if (!empty($results)) {
			return $results[0]->ID;
		}
		return 0;
	}
}