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

class ContentForm implements AdminPage {
	public static function getConfiguration(): AdminPageConfiguration {
		return new AdminPageConfiguration(
			key: 'smolblog-content-form',
			pageTitle: 'Smolblog - Content',
			menuTitle: 'Add Content',
			parentKey: 'smolblog',
		);
	}

	private array $formData = [];

	public function __construct(
		private WordPressEnvironment $env,
		private ContentTypeRegistry $types,
		private FormBuilder $builder,
	) {}

	// private static array $

	public function handleForm(): void {
		echo '<h3>Post array:</h3><pre><code>';
		print_r($_POST);
		echo '</code></pre>';

		// $shaped = $builder->shapeInputForClass(class: Content::class, input: $_POST);
		// echo '<h3>Shaped:</h3><pre><code>';
		// print_r($shaped);
		// echo '</code></pre>';

		// echo '<h3>Parsed:</h3><pre><code>';
		// try {
		// 	print_r(Content::deserializeValue($shaped));
		// } catch (Throwable $e) {
		// 	echo $e->getMessage();
		// }
		// echo '</code></pre>';
	}

	public function displayPage(): void {
		$activeType = null; // Can set based on existing data?

		$types = $this->types->availableContentTypes();
		$activeType ??= array_keys($types)[0];
	?>

	<form
		class="sb-autogen" method="post"
		action="<?php echo admin_url('admin.php?page=' . static::getConfiguration()->key) ?>"
	>
		<ul class="nav nav-pills" id="myTab" role="tablist">
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

		<?php echo $this->builder->fieldsetForClass(Content::class); ?>
		<?php submit_button('Save'); ?>

		<script>
			jQuery(document).ready(() => {
				jQuery('button[data-bs-toggle="tab"]').each((ind, btn) => {
					console.log('Set event handler', btn);
					btn.addEventListener('shown.bs.tab', (event) => {
						const newType = jQuery(event.target).data('smolblog-content-type');
						jQuery('#body-active-tab').val(newType);
						console.log('Type updated', newType);
					});
				});
				console.log('Ran ready function.');
			});
			console.log('Loaded function.');
		</script>

	</form>

		<?php
	}
}