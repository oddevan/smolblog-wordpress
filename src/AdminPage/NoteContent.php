<?php

namespace Smolblog\WP\AdminPage;

use Smolblog\Core\Content\ContentUtilities;
use Smolblog\Core\Content\Entities\ContentType;
use Smolblog\Core\Content\Extensions\Tags\Tags;
use Smolblog\Core\Content\Extensions\Warnings\ContentWarning;
use Smolblog\Core\Content\Extensions\Warnings\Warnings;
use Smolblog\Core\Content\Types\Article\Article;
use Smolblog\Core\Content\Types\Note\Note;
use Smolblog\Core\Content\Types\Picture\Picture;
use Smolblog\Foundation\Value;
use Smolblog\Foundation\Value\Fields\DateTimeField;
use Smolblog\Foundation\Value\Fields\Identifier;
use Smolblog\Foundation\Value\Fields\Markdown;
use Smolblog\Foundation\Value\Fields\Url;
use Smolblog\Foundation\Value\Traits\SerializableValue;
use Smolblog\Foundation\Value\Traits\SerializableValueKit;

/**
 * A short, text-only message. Like a tweet.
 */
readonly class NoteContent extends Value implements SerializableValue {
	use SerializableValueKit;

	/**
	 * Construct the Note.
	 *
	 * @param Markdown $text Markdown-formatted text of the Note.
	 */
	public function __construct(
		public Article $body,
		public ?DateTimeField $publishTimestamp = null,
		public ?Url $canonicalUrl = null,
		public ?Tags $tags = null,
		public ?Warnings $warnings = null,
	) {
	}
}
