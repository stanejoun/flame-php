<?php

namespace Stanejoun\LightPHP;

class Role
{
	private string $name;
	private string $description;
	private int $priority;

	public function __construct(string $name, string $description, int $priority)
	{
		$this->name = $name;
		$this->description = $description;
		$this->priority = $priority;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getPriority(): int
	{
		return $this->priority;
	}
}