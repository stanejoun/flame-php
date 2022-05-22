<?php

namespace Stanejoun\LightPHP;

use Exception;
use HTMLPurifier;
use RangeException;

class Security
{
	private static ?HTMLPurifier $PURIFIER = null;

	public static function htmlEntities(mixed $data): mixed
	{
		if (!is_array($data) && !is_object($data)) {
			$data = self::purifyData($data);
			$data = trim((string)$data);
			return htmlentities($data, ENT_QUOTES, 'UTF-8');
		}
		foreach ($data as $key => $value) {
			if (is_object($data)) {
				$data->{$key} = self::htmlEntities($value);
			} else if (is_array($data)) {
				$data[$key] = self::htmlEntities($value);
			}
		}
		return $data;
	}

	public static function purifyData(mixed $data): mixed
	{
		if (!is_array($data) && !is_object($data)) {
			if (!is_string($data)) {
				$data = (string)$data;
			}
			if (self::$PURIFIER === null) {
				self::$PURIFIER = new HTMLPurifier();
			}
			return self::$PURIFIER->purify($data);
		}
		foreach ($data as $key => $value) {
			if (is_object($data)) {
				$data->{$key} = self::purifyData($value);
			} else if (is_array($data)) {
				$data[$key] = self::purifyData($value);
			}
		}
		return $data;
	}

	public static function htmlEntitiesDecode(mixed $data): mixed
	{
		if (!is_array($data) && !is_object($data)) {
			return html_entity_decode($data, ENT_QUOTES, 'UTF-8');
		}
		foreach ($data as $key => $value) {
			if (is_object($data)) {
				$data->{$key} = self::htmlEntitiesDecode($value);
			} else if (is_array($data)) {
				$data[$key] = self::htmlEntitiesDecode($value);
			}
		}
		return $data;
	}

	public static function token(string $id = '', int $expireTime = 0): string
	{
		if (empty($id)) {
			throw new \Exception('The token id cannot be empty!');
		}
		if (empty($expireTime) || !is_int($expireTime)) {
			$expireTime = 1800 / 60;
		}
		$token = self::hash(uniqid(rand(), true) . Config::get('secretKey'));
		$_SESSION['token'][$id] = $token;
		$_SESSION['token.time'][$id] = time();
		$_SESSION['token.expire'][$id] = $expireTime;
		return $token;
	}

	public static function hash(string $str): string
	{
		return hash(Config::get('hashingAlgorithm'), $str . Config::get('secretKey'));
	}

	public static function checkToken(string $id, string $token = ''): bool
	{
		if (empty($id)) {
			throw new \Exception('The token id cannot be empty!');
		}
		if (empty($token)) {
			$token = Request::data('token');
			if ($token === null) {
				$token = Request::get('token');
			}
		}
		self::removeExpiredTokens();
		if (
			!empty($token) &&
			isset($_SESSION['token'], $_SESSION['token'][$id], $_SESSION['token.time'][$id], $_SESSION['token.expire'][$id]) &&
			$token === $_SESSION['token'][$id] &&
			$_SESSION['token.time'][$id] > (time() - ((int)$_SESSION['token.expire'][$id] * 60))
		) {
			$_SESSION['token.time'][$id] = time();
			return true;
		}
		return false;
	}

	public static function removeExpiredTokens(): void
	{
		if (!empty($_SESSION['token'])) {
			foreach ($_SESSION['token'] as $key => $value) {
				if ($_SESSION['token.time'][$key] <= (time() - ((int)$_SESSION['token.time'][$key] * 60))) {
					unset($_SESSION['token'][$key], $_SESSION['token.time'][$key], $_SESSION['token.expire'][$key]);
				}
			}
		}
	}

	public static function getFingerprint(): string
	{
		$http_user_agent = (string)Request::getHeader('HTTP_USER_AGENT');
		$http_accept_language = (string)Request::getHeader('HTTP_ACCEPT_LANGUAGE');
		$ip = self::ip();
		return md5("$http_user_agent-$http_accept_language-$ip");
	}

	public static function ip(): string
	{
		$http_x_forwarded_for = Request::getHeader('HTTP_X_FORWARDED_FOR');
		if (!empty($http_x_forwarded_for)) {
			if (str_contains($http_x_forwarded_for, ',')) {
				$tab = explode(',', $http_x_forwarded_for);
				$ip_address = $tab[0];
			} else {
				$ip_address = $http_x_forwarded_for;
			}
		} else {
			$remote_addr = Request::getHeader('REMOTE_ADDR');
			$ip_address = $remote_addr;
		}
		return $ip_address;
	}

	public static function checkPassword(string $userString, string $knownString): bool
	{
		return password_verify($userString, base64_decode($knownString));
	}

	public static function hashPassword(string $password): string
	{
		return base64_encode(password_hash($password, PASSWORD_DEFAULT));
	}

	public function EncryptFile($filename): void
	{
		$handle = fopen($filename, 'r+b');
		$content = fread($handle, filesize($filename));
		$cipherContent = self::encrypt($content);
		ftruncate($handle, 0);
		rewind($handle);
		fwrite($handle, $cipherContent);
		fclose($handle);
	}

	public static function encrypt(string $message): string
	{
		if (empty($message)) {
			return $message;
		}
		$encryptKey = Config::get('secretKey');
		if (mb_strlen($encryptKey, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			throw new RangeException('Key is not the correct size (must be 32 bytes).');
		}
		$nonce = substr($encryptKey, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		return base64_encode(sodium_crypto_secretbox($message, $nonce, $encryptKey));
	}

	public function DecryptFile($filename): void
	{
		$handle = fopen($filename, 'r+b');
		$cipherContent = fread($handle, filesize($filename));
		$content = self::decrypt($cipherContent);
		ftruncate($handle, 0);
		rewind($handle);
		fwrite($handle, $content);
		fclose($handle);
	}

	public static function decrypt(string $encrypted): string
	{
		if (empty($encrypted)) {
			return $encrypted;
		}
		$decoded = base64_decode($encrypted);
		if ($decoded === false) {
			throw new \Exception('The encoding failed!');
		}
		$encryptKey = Config::get('secretKey');
		if (mb_strlen($encryptKey, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
			throw new RangeException('Key is not the correct size (must be 32 bytes).');
		}
		$nonce = substr($encryptKey, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$plaintext = sodium_crypto_secretbox_open($decoded, $nonce, $encryptKey);
		if (!is_string($plaintext)) {
			throw new Exception('Unable to decrypt the value!');
		}
		return $plaintext;
	}
}
