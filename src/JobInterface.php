<?php

namespace Stanejoun\FlamePHP;

interface JobInterface
{
	public function run(): void;

	public function isLongJob(): bool;

	public function notify(): void;

	public function runLater(): void;
}