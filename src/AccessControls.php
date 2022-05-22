<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\ForbiddenException;

class AccessControls
{
	#[ArrayOf('\Stanejoun\LightPHP\Role')]
	private static array $ROLES;

	final public static function check(Route $route): bool
	{
		if (empty($route->getAccessControl())) {
			return true;
		}
		if (Authentication::getAuthenticatedUser() !== null) {
			self::checkAccess($route->getAccessControl());
			return true;
		}
		return false;
	}

	final public static function checkAccess(mixed $roleName): void
	{
		if (is_array($roleName)) {
			foreach ($roleName as $currentRoleName) {
				if (!self::hasRight($currentRoleName)) {
					throw new ForbiddenException();
				}
			}
		} else {
			if (!self::hasRight($roleName)) {
				throw new ForbiddenException();
			}
		}
	}

	final public static function hasRight(mixed $roleName): bool
	{
		if (is_array($roleName)) {
			foreach ($roleName as $currentRoleName) {
				if (!self::hasRight($currentRoleName)) {
					return false;
				}
			}
			return true;
		}
		/** @var User $user */
		$user = Authentication::getAuthenticatedUser();
		if ($user !== null) {
			$userRoles = $user->getRoles();
			if (in_array($roleName, $userRoles)) {
				return true;
			}
			$rolePriorities = [];
			/** @var Role[] $roles */
			$roles = self::getRoles();
			foreach ($roles as $role) {
				$rolePriorities[$role->getName()] = $role->getPriority();
			}
			foreach ($userRoles as $userRole) {
				if (isset($rolePriorities[$roleName]) && isset($rolePriorities[$userRole]) && $rolePriorities[$userRole] < $rolePriorities[$roleName]) {
					return true;
				}
			}
		}
		return false;
	}

	final public static function getRoles(): array
	{
		if (!isset(self::$ROLES)) {
			$content = file_get_contents(ROOT . 'config/' . 'access.json');
			$content = json_decode($content, false, 512, \JSON_THROW_ON_ERROR);
			$roles = [];
			foreach ($content->roles as $role) {
				$roles[] = new Role($role->name, $role->description, $role->priority);
			}
			self::$ROLES = $roles;
		}
		return self::$ROLES;
	}
}
