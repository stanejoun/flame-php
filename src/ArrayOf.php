<?php

namespace Stanejoun\LightPHP;

use Attribute;

#[Attribute(\Attribute::TARGET_PROPERTY)]
class ArrayOf
{
	private string $type = '';

	public function __construct($type)
	{
		$this->type = $type;
	}

	public function getType(): string
	{
		return $this->type;
	}
}