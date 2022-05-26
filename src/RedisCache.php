<?php

namespace Stanejoun\FlamePHP;

use Predis\Client;

class RedisCache implements CacheInterface
{
	private int $timeout;
	private Client $redis;
	private string $prefixKey;

	public function __construct(int $timeout, string $path = null)
	{
		$this->timeout = $timeout;
		$this->redis = Redis::getInstance('app_cache', 0);
		$path = (str_ends_with(trim($path), '/')) ? substr($path, 0, -1) : $path;
		$path = str_replace('/', ':', $path);
		$this->prefixKey = (!empty($path)) ? "$path:" : 'cache:';
	}

	public function write(string $uid, mixed $content): void
	{
		if (!empty($uid) && !empty($content)) {
			$content = (!is_string($content)) ? Helper::toString($content) : $content;
			$this->redis->set($this->prefixKey . $uid, $content, $this->timeout);
		}
	}

	public function read(string $uid, bool $force = false): mixed
	{
		if (empty($uid) || (Cache::isDisabled() && !$force)) {
			return null;
		}
		return Helper::decodeString($this->redis->get($this->prefixKey . $uid));
	}

	public function remove(string $uid = ''): void
	{
		if (empty($uid)) {
			$keys = $this->redis->keys($this->prefixKey . '*');
			if (!empty($keys)) {
				foreach ($keys as $key) {
					$this->redis->delete($key);
				}
			}
		} else {
			$this->redis->del($this->prefixKey . $uid);
		}
	}
}
