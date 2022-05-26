<?php

namespace Stanejoun\FlamePHP;

use Stanejoun\FlamePHP\Exceptions\InternalServerErrorException;

class Logger implements LoggerInterface
{
	private static array $LOG_FILES = [];

	public static function emergency(string $message, array $context = []): void
	{
		self::log('EMERGENCY', $message, $context);
	}

	public static function log(string $level, string $message, array $context = []): void
	{
		self::write($level, '[' . (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s') . '][' . $level . '] ' . self::rowStart() . ', MESSAGE: ' . $message . ' , CONTEXT: ' . json_encode($context));
	}

	private static function write(string $level, string $row): void
	{
		$logFile = self::getLogFile(strtolower($level));
		$success = file_put_contents($logFile, $row . "\n", FILE_APPEND);
		if (!$success) {
			throw new InternalServerErrorException('Log write error!');
		}
	}

	private static function getLogFile(string $level): string
	{
		if (empty(self::$LOG_FILES[$level])) {
			$currentYear = date('Y');
			$pathYear = LOGS . $currentYear;
			if (!is_dir($pathYear) && !mkdir($pathYear, 0644, true) && !is_dir($pathYear)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathYear));
			}
			$pathMonth = $pathYear . DIRECTORY_SEPARATOR . date('m');
			if (!is_dir($pathMonth) && !mkdir($pathMonth, 0644, true) && !is_dir($pathMonth)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathMonth));
			}
			self::$LOG_FILES[$level] = $pathMonth . DIRECTORY_SEPARATOR . date('Ymd') . "_$level" . '.txt';
		}
		return self::$LOG_FILES[$level];
	}

	private static function rowStart(): string
	{
		$fingerprint = Security::getFingerprint();
		$ip = Security::ip();
		$lang = Lang::$CURRENT;
		$acceptLanguage = Request::getHeader('HTTP_ACCEPT_LANGUAGE');
		$currentUrl = Router::getCurrentHttpProtocol() . '://' . Request::getHeader('HTTP_HOST') . Request::getHeader('REQUEST_URI');
		$user = Authentication::$AUTHENTICATED_USER;
		$httpReferer = Request::getHeader('HTTP_REFERER');
		$userId = 'anonymous';
		if (!empty($user)) {
			$userId = (string)$user->getId();
		}
		return "FINGERPRINT: $fingerprint, IP: $ip, ACCEPT_LANGUAGE: $acceptLanguage, LANGUAGE: $lang, USER: $userId, HTTP_REFERER: $httpReferer, CURRENT URL: $currentUrl";
	}

	public static function alert(string $message, array $context = []): void
	{
		self::log('ALERT', $message, $context);
	}

	public static function critical(string $message, array $context = []): void
	{
		self::log('CRITICAL', $message, $context);
	}

	public static function error(string $message, array $context = []): void
	{
		self::log('ERROR', $message, $context);
	}

	public static function warning(string $message, array $context = []): void
	{
		self::log('WARNING', $message, $context);
	}

	public static function notice(string $message, array $context = []): void
	{
		self::log('NOTICE', $message, $context);
	}

	public static function info(string $message, array $context = []): void
	{
		self::log('INFO', $message, $context);
	}

	public static function debug(string $message, array $context = []): void
	{
		self::log('DEBUG', $message, $context);
	}
}
