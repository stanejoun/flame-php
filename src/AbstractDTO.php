<?php

namespace Stanejoun\LightPHP;

abstract class AbstractDTO extends AbstractCommon
{
	public function hydrate(mixed $definition): void
	{
		Helper::hydrate($this, $definition);
	}

	public function toJson(): string
	{
		throw new \Exception('Error: "toJson" function not implemented for this DTO!');
	}

	public function toModel(): AbstractModel
	{
		throw new \Exception('Error: "toModel" function not implemented for this DTO!');
	}
}
