<?php

namespace Stanejoun\FlamePHP;

class Helper
{
	public static function displayFileSize(int $fileSize): string
	{
		$decr = 1024;
		$step = 0;
		$prefix = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB');
		$size = (float)$fileSize;
		while (($fileSize / $decr) > 0.9) {
			$size /= $decr;
			$step++;
		}
		return round($size, 2) . ' ' . $prefix[$step];
	}

	public static function toString(mixed $value, string $dateFormat = null): string
	{
		if ($value instanceof \DateTime) {
			if (empty($dateFormat)) {
				$dateFormat = 'Y-m-d H:i:s';
			}
			return $value->format($dateFormat);
		}
		if (is_array($value)) {
			if (!empty($value)) {
				foreach ($value as $index => $currentArrayValue) {
					$value[$index] = self::toString($currentArrayValue);
				}
			}
			return json_encode($value);
		}
		if ($value instanceof \stdClass) {
			foreach ($value as $key => $currentObjectValue) {
				$value->{$key} = self::toString($currentObjectValue);
			}
			return json_encode($value);
		}
		if (is_object($value)) {
			return serialize($value);
		}
		if (is_bool($value)) {
			return ($value) ? '1' : '0';
		}
		return (string)$value;
	}

	public static function decodeString(string $value): mixed
	{
		if (self::isJson($value)) {
			$value = json_decode($value, false, 512, \JSON_THROW_ON_ERROR);
		} else if (self::isSerialized($value)) {
			$value = unserialize($value);
		}
		if (is_array($value)) {
			foreach ($value as $index => $currentArrayValue) {
				if (is_string($currentArrayValue)) {
					$value[$index] = self::decodeString($currentArrayValue);
				}
			}
		}
		if ($value instanceof \stdClass) {
			foreach ($value as $key => $currentObjectValue) {
				if (is_string($currentObjectValue)) {
					$value->{$key} = self::decodeString($currentObjectValue);
				}
			}
		}
		if (is_numeric($value)) {
			if (str_contains($value, '.')) {
				$value = (float)$value;
			} else {
				$value = (int)$value;
			}
		}
		if ($value === 'true') {
			$value = true;
		}
		if ($value === 'false') {
			$value = false;
		}
		return $value;
	}

	public static function isJson(string $str): bool
	{
		return is_array(json_decode($str, true));
	}

	public static function isSerialized(string $str): bool
	{
		return (@unserialize($str, []) !== false);
	}

	public static function getAccessPermissions(int $perms): string
	{
		if (($perms & 0xC000) == 0xC000) {
			// Socket
			$info = 's';
		} else if (($perms & 0xA000) == 0xA000) {
			// Symbolic Link
			$info = 'l';
		} else if (($perms & 0x8000) == 0x8000) {
			// Regular
			$info = '-';
		} else if (($perms & 0x6000) == 0x6000) {
			// Block special
			$info = 'b';
		} else if (($perms & 0x4000) == 0x4000) {
			// Directory
			$info = 'd';
		} else if (($perms & 0x2000) == 0x2000) {
			// Character special
			$info = 'c';
		} else if (($perms & 0x1000) == 0x1000) {
			// FIFO pipe
			$info = 'p';
		} else {
			// Unknown
			$info = 'u';
		}
		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ?
			(($perms & 0x0800) ? 's' : 'x') :
			(($perms & 0x0800) ? 'S' : '-'));
		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ?
			(($perms & 0x0400) ? 's' : 'x') :
			(($perms & 0x0400) ? 'S' : '-'));
		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ?
			(($perms & 0x0200) ? 't' : 'x') :
			(($perms & 0x0200) ? 'T' : '-'));
		return $info;
	}

	public static function cleanFilename(string $filename): string
	{
		return preg_replace('/([^.a-z0-9]+)/i', '-', strtr($filename, 'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ', 'AAAAAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy'));
	}

	public static function instantiate(string $className, mixed $definition): object
	{
		$instance = new $className();
		if (!empty($definition)) {
			self::hydrate($instance, $definition);
		}
		return $instance;
	}

	public static function hydrate(object &$object, mixed $definition): void
	{
		if (!empty($definition)) {
			(new DataMapper())->map($object, $definition);
		}
	}

	public static function getPropertyValue(object $object, string $propertyName): mixed
	{
		$methodName = Helper::getCamelCaseName($propertyName);
		$getMethodName = 'get' . ucfirst($methodName);
		$isMethodName = 'is' . ucfirst($methodName);
		if (method_exists($object, $methodName)) {
			$value = $object->{$methodName}();
		} elseif (method_exists($object, $getMethodName)) {
			$value = $object->{$getMethodName}();
		} elseif (method_exists($object, $isMethodName)) {
			$value = $object->{$isMethodName}();
		} else {
			$value = $object->{$propertyName};
		}
		return $value;
	}

	public static function getCamelCaseName(string $name): string
	{
		return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name))));
	}

	public static function getSnakeCaseName(string $name): string
	{
		$pattern = '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!';
		preg_match_all($pattern, $name, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	public static function setPropertyValue(object &$object, string $propertyName, mixed $value): void
	{
		$methodName = Helper::getCamelCaseName($propertyName);
		$setMethodName = 'set' . ucfirst($methodName);
		if (method_exists($object, $setMethodName)) {
			$object->{$setMethodName}($value);
		} else {
			if (!property_exists($object, $propertyName)) {
				$object->{$propertyName} = null;
			}
			$object->{$propertyName} = $value;
		}
	}

	public static function addPropertyValue(object &$object, string $propertyName, mixed $value): void
	{
		$methodName = Helper::getCamelCaseName($propertyName);
		$addMethodName = 'add' . ucfirst(substr($methodName, 0, -1));
		if (method_exists($object, $addMethodName)) {
			$object->{$addMethodName}($value);
		} else {
			if (!property_exists($object, $propertyName)) {
				$object->{$propertyName} = [];
			}
			$object->{$propertyName}[] = $value;
		}
	}

	public static function createFilesDirectory(string $dirName, bool $public = false): void
	{
		try {
			$root = ($public) ? DOCUMENT_ROOT : FILES;
			$root = (str_ends_with($root, '/')) ? substr($root, 0, -1) : $root;
			$explode = explode('/', $root);
			$countRoot = count($explode);
			$paths = explode('/', $dirName);
			$countDirName = count($paths);
			for ($i = $countRoot; $i < $countDirName; $i++) {
				if (!empty($paths[$i])) {
					$file = $root . '/' . $paths[$i];
					if (!file_exists($file) && !mkdir($file, 0644, true) && !is_dir($file)) {
						throw new \RuntimeException(sprintf('Directory "%s" was not created', $file));
					}
					$root = $root . '/' . $paths[$i];
				}
			}
		} catch (\Exception $exception) {
			throw new \Exception($exception->getMessage());
		}
	}

	public static function parseDocBlock(string $docBlock): array
	{
		$annotations = [];
		// Strip away the docBlock header and footer
		// to ease parsing of one line annotations
		$docBlock = substr($docBlock, 3, -2);
		$result = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m';
		if (preg_match_all($result, $docBlock, $matches)) {
			$numMatches = count($matches[0]);
			for ($i = 0; $i < $numMatches; ++$i) {
				$annotations[$matches['name'][$i]][] = $matches['value'][$i];
			}
		}
		return $annotations;
	}


}
