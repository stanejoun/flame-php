<?php

namespace Stanejoun\LightPHP;

use Predis\Client;

class Redis
{
	#[ArrayOf('\Predis\Client')]
	private static array $INSTANCES = [];

	final public static function getInstance(string $name = 'app', int $database = 0): Client
	{
		if (!Config::get('redis') || !Config::get('redis')->enable) {
			throw new \Exception('Unavailable Redis config!');
		}
		if (!isset(self::$INSTANCES[$name])) {
			self::$INSTANCES[$name] = self::connection($database);
		}
		return self::$INSTANCES[$name];
	}

	private static function connection(int $database): Client
	{
		$redisUrl = (Config::get('redis_url')) ? Config::get('redis_url') : 'redis://127.0.0.1:6379';
		$redis = new Client($redisUrl);
		$redis->select($database);
		return $redis;
	}
}