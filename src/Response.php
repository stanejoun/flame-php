<?php

namespace Stanejoun\LightPHP;

class Response
{
	public static function sendHtml(string $content): void
	{
		header('Content-Type: text/html; charset=UTF-8');
		echo $content;
	}

	public static function sendJson(mixed $content): void
	{
		if (!is_string($content)) {
			$jsonResponse = new JsonResponse($content);
			$content = $jsonResponse->getContent();
		}
		header('Content-Type: application/json; charset=UTF-8');
		echo $content;
	}

	public static function setHeader(int $code): void
	{
		switch ((int)$code) {
			case 200:
				header('HTTP/1.1 200 Success');
				break;
			case 400:
				header('HTTP/1.1 400 Bad request');
				break;
			case 401:
				header('HTTP/1.1 401 Unauthorized');
				break;
			case 403:
				header('HTTP/1.1 403 Forbidden');
				break;
			case 404:
				header('HTTP/1.1 404 Not Found');
				break;
			case 500:
				header('HTTP/1.1 500 Internal Server Error');
				break;
			case 555:
				header('HTTP/1.1 555 Business Exception');
				break;
		}
	}
}
