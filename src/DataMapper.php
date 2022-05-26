<?php

namespace Stanejoun\FlamePHP;

class DataMapper
{
	public function map(object &$object, mixed $definition): void
	{
		if (is_string($definition)) {
			$definition = json_decode($definition, false, 512, \JSON_THROW_ON_ERROR);
		}
		if (!($definition instanceof \stdClass) && !is_array($definition)) {
			throw new \InvalidArgumentException('DataMapper::map() requires first argument to be an object or array' . ', ' . gettype($definition) . ' given.');
		}
		$reflectionClass = new \ReflectionClass($object);
		$reflectionProperties = $reflectionClass->getProperties();
		$properties = [];
		foreach ($reflectionProperties as $reflectionProperty) {
			$properties[$reflectionProperty->getName()] = $reflectionProperty;
		}
		foreach ($definition as $propertyName => $value) {
			$propertyName = Helper::getCamelCaseName($propertyName);
			if (isset($properties[$propertyName])) {
				$typeName = $properties[$propertyName]->getType()->getName();
				if ($typeName === 'array') {
					$propertyAttributes = $properties[$propertyName]->getAttributes();
					$currentTypeName = $typeName;
					if (empty($propertyAttributes)) {
						$dockBlock = $properties[$propertyName]->getDocComment();
						if ($dockBlock) {
							$annotations = Helper::parseDocBlock($dockBlock);
							if (!empty($annotations) && isset($annotations['var']) && !empty($annotations['var'][0])) {
								$annotationTypeName = $annotations['var'][0];
								$currentTypeName = str_replace('[]', '', $annotationTypeName);
							}
						}
					} else {
						/** @var ArrayOf $arrayOf */
						$arrayOf = $propertyAttributes[0]->newInstance();
						$currentTypeName = $arrayOf->getType();
					}
					if ($this->isClassType($currentTypeName)) {
						$typeName = $arrayOf->getType();
						$values = [];
						if ($value !== null) {
							foreach ($value as $arrayValue) {
								if (is_array($arrayValue) || is_object($arrayValue)) {
									$instance = new $typeName();
									$this->map($instance, $arrayValue);
									$values[] = $instance;
								}
							}
						}
						$value = $values;
					} else if (is_string($value)) {
						$value = Helper::decodeString($value);
					}
				} else if ($typeName === 'DateTime') {
					if ($value !== null) {
						$value = new \DateTime($value);
					}
				} else if ($this->isClassType($typeName)) {
					$instance = new $typeName();
					$this->map($instance, $value);
					$value = $instance;
				} else if ($typeName !== 'mixed') {
					settype($value, $typeName);
				}
				Helper::setPropertyValue($object, $propertyName, $value);
			}
		}
	}

	private function isClassType($type): bool
	{
		return !(
			$type == 'string' ||
			$type == 'boolean' ||
			$type == 'bool' ||
			$type == 'integer' ||
			$type == 'int' ||
			$type == 'double' ||
			$type == 'float' ||
			$type == 'array' ||
			$type == 'object' ||
			$type == 'mixed'
		);
	}

	private function getFullNamespace($type, $strNs): string
	{
		if ($type !== '' && $type[0] != '\\' && $strNs != '') {
			$type = '\\' . $strNs . '\\' . $type;
		}
		return $type;
	}
}
