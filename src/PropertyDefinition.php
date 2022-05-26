<?php

namespace Stanejoun\FlamePHP;

class PropertyDefinition
{
	public string $name = '';
	public string $type = '';
	public bool $required = false;
	/** @var callable|null */
	public mixed $constraints = null;
	public string $typeErrorMessage = '';
	public string $requiredErrorMessage = '';
	public array $subPropertiesDefinitions = [];

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): PropertyDefinition
	{
		$this->name = $name;
		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type, ?string $customErrorMessage = null, ?array $subPropertiesDefinitions = []): PropertyDefinition
	{
		if (!empty($customErrorMessage)) {
			$this->typeErrorMessage = $customErrorMessage;
		}
		if (!empty($subPropertiesDefinitions)) {
			$this->subPropertiesDefinitions = $subPropertiesDefinitions;
		}
		$this->type = $type;
		return $this;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function setRequired(bool $required, ?string $customErrorMessage = null): PropertyDefinition
	{
		if (!empty($customErrorMessage)) {
			$this->requiredErrorMessage = $customErrorMessage;
		}
		$this->required = $required;
		return $this;
	}

	public function getConstraints(): mixed
	{
		return $this->constraints;
	}

	public function setConstraints(mixed $constraints): PropertyDefinition
	{
		$this->constraints = $constraints;
		return $this;
	}

	public function getTypeErrorMessage(): string
	{
		return $this->typeErrorMessage;
	}

	public function setTypeErrorMessage(string $typeErrorMessage): PropertyDefinition
	{
		$this->typeErrorMessage = $typeErrorMessage;
		return $this;
	}

	public function getRequiredErrorMessage(): string
	{
		return $this->requiredErrorMessage;
	}

	public function setRequiredErrorMessage(string $requiredErrorMessage): PropertyDefinition
	{
		$this->requiredErrorMessage = $requiredErrorMessage;
		return $this;
	}

	public function getSubProperties(): array
	{
		return $this->subPropertiesDefinitions;
	}

	public function setSubProperties(array $subPropertiesDefinitions): PropertyDefinition
	{
		$this->subPropertiesDefinitions = $subPropertiesDefinitions;
		return $this;
	}

	public function hasSubProperties(): bool
	{
		if (!empty($this->subPropertiesDefinitions)) {
			return true;
		}
		return false;
	}
}
