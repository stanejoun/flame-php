<?php

namespace Stanejoun\FlamePHP;

class Cache
{
	#[ArrayOf('\Stanejoun\FlamePHP\CacheInterface')]
	private static array $INSTANCE = [];

	final public static function getInstance($path = '', $timeout = null): CacheInterface
	{
		$instanceKey = md5($path . $timeout);
		$timeout = ($timeout !== null) ? $timeout : Config::get('cache')->timeout; //secondes
		if (!isset(self::$INSTANCE[$instanceKey])) {
			$mode = Config::get('cache')->mode;
			if (!Config::get('redis') || !Config::get('redis')->enable) {
				$mode = 'file';
			}
			if (!empty($path)) {
				$firstChar = substr($path, 0, 1);
				if ($firstChar === '/') {
					$path = substr($path, 1);
				}
				$path = str_replace('`', '', $path);
			}
			switch ($mode) {
				case 'redis':
					if (!empty($path)) {
						$path = str_replace(DIRECTORY_SEPARATOR, ':', $path);
					}
					$instance = new RedisCache($timeout, $path);
					break;
				case 'file':
				default:
					$instance = new FileCache($timeout, $path);
					break;
			}
			self::$INSTANCE[$instanceKey] = $instance;
		}
		return self::$INSTANCE[$instanceKey];
	}

	final public static function isDisabled(): bool
	{
		return (!Config::get('cache')->enable);
	}
}
