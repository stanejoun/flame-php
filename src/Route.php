<?php

namespace Stanejoun\LightPHP;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route
{
	private string $controller = '';
	private string $function = '';
	private string $pattern = '';
	#[ArrayOf('string')]
	private array $arguments = [];
	#[ArrayOf('string')]
	private array $argumentsValues = [];
	private ?string $method = null;
	private string $name = '';
	/** @var string|string[]|null */
	private mixed $accessControl = null;

	public function __construct(string $name, string $pattern, ?string $method = null, mixed $accessControl = null)
	{
		$this->name = $name;
		$this->pattern = $pattern;
		$this->method = $method;
		$this->accessControl = $accessControl;
	}

	public function getController(): string
	{
		return $this->controller;
	}

	public function setController(string $controller): Route
	{
		$this->controller = $controller;
		return $this;
	}

	public function getFunction(): string
	{
		return $this->function;
	}

	public function setFunction(string $function): Route
	{
		$this->function = $function;
		return $this;
	}

	public function getPattern(): string
	{
		return $this->pattern;
	}

	public function setPattern(string $pattern): Route
	{
		$this->pattern = $pattern;
		return $this;
	}

	public function getArguments(): array
	{
		return $this->arguments;
	}

	public function setArguments(array $arguments): Route
	{
		$this->arguments = $arguments;
		return $this;
	}

	public function getArgumentsValues(): array
	{
		return $this->argumentsValues;
	}

	public function setArgumentsValues(array $argumentsValues): Route
	{
		$this->argumentsValues = $argumentsValues;
		return $this;
	}

	public function getMethod(): ?string
	{
		return $this->method;
	}

	public function setMethod(?string $method): Route
	{
		$this->method = $method;
		return $this;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): Route
	{
		$this->name = $name;
		return $this;
	}

	public function getAccessControl(): mixed
	{
		return $this->accessControl;
	}
}
