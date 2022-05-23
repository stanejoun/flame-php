<?php

namespace Stanejoun\LightPHP;

abstract class AbstractMessage
{
	public string $action;
	public string $context;
	public string $callback;

	public abstract function send($queueName, $exchangeName = '', $headers = []): void;

	public function getAction(): string
	{
		return $this->action;
	}

	public function setAction(string $action): AbstractMessage
	{
		$this->action = $action;
		return $this;
	}

	public function getContext(): string
	{
		return $this->context;
	}

	public function setContext(string $context): AbstractMessage
	{
		$this->context = $context;
		return $this;
	}

	public function getCallback(): string
	{
		return $this->callback;
	}

	public function setCallback(string $callback): AbstractMessage
	{
		$this->callback = $callback;
		return $this;
	}
}