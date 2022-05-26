<?php

namespace Stanejoun\FlamePHP;

interface CacheInterface
{
	public function write(string $uid, $content): void;

	public function read(string $uid, bool $force): mixed;

	public function remove(string $uid): void;
}