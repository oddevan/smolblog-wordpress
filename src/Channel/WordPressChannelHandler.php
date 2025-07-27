<?php

namespace Smolblog\WP\Channel;

use Psr\EventDispatcher\EventDispatcherInterface;
use Smolblog\Core\Channel\Data\ChannelRepo;
use Smolblog\Core\Channel\Entities\BasicChannel;
use Smolblog\Core\Channel\Entities\Channel;
use Smolblog\Core\Channel\Entities\ChannelHandlerConfiguration;
use Smolblog\Core\Channel\Entities\ContentChannelEntry;
use Smolblog\Core\Channel\Events\ContentPushedToChannel;
use Smolblog\Core\Channel\Services\ChannelHandler;
use Smolblog\Core\Channel\Services\ContentPushException;
use Smolblog\Core\Channel\Services\ProjectionChannelHandler;
use Smolblog\Core\Content\Data\ContentRepo;
use Smolblog\Core\Content\Entities\Content;
use Smolblog\Core\Content\Events\ContentCanonicalUrlSet;
use Smolblog\Core\Content\Extensions\Tags\Tags;
use Smolblog\Core\Content\Types\{
	Note\Note,
	Article\Article,
	Picture\Picture,
	Reblog\Reblog,
};
use Smolblog\Foundation\Exceptions\CodePathNotSupported;
use Smolblog\Foundation\Service\Event\EventListenerService;
use Smolblog\Foundation\Service\Event\ProjectionListener;
use Smolblog\Foundation\Value\Fields\Identifier;
use Smolblog\Foundation\Value\Fields\RandomIdentifier;
use Smolblog\Foundation\Value\Fields\Url;
use Smolblog\Markdown\SmolblogMarkdown;
use Smolblog\WP\Adapters\UserAdapter;

class WordPressChannelHandler extends ProjectionChannelHandler {
	public static function getConfiguration(): ChannelHandlerConfiguration
	{
		return new ChannelHandlerConfiguration(
			key: 'wordpress',
			displayName: 'WordPress',
			canBeCanonical: true,
		);
	}

	private static BasicChannel $internal;
	public static function internalChannel(): Channel {
		self::$internal ??= new BasicChannel(
			handler: 'wordpress',
			handlerKey: 'internal',
			displayName: get_bloginfo('name'),
			details: [],
		);
		return self::$internal;
	}

	protected const PUSH_EVENT = ContentPushedToWordPress::class;

	public function __construct(
		EventDispatcherInterface $eventBus,
		ChannelRepo $channels,
		private ContentRepo $repo,
		private UserAdapter $users,
		private SmolblogMarkdown $md
	)	{
		parent::__construct(eventBus: $eventBus, channels: $channels);
	}

	/**
	 * Push the given content to the given channel.
	 *
	 * @throws ContentPushException On failure.
	 *
	 * @param Content    $content   Content object to push.
	 * @param Channel    $channel   Channel to push object to.
	 * @param Identifier $userId    ID of the user who initiated the push.
	 * @param Identifier $processId ID of this particular push or regeneration process.
	 * @return ContentChannelEntry Information about the successfully completed push.
	 */
	protected function project(
		Content $content,
		Channel $channel,
		Identifier $userId,
		?Identifier $processId
	): ContentChannelEntry {
		global $wpdb;

		if ($channel->getId() != self::internalChannel()->getId()) {
			throw new ContentPushException(
				message: 'There should only be one internal WordPress channel, and this ain\'t it.',
				details: ['givenChannel' => $channel->serializeValue()],
			);
		}

		$wpId = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'smolblog_content_id' AND meta_value = %s",
				$content->id->toString(),
			)
		);

		$tags = [];
		if (isset($content->extensions['tags'])) {
			/** @var Tags */
			$tagObj = $content->extensions['tags'];
			$tags = $tagObj->tags;
		}

		$wpId = wp_insert_post([
			'id' => $wpId ?? 0,
			'post_author' => $this->users->wordPressIdFromUserId($content->userId),
			'post_content' => $this->contentToHtml($content),
			'post_title' => $content->title(),
			'post_status' => 'publish',
			'post_type' => 'sb-' . $content->type(),
			'tags_input' => $tags,
			'meta_input' => ['smolblog_content_id' => $content->id->toString()]
		]);

		return new ContentChannelEntry(
			contentId: $content->id,
			channelId: $channel->getId(),
			url: new Url(get_permalink($wpId)),
			details: ['id' => $wpId],
		);
	}

	private function contentToHtml(Content $content): string {
		switch ($content->type()) {
			case 'note':
			case 'article':
				/** @var Note|Article */
				$body = $content->body;
				return $this->md->parse($body->text);
			case 'picture':
				/** @var Picture */
				$body = $content->body;
				$bodyMd = "Picture!\n\n{$body->caption}";
				return $this->md->parse($bodyMd);
			case 'reblog':
				/** @var Reblog */
				$body = $content->body;
				$bodyHtml = $body->url . "\n\n";
				if (!empty($body->caption)) {
					$bodyHtml .= $this->md->parse($body->caption);
				}
				return $bodyHtml;
		}

		return '';
	}
}