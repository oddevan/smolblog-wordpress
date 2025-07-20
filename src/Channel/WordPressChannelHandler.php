<?php

namespace Smolblog\WP\Channel;

use Smolblog\Core\Channel\Entities\BasicChannel;
use Smolblog\Core\Channel\Entities\Channel;
use Smolblog\Core\Channel\Entities\ChannelHandlerConfiguration;
use Smolblog\Core\Channel\Services\ChannelHandler;
use Smolblog\Core\Content\Entities\Content;
use Smolblog\Foundation\Exceptions\CodePathNotSupported;
use Smolblog\Foundation\Value\Fields\Identifier;

class WordPressChannelHandler implements ChannelHandler {
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

	public function pushContentToChannel(Content $content, Channel $channel, Identifier $userId): void
	{
		if ($channel->handlerKey !== 'internal') {
			throw new CodePathNotSupported('Uh, WTF dude?', location: __CLASS__);
		}

		
	}
}