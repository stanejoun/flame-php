<?php

namespace Stanejoun\LightPHP;

class Config
{
	public const PROD_ENVIRONMENT = 'PROD';
	public const TEST_ENVIRONMENT = 'TEST';
	public const DEV_ENVIRONMENT = 'DEV';

	public static string $ENVIRONMENT = Config::PROD_ENVIRONMENT;

	private static ?object $CONFIG = null;

	final public static function get(string $key = null, mixed $default = null): mixed
	{
		if (empty(self::$CONFIG)) {
			$content = file_get_contents(ROOT . 'config/config.json');
			$config = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
			if (self::$ENVIRONMENT === Config::TEST_ENVIRONMENT) {
				$testConfigFilename = ROOT . 'config/config.test.json';
				if (file_exists($testConfigFilename)) {
					$testContent = file_get_contents($testConfigFilename);
					$testConfig = json_decode($testContent, true, 512, \JSON_THROW_ON_ERROR);
					$config = array_merge($config, $testConfig);
				}
			}
			self::$CONFIG = (object)$config;
		}
		if (empty($key)) {
			return self::$CONFIG;
		}
		return self::$CONFIG->{$key} ?? $default;
	}

	public static function testMode(): void
	{
		self::$CONFIG = null;
		self::$ENVIRONMENT = Config::TEST_ENVIRONMENT;
	}

	public static function prodMode(): void
	{
		self::$CONFIG = null;
		self::$ENVIRONMENT = Config::PROD_ENVIRONMENT;
	}

	public static function devMode(): void
	{
		self::$CONFIG = null;
		self::$ENVIRONMENT = Config::DEV_ENVIRONMENT;
	}

	final public function __clone(): void {}
}