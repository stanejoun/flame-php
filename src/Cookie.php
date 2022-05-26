<?php

namespace Stanejoun\FlamePHP;

class Cookie
{
	public static function set(string $name, $data = null, int $expire = 1800): void
	{
		$data = (!is_string($data)) ? json_encode($data) : $data;
		$expire = ($expire > 0) ? (int)(time() + $expire) : 0;
		$secure = (Config::get('protocol') === 'https');
		setcookie($name, $data, $expire, '/', null, $secure);
	}

	public static function delete(string $name): void
	{
		if (isset($_COOKIE[$name])) {
			$secure = (Config::get('protocol') === 'https');
			setcookie($name, null, (int)(time() - 3600), '/', null, $secure);
		}
	}

	public static function deleteByName(string $name): void
	{
		$cookies = self::get();
		if (!empty($cookies) && is_array($cookies)) {
			$len = strlen($name);
			$secure = (Config::get('protocol') === 'https');
			foreach ($cookies as $cookie_key => $cookie_val) {
				if (strtolower(substr($cookie_key, 0, $len)) === trim(strtolower($name))) {
					setcookie($cookie_key, null, (int)(time() - 3600), '/', null, $secure);
				}
			}
		}
	}

	public static function get(string $name = null)
	{
		if (!empty($name)) {
			if (isset($_COOKIE[$name])) {
				if (Helper::isJson($_COOKIE[$name])) {
					$_COOKIE[$name] = json_decode($_COOKIE[$name], true, 512, \JSON_THROW_ON_ERROR);
				}
				return Security::purifyData($_COOKIE[$name]);
			}
			return null;
		} else {
			return filter_input_array(INPUT_COOKIE);
		}
	}

	public static function deleteAll(): void
	{
		$cookies = self::get();
		if (!empty($cookies) && is_array($cookies)) {
			$secure = (Config::get('protocol') === 'https');
			foreach ($cookies as $cookie_key => $cookie_val) {
				setcookie($cookie_key, null, (int)(time() - 3600), '/', null, false, $secure);
			}
		}
	}
}
