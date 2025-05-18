<?php

namespace Smolblog\WP;

use BackedEnum;
use ReflectionEnum;
use Smolblog\Core\Media\Entities\Media;
use Smolblog\Foundation\Value\Fields\{DateTimeField, Identifier, Markdown, Url};
use Smolblog\Foundation\Value\Traits\ArrayType;
use Smolblog\Foundation\Value\Traits\Field;
use Smolblog\Foundation\Value\ValueProperty;

class FormBuilder {
	private static bool $mediaJsEnqueued = false;
	private static bool $mediaJsOutput = false;

	private static function mediaJs(): string {
		if (self::$mediaJsEnqueued && !self::$mediaJsOutput) {
			self::$mediaJsOutput = true;
			return <<<EOF
			<script type="text/javascript">
				function mediaLibraryFor(fieldId) {
					return (clickEvent) => {
						clickEvent?.preventDefault;
						const frame = wp.media({ title: 'Select Image', multiple: false });
						frame.on('select', (selectEvent) => {
							const attachment = frame.state().get('selection').first().toJSON();
							const field = document.getElementById(fieldId);
							field.value = attachment.id;
							const thumbnail = document.getElementById(fieldId + '_thumbnail');
							thumbnail.innerHTML = '<img src="' + attachment.sizes.thumbnail.url + '" width="50" height="50" alt="' + attachment.alt + '">'; 
						});
						frame.open();
					};
				}
			</script>
			EOF;
		}
		return '';
	}

	public function fieldsetForClass(string $class, string $prefix = ''): string {
		$reflection = $class::reflection();
		$html = "<fieldset><legend>{$class}</legend>";
		foreach ($reflection as $prop => $info) {
			$html .= $this->fieldForProperty($prefix . $prop, $info);
		}
		$html .= '</fieldset>';
		return self::mediaJs() . $html;
	}

	private function fieldForProperty(string $fieldName, ValueProperty $info): string {
		if (
			class_exists($info->type) &&
			$info->type !== Media::class &&
			!is_a($info->type, BackedEnum::class, allow_string: true) &&
			!is_a($info->type, Field::class, allow_string: true)
		) {
			// It is a class that is not a single value. Get the set.
			return $this->fieldsetForClass($info->type, $fieldName . '_');
		}

		if ($info->type === 'array') {
			return $this->repeaterField($fieldName, $info);
		}

		$fieldId = $fieldName . '_field';
		$label = "<label for='{$fieldId}'>{$fieldName}</label>";
		$element = '';

		if (is_a($info->type, BackedEnum::class, allow_string: true)) {
			$element = "<select name='{$fieldName}' id={$fieldId}>";
			foreach (self::reflectEnum($info->type) as $val => $name) {
				$element .= "<option value='{$val}'>{$name}</option>";
			}
			$element .= '</select>';
			return '<div class="form-field">' . $label . $element . '</div>';
		}

		switch ($info->type) {
			case Markdown::class:
				$element = "<textarea id='{$fieldId}' name='{$fieldName}' cols='80' rows='10'></textarea>";
				break;
			
			case Identifier::class:
				$element = "<input type='text' id='{$fieldId}' name='{$fieldName}' readonly value='Identifier'>";
				break;
			
			case DateTimeField::class:
				$element = "<input type='datetime-local' id='{$fieldId}' name='{$fieldName}'>";
				break;
			
			case Url::class:
				$element = "<input type='url' id='{$fieldId}' name='{$fieldName}'>";
				break;
			
			case 'bool':
				$element = "<input type='checkbox' id='{$fieldId}' name='{$fieldName}'>";
				break;

			case Media::class:
				$element = "<input type='hidden' id='{$fieldId}' name='{$fieldName}'><span id='{$fieldId}_thumbnail'></span>";
				$element .= "<button type='button' class='button-primary button-small' id='{$fieldId}_button' onClick=\"mediaLibraryFor('{$fieldId}')()\">Select Media</button>";
				self::$mediaJsEnqueued = true;
				break;
			
			case 'string':
			case ArrayType::TYPE_STRING: // this is stupid.
			default:
				$element = "<input type='text' id='{$fieldId}' name='{$fieldName}'>";
				break;
		}

		return '<div class="form-field">' . $label . $element . '</div>';
	}

	private function repeaterField(string $fieldName, ValueProperty $info): string {
		$html = "<fieldset><legend>{$fieldName}</legend>";
		$html .= $this->fieldForProperty($fieldName . '[]', new ValueProperty(type: $info->items));
		$html .= "</fieldset>";

		return $html;
	}

	public static function reflectEnum(string $class): array {
		$reflector = new ReflectionEnum($class);
		$cases = [];
		foreach ($reflector->getCases() as $case) {
			$cases[$case->getBackingValue()] = $case->getName();
		}
		return $cases;
	}
}

?>