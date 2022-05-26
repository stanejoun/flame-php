<?php

namespace Stanejoun\FlamePHP;

class JsonResponse implements ResponseInterface
{
	public mixed $content;

	public function __construct(mixed $content)
	{
		$this->content = $content;
	}

	public function getContent(): string
	{
		if (!is_string($this->content)) {
			$this->content = json_encode(Security::purifyData($this->content));
		}
		return $this->content;
	}

	public function send(): void
	{
		Response::sendJson($this->content);
	}
}
