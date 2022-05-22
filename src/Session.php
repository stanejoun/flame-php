<?php

namespace Stanejoun\LightPHP;

class Session
{
	private const FAKE_SESSION_ID = 'PHPSESSID';

	public static function start(): void
	{
		if (Config::get('enableSession') === true) {
			self::sessionStart();
			$fingerprint = self::get('fingerprint');
			if (!isset($fingerprint)) {
				self::set('fingerprint', Security::getFingerprint());
				$internalId = substr(sha1(uniqid(mt_rand(), true)), 0, -random_int(13, 16));
				Cookie::set(self::FAKE_SESSION_ID, $internalId, Config::get('sessionTime'));
				self::set('internal_id', $internalId);
			} else if ($fingerprint !== Security::getFingerprint()) {
				self::sessionDestroy();
			}
			$fakeSessionId = Cookie::get(self::FAKE_SESSION_ID);
			if (empty($fakeSessionId)) {
				self::sessionDestroy();
			}
		}
	}

	private static function sessionStart(): void
	{
		$sessionName = Config::get('sessionName') ?? 'visit';
		session_name($sessionName);
		session_start();
		Cookie::set($sessionName, session_id(), Config::get('sessionTime'));
		if ($internalId = self::get('internal_id')) {
			Cookie::set(self::FAKE_SESSION_ID, $internalId, Config::get('sessionTime'));
		}
	}

	public static function get(?string $name = null): mixed
	{
		if (!Config::get('enableSession')) {
			return null;
		}
		if (!empty($name)) {
			if (isset($_SESSION[$name])) {
				if (is_string($_SESSION[$name])) {
					return Helper::decodeString($_SESSION[$name]);
				}
				return $_SESSION[$name];
			}
			return null;
		}
		return $_SESSION;
	}

	public static function set(string $name, mixed $data): void
	{
		if (Config::get('enableSession') && !empty($name)) {
			$_SESSION[$name] = $data;
		}
	}

	public static function sessionDestroy(): void
	{
		session_unset();
		session_destroy();
	}

	public static function setFlashError(mixed $data): void
	{
		if (Config::get('enableSession')) {
			$_SESSION['flash_error'] = $data;
		}
	}

	public static function getFlashErrors(): mixed
	{
		if (Config::get('enableSession') && isset($_SESSION['flash_error'])) {
			$error = $_SESSION['flash_error'];
			unset($_SESSION['flash_error']);
			return $error;
		}
		return null;
	}

	public static function remove(string $name): void
	{
		if (Config::get('enableSession') && isset($_SESSION[$name])) {
			unset($_SESSION[$name]);
		}
	}
}
