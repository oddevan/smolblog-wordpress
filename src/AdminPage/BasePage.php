<?php

namespace Smolblog\WP\AdminPage;

use Formr\Formr;
use Smolblog;
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

	private array $formData = [];

	public function __construct(
		private Formr $form,
		private WordPressEnvironment $env,
		private CommandBus $cmd,
		private ContentDataService $content,
	) {}

	/*
	public function handleForm(): void {
		// get our form values and assign them to a variable
    $data = $this->form->fastpost([
			'body_text' => ['Note','required|max[300]'],
			'extensions_tags_tags' => ['Tags'],
		]);

		$this->formData = [
			'userId' => $this->env->getUserId()->toString(),
			'body' => ['text' => $data['body_text'], 'type' => Note::class],
			'siteId' => $this->env->getSiteId()->toString(),
			'extensions' => ['tags' => [
					'type' => Tags::class,
					'tags' => array_map(fn($str) => trim($str), explode(',', $data['extensions_tags_tags']))
				]
			],
		];

		$command = CreateContent::deserializeValue($this->formData);
		$this->cmd->execute($command);

    // show a success message if no errors
    if($this->form->ok()) {
        $this->form->success_message = "Validation passed.";
    }
	}
		*/
	public function handleForm(): void {
		$builder = new FormBuilder();

		echo '<h3>Post array:</h3><pre><code>';
		print_r($_POST);
		echo '</code></pre>';

		$shaped = $builder->shapeInputForClass(class: Content::class, input: $_POST);
		echo '<h3>Shaped:</h3><pre><code>';
		print_r($shaped);
		echo '</code></pre>';

		echo '<h3>Parsed:</h3><pre><code>';
		try {
			print_r(Content::deserializeValue($shaped));
		} catch (Throwable $e) {
			echo $e->getMessage();
		}
		echo '</code></pre>';
	}

	public function displayPage(): void {
		$builder = new FormBuilder();
		echo '<p>The future of blogging awaits! This ain\'t it, though.</p>';

		// $this->form->action = '';
		// $this->form->fastform([
		// 	'textarea' => 'body_text,Note',
		// 	'text' => 'extensions_tags_tags,Tags',
		// ]);

		echo '<hr>';
		$notes = $this->content->contentList(
			siteId: $this->env->getSiteId(),
			userId: $this->env->getUserId(),
		);
		?>

		<h3>Latest Notes</h3>

		<ol>
			<?php foreach ($notes as $note) : ?>
				<li>
					<p><?= $note->body->text ?></p>
					<p>Posted <?= $note->publishTimestamp?->object?->format('F n, Y') ?? 'never' ?></p>
			<?php endforeach; ?>
		</ol>

		<hr>

		<h3>Debug</h3>

		<form class="sb-autogen" method="post" action="<?php echo admin_url('admin.php?page=' . static::getConfiguration()->key) ?>">
			<?php echo $builder->fieldsetForClass(Content::class); ?>
			<?php submit_button('Save'); ?>
		</form>

		<?php
	}
}