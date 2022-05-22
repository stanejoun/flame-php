<?php

namespace Stanejoun\LightPHP\Exceptions;

use Stanejoun\LightPHP\Request;

class UnauthorizedException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 401 Unauthorized');
		parent::__construct($message, 401);
	}
}