<?php

namespace Stanejoun\FlamePHP;

#[ModelDescription([
	'table' => 'user',
	'softDelete' => true,
	'encryptedFields' => [
		'email'
	]
])]
class User extends AbstractModel
{
	public static mixed $USER_IDENTIFIER_PROPERTY = 'email';

	public int $id = 0;
	protected string $uid = '';
	protected string $email = '';
	protected string $password = '';
	protected string $salt = '';
	protected bool $trusted = false;
	protected ?\DateTime $createdAt = null;
	protected ?\DateTime $updatedAt = null;
	protected ?\DateTime $deletedAt = null;
	#[ArrayOf('string')]
	protected array $roles = [];
	protected int $loginAttempts = 0;

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): User
	{
		$this->id = $id;
		return $this;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function setUid(string $uid): User
	{
		$this->uid = $uid;
		return $this;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): User
	{
		$this->email = $email;
		return $this;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword(string $password): User
	{
		$this->password = $password;
		return $this;
	}

	public function getSalt(): string
	{
		return $this->salt;
	}

	public function setSalt(string $salt): User
	{
		$this->salt = $salt;
		return $this;
	}

	public function isTrusted(): bool
	{
		return $this->trusted;
	}

	public function setTrusted(bool $trusted): User
	{
		$this->trusted = $trusted;
		return $this;
	}

	public function getCreatedAt(): ?\DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?\DateTime $createdAt): User
	{
		$this->createdAt = $createdAt;
		return $this;
	}

	public function getUpdatedAt(): ?\DateTime
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?\DateTime $updatedAt): User
	{
		$this->updatedAt = $updatedAt;
		return $this;
	}

	public function getDeletedAt(): ?\DateTime
	{
		return $this->deletedAt;
	}

	public function setDeletedAt(?\DateTime $deletedAt): User
	{
		$this->deletedAt = $deletedAt;
		return $this;
	}

	public function getRoles(): array
	{
		return $this->roles;
	}

	public function setRoles(array $roles): User
	{
		$this->roles = $roles;
		return $this;
	}

	public function getLoginAttempts(): int
	{
		return $this->loginAttempts;
	}

	public function setLoginAttempts(int $loginAttempts): User
	{
		$this->loginAttempts = $loginAttempts;
		return $this;
	}

	public function getPublicData(): array
	{
		return [];
	}
}