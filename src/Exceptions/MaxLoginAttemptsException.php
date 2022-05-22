<?php

namespace Stanejoun\LightPHP\Exceptions;

use Stanejoun\LightPHP\Request;

class MaxLoginAttemptsException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 400 Bad request');
		parent::__construct($message, 400);
	}
}