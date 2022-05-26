<?php

namespace Stanejoun\FlamePHP\Exceptions;

use Stanejoun\FlamePHP\Request;

class MaxLoginAttemptsException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 400 Bad request');
		parent::__construct($message, 400);
	}
}