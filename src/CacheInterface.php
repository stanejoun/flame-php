<?php

namespace Stanejoun\LightPHP;

interface CacheInterface
{
	public function write(string $uid, $content): void;

	public function read(string $uid, bool $force): mixed;

	public function remove(string $uid): void;
}