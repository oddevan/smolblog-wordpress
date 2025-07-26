<?php

namespace Smolblog\WP\Channel;

use Psr\EventDispatcher\EventDispatcherInterface;
use Smolblog\Core\Channel\Entities\BasicChannel;
use Smolblog\Core\Channel\Entities\Channel;
use Smolblog\Core\Channel\Entities\ChannelHandlerConfiguration;
use Smolblog\Core\Channel\Events\ContentPushedToChannel;
use Smolblog\Core\Channel\Services\ChannelHandler;
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

class WordPressChannelHandler implements ChannelHandler, EventListenerService {
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

	public function __construct(
		private EventDispatcherInterface $events,
		private ContentRepo $repo,
		private UserAdapter $users,
		private SmolblogMarkdown $md
	)	{
		
	}

	public function pushContentToChannel(Content $content, Channel $channel, Identifier $userId): void
	{
		if ($channel->handlerKey !== 'internal') {
			throw new CodePathNotSupported('Uh, WTF dude?', location: __CLASS__);
		}

		$slug = sanitize_title($content->title());
		$url = new Url(get_home_url(
			null, //blog_id, will need to modify for multisite. Use $content->siteId.
			"/{$content->type()}/{$slug}/",
		));

		$contentToPush = $content;
		$processId = null;
		// If no canonical URL set, create it. Eventually Core should handle this in a more canonical way.
		if (!isset($content->canonicalUrl)) {
			$processId = new RandomIdentifier();

			$this->events->dispatch(
				new ContentCanonicalUrlSet(
					url: $url,
					aggregateId: $content->siteId,
					userId: $userId,
					entityId: $content->id,
					processId: $processId,
				)
			);

			// Get the updated Content object.
			$contentToPush = $this->repo->contentById($content->id) ?? $content->with(canonicalUrl: $url);
		}


		// Fire the push event.
		$this->events->dispatch(
			new ContentPushedToWordPress(
				content: $contentToPush,
				channelId: $channel->getId(),
				userId: $userId,
				aggregateId: $contentToPush->siteId,
				url: $url,
				processId: $processId,
			)
		);
	}

	#[ProjectionListener]
	public function onContentPushedToWordPress(ContentPushedToWordPress $event) {
		global $wpdb;

		$wpId = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'smolblog_content_id' AND meta_value = %s",
				$event->entityId?->toString(),
			)
		);
		$content = $event->content;

		$tags = [];
		if (isset($content->extensions['tags'])) {
			/** @var Tags */
			$tagObj = $content->extensions['tags'];
			$tags = $tagObj->tags;
		}

		wp_insert_post([
			'id' => $wpId ?? 0,
			'post_author' => $this->users->wordPressIdFromUserId($content->userId),
			'post_content' => $this->contentToHtml($content),
			'post_title' => $content->title(),
			'post_status' => 'publish',
			'post_type' => 'sb-' . $content->type(),
			'tags_input' => $tags,
			'meta_input' => ['smolblog_content_id' => $content->id->toString()]
		]);
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