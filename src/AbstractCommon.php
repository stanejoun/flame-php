<?php

namespace Stanejoun\FlamePHP;

class AbstractCommon
{
	public function hasRight(string $permissionName): bool
	{
		return AccessControls::hasRight($permissionName);
	}

	public function checkAccess(string $permissionName): void
	{
		AccessControls::checkAccess($permissionName);
	}

	public function userDir(string $dir = null, User $user = null): string
	{
		$user = ($user) ? $user : Authentication::getAuthenticatedUser();
		if (!$user) {
			$path = 'users/anonymous/';
		} else {
			$path = 'users/' . $user->getId() . '/';
			if (!empty($dir)) {
				$path .= $dir . '/';
			}
		}
		return $path;
	}

	public function toObject(): object
	{
		return (object)$this->toArray();
	}

	public function toArray(): array
	{
		$reflection = new \ReflectionClass($this);
		$properties = $reflection->getProperties();
		$staticProperties = $reflection->getStaticProperties();
		$staticPropertiesNames = [];
		foreach ($staticProperties as $staticPropertyName => $staticPropertyValue) {
			$staticPropertiesNames[] = $staticPropertyName;
		}
		$array = [];
		foreach ($properties as $reflectionProperty) {
			$property = $reflectionProperty->name;
			if (!in_array($property, $staticPropertiesNames)) {
				$value = Helper::getPropertyValue($this, $property);
				if ($value instanceof AbstractModel || $value instanceof AbstractDTO) {
					$value = $value->toArray();
				}
				if (is_object($value)) {
					if ($value instanceof \DateTime) {
						$value = $value->format('Y-m-d H:i:s');
					} else {
						$value = (array)$value;
					}
				}
				if (is_array($value)) {
					foreach ($value as $index => $currentValue) {
						if ($currentValue instanceof AbstractModel || $value instanceof AbstractDTO) {
							$value[$index] = $currentValue->toArray();
						}
						if (is_object($value[$index])) {
							$value[$index] = (string)$value[$index];
						}
					}
				}
				$array[$property] = $value;
			}
		}
		return $array;
	}
}
