<?php

namespace Stanejoun\LightPHP;

interface WorkerInterface
{
	public function consume(string $queueName);
}