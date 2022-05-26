<?php

namespace Stanejoun\FlamePHP;

class ResponseErrorDTO extends AbstractDTO
{
	private int $code;
	private string $message;
	private array $errors;

	public function getCode(): int
	{
		return $this->code;
	}

	public function setCode(int $code): self
	{
		$this->code = $code;
		return $this;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function setMessage(string $message): self
	{
		$this->message = $message;
		return $this;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	public function setErrors(array $errors): self
	{
		$this->errors = $errors;
		return $this;
	}

	public function toJson(): string
	{
		return json_encode([
			'code' => $this->code,
			'message' => $this->message,
			'errors' => $this->errors
		]);
	}
}
