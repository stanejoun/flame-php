<?php

namespace Stanejoun\FlamePHP;

class HtmlResponse implements ResponseInterface
{
	public string $content;

	public function __construct(string $content)
	{
		$this->content = Security::purifyData($content);
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function send(): void
	{
		Response::sendHtml($this->content);
	}
}
