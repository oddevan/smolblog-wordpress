<?php

namespace Smolblog\WP;

use BackedEnum;
use ReflectionEnum;
use Smolblog\Core\Content\Extensions\Tags\Tags;
use Smolblog\Core\Content\Extensions\Warnings\Warnings;
use Smolblog\Core\Media\Entities\Media;
use Smolblog\Foundation\Value\Fields\{DateTimeField, Identifier, Markdown, Url};
use Smolblog\Foundation\Value\Traits\ArrayType;
use Smolblog\Foundation\Value\Traits\Field;
use Smolblog\Foundation\Value\ValueProperty;

class FormBuilder {
	public function fieldsetForClass(string $class, ?string $prefix = null): string {
		$reflection = $class::reflection();
		$html = "<fieldset><legend>{$class}</legend>";
		foreach ($reflection as $prop => $info) {
			$html .= $this->fieldForProperty($prefix ? "{$prefix}[{$prop}]" : $prop, $info);
		}
		$html .= '</fieldset>';
		return $html;
	}

	public function shapeInputForClass(string $class, mixed $input): mixed {
		$reflection = $class::reflection();
		$shaped = $input;

		foreach ($reflection as $prop => $info) {
			if (!isset($shaped[$prop])) {
				continue;
			}

			$shaped[$prop] = match ($info->type) {
				Tags::class => ['tags' => array_map(fn($tag) => $tag['item'], $input[$prop]['tags'])],
				Warnings::class => ['warnings' => array_map(
					fn($warn) => ['content' => $warn['content'], 'mention' => !empty($warn['mention'])],
					$input[$prop]['warnings']
				)],
				default => $shaped[$prop],
			};
		}

		return $shaped;
	}

	private function fieldForProperty(string $fieldName, ValueProperty $info): string {
		if (
			class_exists($info->type) &&
			$info->type !== Media::class &&
			!is_a($info->type, BackedEnum::class, allow_string: true) &&
			!is_a($info->type, Field::class, allow_string: true)
		) {
			// It is a class that is not a single value. Get the set.
			return $this->fieldsetForClass($info->type, $fieldName);
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
		$html = "<div class='repeater'><fieldset data-repeater-list='{$fieldName}'><legend>{$fieldName}</legend>";
		$html .= '<div data-repeater-item>';
		$html .= $this->fieldForProperty('[item]', new ValueProperty(type: $info->items));
		$html .= '<button data-repeater-delete type="button" aria-label="Remove"><span class="dashicons dashicons-no"></span></button>';
		$html .= '</div>';
		$html .= '<button data-repeater-create type="button" aria-label="Add"><span class="dashicons dashicons-plus"></span></button>';
		$html .= "</fieldset></div>";

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