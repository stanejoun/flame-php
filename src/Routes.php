<?php

namespace Stanejoun\LightPHP;

class Routes
{
	#[ArrayOf('\Stanejoun\LightPHP\Route')]
	private static array $ROUTES = [];

	/**
	 * @return Route[]
	 */
	final public static function get(): array
	{
		if (empty(self::$ROUTES)) {
			try {
				$cache = Cache::getInstance('/', 0);
				if ($routes = $cache->read('routes')) {
					self::$ROUTES = $routes;
				} else {
					$routes = [];
					self::parseControllers(ROOT . 'src/Controller', $routes);
					$cache->write('routes', $routes);
				}
			} catch (\Exception $e) {
				header('HTTP/1.1 500 Internal Server Error');
				die('Error! Please let us know what you were doing when this error occurred. We will fix it as soon as possible. Sorry for any inconvenience caused.');
			}
		}
		return self::$ROUTES;
	}

	final public static function parseControllers(string $dir, array &$routes): void
	{
		$filenames = scandir($dir);
		foreach ($filenames as $filename) {
			if ($filename !== '.' && $filename !== '..') {
				if (is_dir("$dir/$filename")) {
					self::parseControllers("$dir/$filename", $routes);
				} else {
					$class = str_replace(ROOT, '\\', "$dir/$filename");
					$class = str_replace('\\src/', '\\App/', $class);
					$class = str_replace('.php', '', $class);
					$class = str_replace('/', '\\', $class);
					$class = str_replace('..', '', $class);
					if (class_exists($class)) {
						$reflectionClass = new \ReflectionClass($class);
						$methods = $reflectionClass->getMethods();
						foreach ($methods as $method) {
							$reflectionMethod = new \ReflectionMethod($class, $method->getName());
							$attributes = $reflectionMethod->getAttributes();
							if (!empty($attributes)) {
								/** @var Route $route */
								$route = $attributes[0]->newInstance();
								$route->setController($class);
								$route->setFunction($method->getName());
								$arguments = [];
								$pattern = $route->getPattern();
								preg_match('({[a-zA-Z0-9}]+)', $pattern, $matches);
								foreach ($matches as $match) {
									$pattern = str_replace($match, '([a-zA-Z0-9]+)', $pattern);
									$arguments[] = substr($match, 1, -1);
								}
								if (!empty($arguments)) {
									$route->setArguments($arguments);
									$route->setPattern($pattern);
								}
								self::add($route->getName(), $route);
								$routes[$route->getName()] = $route;
							}
						}
					}
				}
			}
		}
	}

	final public static function add(string $routeName, Route $values): void
	{
		$values->setName($routeName);
		self::$ROUTES[$routeName] = $values;
	}
}