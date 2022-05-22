<?php

namespace Stanejoun\LightPHP;

#[ModelDescription(['table' => 'refresh_token'])]
class RefreshToken extends AbstractModel
{
	public int $id = 0;
	protected string $token;
	protected string $fingerprint;
	protected int $userId;
	protected int $expiredAt;

	/**
	 * @return string
	 */
	public function getToken(): string
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 * @return RefreshToken
	 */
	public function setToken(string $token): RefreshToken
	{
		$this->token = $token;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFingerprint(): string
	{
		return $this->fingerprint;
	}

	/**
	 * @param string $fingerprint
	 * @return RefreshToken
	 */
	public function setFingerprint(string $fingerprint): RefreshToken
	{
		$this->fingerprint = $fingerprint;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 * @return RefreshToken
	 */
	public function setUserId(int $userId): RefreshToken
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getExpiredAt(): int
	{
		return $this->expiredAt;
	}

	/**
	 * @param int $expiredAt
	 * @return RefreshToken
	 */
	public function setExpiredAt(int $expiredAt): RefreshToken
	{
		$this->expiredAt = $expiredAt;
		return $this;
	}
}
