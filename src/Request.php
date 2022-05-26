<?php

namespace Stanejoun\FlamePHP;

class Request
{
	public static function set(string $name, string $value): void
	{
		$_GET[$name] = $value;
	}

	public static function get(?string $name = null): mixed
	{
		if (!empty($name)) {
			return isset($_GET[$name]) ? Security::purifyData($_GET[$name]) : null;
		}
		return Security::purifyData($_GET);
	}

	public static function data(?string $name = null): mixed
	{
		if (empty($_POST)) {
			$httpRawPostData = file_get_contents('php://input');
			if (!empty($httpRawPostData)) {
				if ($httpRawPostData = json_decode($httpRawPostData, true, 512, \JSON_THROW_ON_ERROR)) {
					if (!empty($name)) {
						return isset($httpRawPostData[$name]) ? Security::purifyData($httpRawPostData[$name]) : null;
					}
					return Security::purifyData($httpRawPostData);
				} else {
					$httpRawPostData = rawurldecode($httpRawPostData);
					$httpRawPostDataArray = explode('&', $httpRawPostData);
					$httpRawPostDataArrayAssoc = [];
					if (!empty($httpRawPostDataArray)) {
						foreach ($httpRawPostDataArray as $row) {
							$parts = explode('=', $row);
							$httpRawPostDataArrayAssoc[$parts[0]] = $parts[1];
						}
					}
					if (!empty($name)) {
						return isset($httpRawPostDataArrayAssoc[$name]) ? Security::purifyData($httpRawPostDataArrayAssoc[$name]) : null;
					}
					return Security::purifyData($httpRawPostDataArrayAssoc);
				}
			} else if (!empty($name)) {
				return null;
			}
		} else if (!empty($name)) {
			return isset($_POST[$name]) ? Security::purifyData($_POST[$name]) : null;
		}
		return Security::purifyData($_POST);
	}

	public static function getHeader(?string $name = null): mixed
	{
		if (!empty($name)) {
			return (isset($_SERVER[$name])) ? filter_input(INPUT_SERVER, $name) : null;
		}
		return filter_input_array(INPUT_SERVER);
	}

	public static function remove(string $method, ?string $name = null): void
	{
		$method = strtolower($method);
		if (!empty($method) && ($method === 'post' || $method === 'get')) {
			if (!empty($name)) {
				if ($method === 'get' && isset($_GET[$name])) {
					unset($_GET[$name]);
				} else if ($method === 'post' && isset($_POST[$name])) {
					unset($_POST[$name]);
				}
			} else if ($method === 'get') {
				$_GET = [];
			} else if ($method === 'post') {
				$_POST = [];
			}
		}
	}

	public static function hasFiles(string $name): bool
	{
		if (!empty($_FILES[$name])) {
			if (is_array($_FILES[$name]['error'])) {
				foreach ($_FILES[$name]['error'] as $error) {
					if ($error !== UPLOAD_ERR_OK) {
						return false;
					}
				}
				return isset($_FILES[$name]['error'][0]);
			} else if (isset($_FILES[$name]['error']) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
				return true;
			}
		}
		return false;
	}

	public static function getFiles(string $name): mixed
	{
		if (isset($_FILES[$name])) {
			return $_FILES[$name];
		}
		throw new \Exception('Unable to find the file: "' . $name . '"!');
	}

	public static function hasHeader($name): bool
	{
		return (isset($_SERVER[$name]) && !empty($_SERVER[$name]));
	}

	public static function isRemoteRequest(): bool
	{
		$requestUri = Router::getCurrentRequestUri();
		return (str_contains($requestUri, '/api/') || self::isXmlHttpRequest());
	}

	public static function isXmlHttpRequest(): bool
	{
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	}
}
