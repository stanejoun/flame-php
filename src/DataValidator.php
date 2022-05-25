<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\BusinessException;

class DataValidator
{
	#[ArrayOf('\Stanejoun\LightPHP\PropertyDefinition')]
	private array $propertiesDefinitions = [];
	#[ArrayOf('string')]
	private array $errors = [];
	private mixed $data = null;
	private mixed $outputData = null;

	public function add(string $name): PropertyDefinition
	{
		return new PropertyDefinition($name);
	}

	public function validate(): self
	{
		if (!$this->isValid()) {
			throw new BusinessException($this->errors);
		}
		return $this;
	}

	public function isValid(): bool
	{
		if (empty($this->data)) {
			$this->handleRequest();
		}
		$this->errors = [];
		if (empty($this->propertiesDefinitions) || !is_array($this->propertiesDefinitions)) {
			throw new \Exception('No property definitions has been defined!');
		}
		if (empty($this->data) || (!is_object($this->data) && !is_array($this->data))) {
			throw new \Exception('No data to validate!');
		}
		$this->outputData = null;
		list($this->outputData, $this->errors) = $this->doValidate($this->data, $this->propertiesDefinitions);
		return !$this->hasErrors();
	}

	public function handleRequest(): self
	{
		if ($this->hasSubmittedData()) {
			$this->data = Request::data();
			if (!empty($_FILES)) {
				foreach ($_FILES as $inputName => $fileValues) {
					if (is_object($this->data)) {
						$this->data->{$inputName} = $inputName;
					}
					if (is_array($this->data)) {
						$this->data[$inputName] = $inputName;
					}
				}
			}
		}
		return $this;
	}

	public function hasSubmittedData(): bool
	{
		$data = Request::data();
		/** @var PropertyDefinition $propertyDefinition */
		foreach ($this->propertiesDefinitions as $propertyDefinition) {
			if ($propertyDefinition->getType() === 'file' && Request::hasFiles($propertyDefinition->getName())) {
				return true;
			} else {
				foreach ($data as $key => $value) {
					if ($propertyDefinition->getName() === $key) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function doValidate(mixed $data, array $propertyDefinitions): array
	{
		if (is_object($data)) {
			$outputData = new \stdClass();
		} else {
			$outputData = [];
		}
		$outputErrors = [];
		foreach ($data as $key => $value) {
			if (is_numeric($key)) {
				list($currentValue, $currentError) = $this->doValidate($value, $propertyDefinitions);
				$outputData[$key] = $currentValue;
				if (!empty($currentError)) {
					$outputErrors[$key] = $currentError;
				}
			}
			/** @var PropertyDefinition $propertyDefinition */
			foreach ($propertyDefinitions as $propertyDefinition) {
				if ($propertyDefinition->getName() === $key) {
					$currentValue = null;
					$currentError = null;
					$propertyDefinitionName = $propertyDefinition->getName();
					if ((is_array($value) || is_object($value)) && $propertyDefinition->hasSubProperties()) {
						if (!empty($propertyDefinition->getType()) && !empty($value)) {
							if ($propertyDefinition->getType() === 'array' && !is_array($value)) {
								$message = !empty($propertyDefinition->getTypeErrorMessage()) ? $propertyDefinition->getTypeErrorMessage() : Translator::translate('This field must be an array!');
								$outputErrors[$propertyDefinitionName] = $message;
							} else if ($propertyDefinition->getType() === 'object' && !is_object($value)) {
								$message = !empty($propertyDefinition->getTypeErrorMessage()) ? $propertyDefinition->getTypeErrorMessage() : Translator::translate('This field must be an object!');
								$outputErrors[$propertyDefinitionName] = $message;
							} else {
								list($currentValue, $currentError) = $this->doValidate($value, $propertyDefinition->getSubProperties());
							}
						}
					} else if (!$propertyDefinition->hasSubProperties()) {
						try {
							if (!empty($propertyDefinition->getType()) && !empty($value)) {
								$Method = 'valid' . ucfirst(trim($propertyDefinition->getType()));
								if (!method_exists('\Stanejoun\LightPHP\ValidatorHelper', $Method)) {
									throw new BusinessException(Translator::translate('Unable to validate this value!'));
								}
								$message = !empty($propertyDefinition->getTypeErrorMessage()) ? $propertyDefinition->getRequiredErrorMessage() : null;
								ValidatorHelper::$Method($value, $message);
							}
							if ($propertyDefinition->isRequired() && (($propertyDefinition->getType() === 'file' && !Request::hasFiles($propertyDefinition->getName())) || ($value !== 0 && empty($value)))) {
								$message = !empty($propertyDefinition->getRequiredErrorMessage()) ? $propertyDefinition->getRequiredErrorMessage() : Translator::translate('This field is required.');
								throw new BusinessException($message);
							}
							if (!empty($propertyDefinition->getConstraints()) && is_callable($propertyDefinition->getConstraints())) {
								$function = $propertyDefinition->getConstraints();
								$function($value);
							}
							$currentValue = $value;
						} catch (BusinessException $exception) {
							$currentError = $exception->getMessage();
						}
					}
					if (is_object($outputData)) {
						$outputData->{$propertyDefinitionName} = $currentValue;
					}
					if (is_array($outputData)) {
						$outputData[$propertyDefinitionName] = $currentValue;
					}
					if (!empty($currentError)) {
						$outputErrors[$propertyDefinitionName] = $currentError;
					}
				}
			}
		}
		return [$outputData, $outputErrors];
	}

	public function hasErrors(): bool
	{
		if (empty($this->errors)) {
			return false;
		}
		return true;
	}

	/**
	 * @return PropertyDefinition[]
	 */
	public function getPropertiesDefinitions(): array
	{
		return $this->propertiesDefinitions;
	}

	/**
	 * @param PropertyDefinition[] $propertiesDefinitions
	 * @return $this
	 */
	public function setPropertiesDefinitions(array $propertiesDefinitions): self
	{
		$this->propertiesDefinitions = $propertiesDefinitions;
		return $this;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function setErrors(array $errors): self
	{
		$this->errors = $errors;
		return $this;
	}

	public function getData(): mixed
	{
		return $this->outputData;
	}

	public function setData(array $data): self
	{
		$this->data = $data;
		return $this;
	}

	public function getValue(string $name): mixed
	{
		if (!empty($this->outputData)) {
			if (is_object($this->outputData) && property_exists($this->outputData, $name)) {
				return $this->outputData->{$name};
			}
			if (is_array($this->outputData) && isset($this->outputData[$name])) {
				return $this->outputData[$name];
			}
		}
		return null;
	}

	public function getEncryptedValue(string $name): ?string
	{
		$value = $this->getValue($name);
		if ($value !== null && !is_array($value) && !is_object($value)) {
			return Security::encrypt($value);
		}
		return null;
	}
}