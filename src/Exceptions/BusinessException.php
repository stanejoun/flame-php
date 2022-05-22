<?php

namespace Stanejoun\LightPHP\Exceptions;

use Stanejoun\LightPHP\ResponseErrorDTO;

class BusinessException extends \Exception
{
	private ResponseErrorDTO $errorDTO;

	public function __construct(mixed $message = '', int $code = 555, \Throwable $previous = null)
	{
		$errorResponseDTO = (new ResponseErrorDTO())
			->setCode($code)
			->setMessage('Business exception!')
			->setErrors([]);
		if (!empty($message)) {
			if (is_string($message)) {
				$errorResponseDTO->setMessage($message);
			}
			if (is_array($message)) {
				$errorResponseDTO->setMessage('Invalid inputs!')->setErrors($message);
			}
		}
		$this->errorDTO = $errorResponseDTO;
		parent::__construct($errorResponseDTO->getMessage(), $code);
	}

	public function getErrorDTO(): ResponseErrorDTO
	{
		return $this->errorDTO;
	}
}
