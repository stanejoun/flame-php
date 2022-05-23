<?php

namespace Stanejoun\LightPHP;

class Config
{
	public const PROD_ENVIRONMENT = 'PROD';
	public const TEST_ENVIRONMENT = 'TEST';
	public const DEV_ENVIRONMENT = 'DEV';

	public static string $ENVIRONMENT = Config::PROD_ENVIRONMENT;

	private static object $CONFIG;

	final public static function get(string $key = null, mixed $default = null): mixed
	{
		if (!isset(self::$CONFIG)) {
			$filename = (isset(self::$ENVIRONMENT) && self::$ENVIRONMENT === Config::TEST_ENVIRONMENT) ? 'config.test.json' : 'config.json';
			$content = file_get_contents(ROOT . 'config/' . $filename);
			self::$CONFIG = json_decode($content, false, 512, \JSON_THROW_ON_ERROR);
		}
		if (empty($key)) {
			return self::$CONFIG;
		}
		return self::$CONFIG->{$key} ?? $default;
	}

	final public function __clone(): void {}
}