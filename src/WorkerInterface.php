<?php

namespace Stanejoun\FlamePHP;

interface WorkerInterface
{
	public function consume(string $queueName);
}