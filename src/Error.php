<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\BusinessException;

class Error
{
	public static function get(\Exception $exception): void
	{
		$errorCode = $exception->getCode();
		Logger::error($exception->getMessage());
		if (empty($errorCode) || $errorCode === 500) {
			$errorCode = 500;
			$errorMessage = Translator::translate('The application has encountered an unknown error.');
		} else {
			$errorMessage = $exception->getMessage();
		}
		if ($exception instanceof BusinessException) {
			$responseErrorDTO = $exception->getErrorDTO();
		} else {
			$responseErrorDTO = (new ResponseErrorDTO())
				->setCode($errorCode)
				->setMessage($errorMessage)
				->setErrors([]);
		}
		try {
			$errorData = $responseErrorDTO->toJson();
			Response::SetHeader($errorCode);
			if (!Request::isRemoteRequest()) {
				$routeName = match ($errorCode) {
					404 => Config::get('defaultRoutes')->pageNotFound,
					403 => Config::get('defaultRoutes')->forbidden,
					default => Config::get('defaultRoutes')->defaultError,
				};
				try {
					$url = Router::getUrl($routeName);
					Session::setFlashError($responseErrorDTO);
					Router::redirect($url);
				} catch (\Exception $e) {
					Response::SendJson($errorData);
				}
			} else {
				Response::SendJson($errorData);
			}
		} catch (\Exception $e) {
			echo $errorMessage;
		}
	}
}