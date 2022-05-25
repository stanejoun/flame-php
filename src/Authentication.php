<?php

namespace Stanejoun\LightPHP;

use Exception;
use Firebase\JWT\JWT;
use Stanejoun\LightPHP\Exceptions\InternalServerErrorException;
use Stanejoun\LightPHP\Exceptions\MaxLoginAttemptsException;
use Stanejoun\LightPHP\Exceptions\UnauthorizedException;
use Stanejoun\LightPHP\Exceptions\UntrustedUserException;

class Authentication
{
	public static ?User $AUTHENTICATED_USER = null;

	public static function register(User $user): void
	{
		Session::set('user', $user);
	}

	public static function logout(): void
	{
		Session::set('user', null);
	}

	public static function getAuthenticatedUser(): ?User
	{
		if (Request::hasHeader('HTTP_AUTHORIZATION') && self::$AUTHENTICATED_USER === null) {
			$userData = self::authenticate();
			$userClass = self::getUserClass();
			$user = new $userClass();
			$user->setId($userData['id']);
			$user->setRoles($userData['roles']);
			self::$AUTHENTICATED_USER = $user;
		}
		if (!empty(self::$AUTHENTICATED_USER) && self::$AUTHENTICATED_USER->getId() > 0) {
			return self::$AUTHENTICATED_USER;
		}
		return Session::get('user');
	}

	public static function authenticate(): array
	{
		$httpAuthorization = Request::getHeader('HTTP_AUTHORIZATION');
		if ($httpAuthorization) {
			$apiKey = explode(' ', $httpAuthorization);
			if (!empty($apiKey) && count($apiKey) === 2 && strtolower($apiKey[0]) === 'bearer' && !empty($apiKey[1])) {
				$jwtBase64 = $apiKey[1];
				$jwt = base64_decode($jwtBase64);
				try {
					$decoded = JWT::decode($jwt, Config::get('secretKey'), ['HS256']);
				} catch (Exception $e) {
					throw new UnauthorizedException(Translator::translate('Invalid jwt token!'));
				}
				if ($decoded->data->fingerprint !== Security::getFingerprint()) {
					throw new UnauthorizedException(Translator::translate('Invalid fingerprint!'));
				}
				if (!empty($decoded->data->id)) {
					if (self::isTokenRevoked($decoded->data->id)) {
						throw new UnauthorizedException(Translator::translate('Authorization revoked!'));
					}
					if (!$roles = json_decode($decoded->data->roles)) {
						throw new InternalServerErrorException();
					}
					return [
						'id' => $decoded->data->id,
						'roles' => $roles
					];
				}
			}
		}
		throw new UnauthorizedException(Translator::translate('Authorization required!'));
	}

	private static function isTokenRevoked(int $userId): bool
	{
		if (Config::get('redis') && Config::get('redis')->enable) {
			$disabledToken = Redis::getInstance()->get('disabled_token:' . $userId);
		} else {
			$fileCache = new FileCache((Config::get('accessTokenTime') + 1), 'authentication/disabled_token');
			$disabledToken = $fileCache->read(md5($userId), true);
		}
		if (!empty($disabledToken)) {
			return true;
		}
		return false;
	}

	private static function getUserClass(): string
	{
		if (class_exists('\App\Model\User')) {
			return '\App\Model\User';
		} else {
			return '\Stanejoun\LightPHP\User';
		}
	}

	public static function login(string $userIdentifierValue = null, string $password = null): User
	{
		$userClass = self::getUserClass();
		/** @var User $user */
		$user = call_user_func([$userClass, 'findOne'], (new Query())
			->where((string)User::$USER_IDENTIFIER_PROPERTY . ' = :' . (string)User::$USER_IDENTIFIER_PROPERTY)
			->args([(string)User::$USER_IDENTIFIER_PROPERTY => $userIdentifierValue])
		);
		if ($user !== null) {
			if (!$user->isTrusted()) {
				throw new UntrustedUserException(Translator::translate('This user has not been validated!'));
			}
			if ($user->getLoginAttempts() > 10) {
				throw new MaxLoginAttemptsException(Translator::translate('Max login attempts!'));
			}
			if ($password !== null && Security::checkPassword($password, $user->getPassword())) {
				$user->setLoginAttempts(0);
				$user->save();
				return $user;
			}
			$loginAttempts = $user->getLoginAttempts();
			$loginAttempts++;
			$user->setLoginAttempts($loginAttempts);
			$user->save();
		}
		throw new UnauthorizedException(Translator::translate('Invalid email and/or password!'));
	}

