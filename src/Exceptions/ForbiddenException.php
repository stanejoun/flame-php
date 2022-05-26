<?php

namespace Stanejoun\FlamePHP\Exceptions;

use Stanejoun\FlamePHP\Request;

class ForbiddenException extends \Exception
{
	public function __construct(string $message = '')
	{
		header(Request::getHeader('SERVER_PROTOCOL') . ' 403 Forbidden');
		parent::__construct($message, 403);
	}
}