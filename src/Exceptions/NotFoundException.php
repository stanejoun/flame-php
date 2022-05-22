<?php

namespace Stanejoun\LightPHP\Exceptions;

use Stanejoun\LightPHP\Request;

class NotFoundException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 404 Not Found');
		parent::__construct($message, 404);
	}
}