	public static function refreshToken(string $refreshTokenValue): array
	{
		/** @var RefreshToken $refreshToken */
		$refreshToken = RefreshToken::findOneBy('token', $refreshTokenValue);
		if (null !== $refreshToken) {
			$currentTimestamp = time();
			$currentFingerprint = Security::getFingerprint();
			if ($currentTimestamp <= $refreshToken->getExpiredAt() && $currentFingerprint === $refreshToken->getFingerprint()) {
				$userId = $refreshToken->getUserId();
				$refreshToken->delete();
				/** @var User $user */
				$user = User::findOneById($userId);
				if ($user !== null) {
					return self::getJSONWebToken($user);
				}
			}
		}
		throw new UnauthorizedException(Translator::translate('Invalid jwt token!'));
	}

	public static function getJSONWebToken(User $user): array
	{
		$issueDateClaim = time();
		$notBeforeClaim = $issueDateClaim;
		$expireClaim = $issueDateClaim + Config::get('accessTokenTime');
		$payload = [
			'iss' => Config::get('host'),
			'aud' => Config::get('host'),
			'iat' => $issueDateClaim,
			'nbf' => $notBeforeClaim,
			'exp' => $expireClaim,
			'data' => [
				'id' => $user->getId(),
				'roles' => json_encode($user->getRoles()),
				'fingerprint' => Security::getFingerprint(),
			]
		];
		$jwt = JWT::encode($payload, Config::get('secretKey'));
		self::deleteExpiredRefreshTokens();
		$refreshToken = new RefreshToken();
		$refreshToken->setToken(Security::hash(uniqid() . date('-Ymd-His-') . $user->getSalt()))
			->setExpiredAt(time() + Config::get('refreshTokenTime'))
			->setUserId($user->id)
			->setFingerprint(Security::getFingerprint());
		$refreshToken->save();
		self::deleteRevokeAccessToken($user);
		return [
			'accessToken' => base64_encode($jwt),
			'refreshToken' => $refreshToken->getToken(),
			'user' => $user->getPublicData()
		];
	}

	public static function deleteExpiredRefreshTokens(): void
	{
		RefreshToken::deleteAll(
			(new Query())
				->where('expired_at < :timestamp')
				->args(['timestamp' => time()])
		);
	}

	private static function deleteRevokeAccessToken(User $user): void
	{
		if (Config::get('redis') && Config::get('redis')->enable) {
			Redis::getInstance()->del('disabled_token:' . $user->getId());
		} else {
			$fileCache = new FileCache((Config::get('accessTokenTime') + 1), 'authentication/disabled_token');
			$fileCache->remove(md5($user->getId()));
		}
	}

	public static function revokeAccessToken(User $user): void
	{
		if (Config::get('redis') && Config::get('redis')->enable) {
			Redis::getInstance()->set('disabled_token:' . $user->getId(), 1, 'ex', (Config::get('accessTokenTime') + 1));
		} else {
			$fileCache = new FileCache((Config::get('accessTokenTime') + 1), 'authentication/disabled_token');
			$fileCache->write(md5($user->getId()), $user->getId());
		}
		self::deleteRefreshToken($user);
	}

	public static function deleteRefreshToken(User $user): void
	{
		$refreshTokens = RefreshToken::find(
			(new Query())
				->where('user_id = :user_id')
				->args(['user_id' => $user->getId()])
		);
		foreach ($refreshTokens as $refreshToken) {
			$refreshToken->delete();
		}
	}
}