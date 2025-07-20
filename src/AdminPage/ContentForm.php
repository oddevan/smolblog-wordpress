<?php

namespace Smolblog\WP\AdminPage;

use Smolblog\Core\Content\Commands\CreateContent;
use Smolblog\Core\Content\Entities\Content;
use Smolblog\Core\Content\Entities\ContentType;
use Smolblog\Core\Content\Services\ContentExtensionRegistry;
use Smolblog\Core\Content\Services\ContentTypeRegistry;
use Smolblog\Foundation\Service\Command\CommandBus;
use Smolblog\Foundation\Value\ValueProperty;
use Smolblog\WP\FormBuilder;
use Smolblog\WP\WordPressEnvironment;

class ContentForm implements AdminPage {
	public static function getConfiguration(): AdminPageConfiguration {
		return new AdminPageConfiguration(
			key: 'smolblog-content-form',
			pageTitle: 'Smolblog - Content',
			menuTitle: 'Add Content',
			parentKey: 'smolblog',
		);
	}

	public function __construct(
		private WordPressEnvironment $env,
		private ContentTypeRegistry $types,
		private ContentExtensionRegistry $extensions,
		private FormBuilder $builder,
		private CommandBus $cmd,
	) {}

	// private static array $

	public function handleForm(): void {
		if (empty($_POST['body-active'])) {
			// No form data, bail.
			return;
		}

		$input = $_POST;
		$activeType = $_POST['body-active'];
		$input['body'] = $_POST['body'][$activeType];
		$input['body']['type'] = $this->types->typeClassFor($activeType);
		unset($input['body-active']);

		$contentReflection = Content::reflection();
		$shaperClass = [
			'publishTimestamp' => $contentReflection['publishTimestamp'],
			'canonicalUrl' => $contentReflection['canonicalUrl'],
		];
		$shaperClass['body'] = $contentReflection['body']->with(type: $this->types->typeClassFor($activeType));
		$shaped = $this->builder->shapeInputForClass(class: $shaperClass, input: $input);

		$extensions = $this->extensions->availableContentExtensions();
		$extensionKeys = array_keys($extensions);
		$shaperClass = array_combine(
			$extensionKeys,
			array_map(fn($key) => new ValueProperty(
				name: $extensions[$key],
				type: $this->extensions->extensionClassFor($key),
			), $extensionKeys)
		);

		$shaped['extensions'] = $this->builder->shapeInputForClass($shaperClass, $_POST['extensions']);
		array_walk($shaped['extensions'], fn(&$val, $key) => $val['type'] = $shaperClass[$key]->type);

		$shaped['userId'] = $this->env->getUserId();
		$shaped['siteId'] = $this->env->getSiteId();

		// Create a Content object. This makes sure everything is valid and does
		// all the deserialization we need to create the Command.
		$content = Content::deserializeValue($shaped);

		$this->cmd->execute(
			new CreateContent(
				userId: $this->env->getUserId(),
				body: $content->body,
				siteId: $this->env->getSiteId(),
				publishTimestamp: $content->publishTimestamp ?? null,
				extensions: $content->extensions,
			)
		);
	}

	public function displayPage(): void {
		$contentFields = Content::reflection();
	?>

	<form
		class="sb-autogen" method="post"
		action="<?php echo admin_url('admin.php?page=' . static::getConfiguration()->key) ?>"
	>
		<?php $this->contentTypeForm(); ?>

		<div class="accordion" id="content-accordion">
			<div class="accordion-item">
				<h2 class="accordion-header">
					<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#content-accordion-base" aria-expanded="true" aria-controls="content-accordion-base">
						Content Details
					</button>
				</h2>
				<div id="content-accordion-base" class="accordion-collapse collapse show" data-bs-parent="#content-accordion">
					<div class="accordion-body">
						<?php echo $this->builder->fieldsetForClass(
							class: [
								'publishTimestamp' => $contentFields['publishTimestamp'],
								'canonicalUrl' => $contentFields['canonicalUrl'],
							],
							hideLegend: true,
						); ?>
					</div>
				</div>
			</div>
			<?php foreach ($this->extensions->availableContentExtensions() as $key => $name): ?>
			<div class="accordion-item">
				<h2 class="accordion-header">
					<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#content-accordion-<?php echo $key; ?>" aria-expanded="false" aria-controls="content-accordion-<?php echo $key; ?>">
						<?php echo $name; ?>
					</button>
				</h2>
				<div id="content-accordion-<?php echo $key; ?>" class="accordion-collapse collapse" data-bs-parent="#content-accordion">
					<div class="accordion-body">
						<?php echo $this->builder->fieldsetForClass(
							class: $this->extensions->extensionClassFor($key),
							prefix: "extensions[{$key}]",
							hideLegend: true,
						); ?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		
		<?php submit_button('Save'); ?>

	</form>

		<?php
	}

	private function contentTypeForm(?string $only = null) {
		$activeType = null;

		$types = $this->types->availableContentTypes();
		if (isset($only) && array_key_exists($only, $types)) {
			$types = [ $only => $types[$only] ]; // basically an array_filter, but simpler.
		}

		$activeType ??= array_keys($types)[0];

		?>
			<ul class="nav nav-pills" role="tablist">
			<?php foreach ($types as $key => $display) : ?>
				<?php $isActive = ($key === $activeType); ?>
				<li class="nav-item" role="presentation">
					<button
						class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
						id="<?php echo $key; ?>-tab" data-bs-toggle="tab"
						data-bs-target="#<?php echo $key; ?>-tab-pane" type="button" role="tab"
						aria-controls="<?php echo $key; ?>-tab-pane"
						aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>"
						data-smolblog-content-type="<?php echo $key; ?>"
					>
						<?php echo $display; ?>
					</button>
				</li>
			<?php endforeach; ?>
			</ul>
			<div class="tab-content" id="myTabContent">
			<?php foreach (array_keys($types) as $key) : ?>
				<?php $isActive = ($key === $activeType); ?>
				<div
					class="tab-pane fade<?php echo $isActive ? ' show active' : ''; ?>"
					id="<?php echo $key; ?>-tab-pane" role="tabpanel"
					aria-labelledby="<?php echo $key; ?>-tab" tabindex="0"
				>
					<?php echo $this->builder->fieldsetForClass(
						class: $this->types->typeClassFor($key),
						prefix: "body[{$key}]",
						hideLegend: true,
					); ?>
				</div>
			<?php endforeach; ?>
			</div>

			<input id="body-active-tab" type="hidden" name="body-active" value="<?php echo $activeType; ?>" />
			<script>
				jQuery(document).ready(() => {
					jQuery('button[data-bs-toggle="tab"]').each((ind, btn) => {
						btn.addEventListener('shown.bs.tab', (event) => {
							const newType = jQuery(event.target).data('smolblog-content-type');
							jQuery('#body-active-tab').val(newType);
						});
					});
				});
			</script>
		<?php
	}
}