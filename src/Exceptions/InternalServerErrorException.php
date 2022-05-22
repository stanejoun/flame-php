<?php

namespace Stanejoun\LightPHP\Exceptions;

use Stanejoun\LightPHP\Request;

class InternalServerErrorException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 500 Internal Server Error');
		parent::__construct($message, 500);
	}
}