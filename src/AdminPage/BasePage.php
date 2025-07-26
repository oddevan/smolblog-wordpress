<?php

namespace Smolblog\WP\AdminPage;

use Formr\Formr;
use Smolblog;
use Smolblog\Core\Channel\Commands\PushContentToChannel;
use Smolblog\Core\Channel\Entities\Channel;
use Smolblog\Core\Channel\Services\ChannelDataService;
use Smolblog\Core\Content\Commands\CreateContent;
use Smolblog\Core\Content\Entities\Content;
use Smolblog\Core\Content\Extensions\Tags\Tags;
use Smolblog\Core\Content\Extensions\Warnings\ContentWarning;
use Smolblog\Core\Content\Extensions\Warnings\Warnings;
use Smolblog\Core\Content\Services\ContentDataService;
use Smolblog\Core\Content\Services\ContentExtensionRegistry;
use Smolblog\Core\Content\Services\ContentTypeRegistry;
use Smolblog\Core\Content\Types\Note\Note;
use Smolblog\Core\Content\Types\Picture\Picture;
use Smolblog\Core\Content\Types\Reblog\Reblog;
use Smolblog\Foundation\Exceptions\CommandNotAuthorized;
use Smolblog\Foundation\Exceptions\EntityNotFound;
use Smolblog\Foundation\Exceptions\InvalidValueProperties;
use Smolblog\Foundation\Service\Command\CommandBus;
use Smolblog\WP\FormBuilder;
use Smolblog\WP\WordPressEnvironment;
use Throwable;

class BasePage implements AdminPage {
	public static function getConfiguration(): AdminPageConfiguration {
		return new AdminPageConfiguration(
			key: 'smolblog',
			pageTitle: 'Smolblog',
			menuTitle: 'Smolblog',
			wp_icon: 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDIwMDAgMTUwMCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMjAwMCAxNTAwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHN0eWxlIHR5cGU9InRleHQvY3NzIj4uc3Qwe2ZpbGw6Izg4RDQ5MTt9PC9zdHlsZT48Zz48cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTMzMy4zLDE2Ni43Yy0zMTEuMywwLTU3MiwyMTMuNy02NDUuNCw1MDIuMWM4MS45LDEwLjYsMTQ1LjQsNzkuOCwxNDUuNCwxNjQuNWMwLDkyLTc0LjYsMTY2LjctMTY2LjcsMTY2LjdTNTAwLDkyNS40LDUwMCw4MzMuM3M3NC42LTE2Ni43LDE2Ni43LTE2Ni43di01MDBDMjk4LjUsMTY2LjcsMCw0NjUuMSwwLDgzMy4zUzI5OC41LDE1MDAsNjY2LjcsMTUwMGMzMTEuMywwLDU3Mi0yMTMuNyw2NDUuNC01MDIuMWMtODEuOS0xMC42LTE0NS40LTc5LjgtMTQ1LjQtMTY0LjVjMC05Miw3NC42LTE2Ni43LDE2Ni43LTE2Ni43YzkyLDAsMTY2LjcsNzQuNiwxNjYuNywxNjYuN3MtNzQuNiwxNjYuNy0xNjYuNywxNjYuN3Y1MDBjMzY4LjIsMCw2NjYuNy0yOTguNSw2NjYuNy02NjYuN1MxNzAxLjUsMTY2LjcsMTMzMy4zLDE2Ni43eiBNNDE2LjcsNTAwYy00NiwwLTgzLjMtMzcuMy04My4zLTgzLjNjMC00NiwzNy4zLTgzLjMsODMuMy04My4zczgzLjMsMzcuMyw4My4zLDgzLjNDNTAwLDQ2Mi43LDQ2Mi43LDUwMCw0MTYuNyw1MDB6Ii8+PHBhdGggY2xhc3M9InN0MCIgZD0iTTc1MCw4My4zYzAtNDYtMzcuMy04My4zLTgzLjMtODMuM3YxNjYuN0M3MTIuNywxNjYuNyw3NTAsMTI5LjQsNzUwLDgzLjN6Ii8+PC9nPjwvc3ZnPg==',
			position: 3,
		);
	}

	public function __construct(
		private WordPressEnvironment $env,
		private CommandBus $cmd,
		private ContentDataService $content,
		private ContentTypeRegistry $types,
		private ChannelDataService $channels,
	) {}

	public function handleForm(): void {
		if (empty($_POST['channelId'])) {
			// No form data, bail.
			return;
		}
		
		$command = PushContentToChannel::deserializeValue([
			'userId' => $this->env->getUserId(),
			...$_POST,
		]);

		$this->cmd->execute($command);
	}

	public function displayPage(): void {
		$content = $this->content->contentList(
			siteId: $this->env->getSiteId(),
			userId: $this->env->getUserId(),
		);

		$contentTypes = $this->types->availableContentTypes();
		$channels = $this->channels->channelsForSite(
			siteId: $this->env->getSiteId(),
			userId: $this->env->getUserId(),
		);
		?>

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th scope="col">Type</th>
					<th scope="col">Title</th>
					<th scope="col">Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($content as $row) : ?>
				<tr>
					<td><?php echo $contentTypes[$row->type()]; ?></td>
					<td>
						<a href="<?php echo admin_url('admin.php?page=smolblog-content-form&edit=' . $row->id) ?>">
							<?php echo $row->title(); ?>
						</a>
					</td>
					<td>
						<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#push-<?php echo $row->id;?>">
							Push to channel
						</button>
						<div
							class="modal fade"
							id="push-<?php echo $row->id;?>"
							tabindex="-1"
							aria-labelledby="push-<?php echo $row->id;?>-label"
							aria-hidden="true"
						>
							<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
								<div class="modal-content">
									<form method="post" action="<?php echo admin_url('admin.php?page=' . static::getConfiguration()->key) ?>">
									<div class="modal-header">
										<h1 class="modal-title fs-5" id="push-<?php echo $row->id;?>-label">
											Push "<?php echo $row->title(); ?>"
										</h1>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<input type="hidden" name="contentId" value="<?php echo $row->id; ?>">
										<?php foreach ($channels as $channel) : ?>
											<?php $channelFormId = "push-{$row->id}-form-channel-{$channel->getId()}"; ?>
											<div class="form-check">
												<input class="form-check-input" type="radio" name="channelId" id="<?php echo $channelFormId; ?>" value="<?php echo $channel->getId(); ?>">
												<label class="form-check-label" for="<?php echo $channelFormId; ?>">
													<?php echo "{$channel->handler}: {$channel->displayName}"; ?>
												</label>
											</div>
										<?php endforeach; ?>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
										<button type="submit" class="btn btn-primary">Push</button>
									</div>
									</form>
								</div>
							</div>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php
	}
}