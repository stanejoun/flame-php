<?php

namespace Stanejoun\FlamePHP;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ModelDescription
{
	#[ArrayOf('\Stanejoun\FlamePHP\ModelDescription')]
	public static array $DESCRIPTIONS = [];
	public static bool $DISABLE_AUTO_DATETIME = false;
	public static bool $DISABLE_CREATED_AT = false;
	public static bool $DISABLE_UPDATED_AT = false;
	public static bool $DISABLE_DELETED_AT = false;
	public static bool $DISABLE_AUTO_ENCRYPTION = false;
	public static bool $DISABLE_AUTO_TRANSLATION = false;

	private string $table = '';
	private bool $autoIncrement = true;
	private bool $softDelete = false;
	private bool $autoDatetime = true;
	#[ArrayOf('string')]
	private array $unmappedProperties = [];
	#[ArrayOf('string')]
	private array $properties = [];
	#[ArrayOf('string')]
	private array $encryptedFields = [];
	#[ArrayOf('string')]
	private array $translatedProperties = [];
	private string $translationTable = '';

	public function __construct(?array $definition = null)
	{
		if ($definition) {
			Helper::hydrate($this, $definition);
		}
	}

	public function hasEncryptedFields(): bool
	{
		return !empty($this->encryptedFields);
	}

	public function getTable(): string
	{
		return $this->table;
	}

	public function setTable(string $table): ModelDescription
	{
		$this->table = $table;
		return $this;
	}

	public function isAutoIncrement(): bool
	{
		return $this->autoIncrement;
	}

	public function setAutoIncrement(bool $autoIncrement): ModelDescription
	{
		$this->autoIncrement = $autoIncrement;
		return $this;
	}

	public function isSoftDelete(): bool
	{
		return $this->softDelete;
	}

	public function setSoftDelete(bool $softDelete): ModelDescription
	{
		$this->softDelete = $softDelete;
		return $this;
	}

	public function isAutoDatetime(): bool
	{
		return $this->autoDatetime;
	}

	public function setAutoDatetime(bool $autoDatetime): ModelDescription
	{
		$this->autoDatetime = $autoDatetime;
		return $this;
	}

	public function getUnmappedProperties(): array
	{
		return $this->unmappedProperties;
	}

	public function setUnmappedProperties(array $unmappedProperties): ModelDescription
	{
		$this->unmappedProperties = $unmappedProperties;
		return $this;
	}

	public function getProperties(): array
	{
		return $this->properties;
	}

	public function setProperties(array $properties): ModelDescription
	{
		$this->properties = $properties;
		return $this;
	}

	public function getEncryptedFields(): array
	{
		return $this->encryptedFields;
	}

	public function setEncryptedFields(array $encryptedFields): ModelDescription
	{
		$this->encryptedFields = $encryptedFields;
		return $this;
	}

	public function getTranslatedProperties(): array
	{
		return $this->translatedProperties;
	}

	public function setTranslatedProperties(array $translatedProperties): ModelDescription
	{
		$this->translatedProperties = $translatedProperties;
		return $this;
	}

	public function getTranslationTable(): string
	{
		return $this->translationTable;
	}

	public function setTranslationTable(string $translationTable): ModelDescription
	{
		$this->translationTable = $translationTable;
		return $this;
	}
}
