<?php

namespace App\Forms\Controls\TextInputBased;

use App\Forms\Controls\TextInputBased\MetronicTextInputBase;

/**
 * TagInput
 *
 * @method self setPlaceholder(string $value) Set placeholder text
 * 
 * @author Petr Poupě
 */
class TagInput extends MetronicTextInputBase
{

	public function __construct($label = NULL)
	{
		parent::__construct($label);
		$this->control->class = 'tags';
		$this->dataAttributes = [
			'Placeholder' => 'default-text',
		];
	}
}
