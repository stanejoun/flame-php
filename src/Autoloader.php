<?php

namespace Stanejoun\LightPHP;

class AutoLoader
{
	static public function Loader($class): void
	{
		$filename = '';
		$path = explode('\\', $class);
		for ($i = 0, $count = count($path); $i < $count; $i++) {
			if ($i === $count - 1) {
				$filename .= $path[$i];
			} else {
				$filename .= strtolower($path[$i]) . '/';
			}
		}
		if (empty($filename)) {
			$filename = $class;
		}
		require_once ROOT . $filename . '.php';
	}
}