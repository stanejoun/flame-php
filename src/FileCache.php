<?php

namespace Stanejoun\FlamePHP;

class FileCache implements CacheInterface
{
	private int $timeout;
	private string $path;

	public function __construct(int $timeout, string $path = null)
	{
		$this->timeout = $timeout;
		$this->path = (!empty($path)) ? CACHES . $path : CACHES;
		$this->path = (str_ends_with(trim($this->path), '/')) ? substr($this->path, 0, -1) : $this->path;
	}

	public function write(string $uid, mixed $content): void
	{
		if (!empty($uid) && !empty($content)) {
			if (!file_exists($this->path)) {
				$directory = mkdir($this->path, 0644, true);
				if ($directory === false && !is_dir($this->path)) {
					throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->path));
				}
			}
			$content = (!is_string($content)) ? Helper::toString($content) : $content;
			$filename = $this->path . '/' . $uid . '.cache';
			file_put_contents($filename, $content);
		}
	}

	public function read(string $uid, bool $force = false): mixed
	{
		if (empty($uid) || (Cache::isDisabled() && !$force)) {
			return null;
		}
		$filename = $this->path . '/' . $uid . '.cache';
		if (file_exists($filename)) {
			$lifeTime = (time() - filemtime($filename));
			if ($this->timeout !== 0 && $lifeTime > $this->timeout) {
				$this->removeFilename($filename);
			} else {
				$content = file_get_contents($filename);
				return Helper::decodeString($content);
			}
		}
		return null;
	}

	private function removeFilename(string $filename): void
	{
		if (file_exists($filename)) {
			if (is_dir($filename)) {
				shell_exec('rm -rf ' . realpath($filename));
			} else {
				unlink($filename);
			}
		}
	}

	public function remove(string $uid = null): void
	{
		if ($uid) {
			$filename = $this->path . '/' . $uid . '.cache';
		} else {
			$filename = $this->path;
		}
		$this->removeFilename($filename);
	}
}
