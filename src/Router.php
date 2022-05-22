<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\BadRequestException;
use Stanejoun\LightPHP\Exceptions\InternalServerErrorException;
use Stanejoun\LightPHP\Exceptions\NotFoundException;
use Stanejoun\LightPHP\Exceptions\UnauthorizedException;

class Router
{
	public static function request(): void
	{
		Session::start();
		Lang::setLocale();
		/** @var string[] $corsRestrictedOrigins */
		$corsRestrictedOrigins = Config::get('corsRestrictedOrigins');
		$http_origin = Request::getHeader('HTTP_ORIGIN');
		if (!empty($corsRestrictedOrigins) && !in_array($http_origin, $corsRestrictedOrigins)) {
			throw new UnauthorizedException(Translator::translate('Cross-Origin Request Blocked: The Same Origin Policy disallows reading the remote resource.'));
		}
		header("Access-Control-Allow-Origin: $http_origin");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 86400');
		if (Request::getHeader('REQUEST_METHOD') === 'OPTIONS') {
			if (Request::getHeader('HTTP_ACCESS_CONTROL_REQUEST_METHOD')) {
				header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
			}
			if (Request::getHeader('HTTP_ACCESS_CONTROL_REQUEST_HEADERS')) {
				header('Access-Control-Allow-Headers: ' . Request::getHeader('HTTP_ACCESS_CONTROL_REQUEST_HEADERS'));
			}
		} else {
			if (Config::get('protocol') === 'https' && self::getCurrentHttpProtocol() !== 'https') {
				throw new BadRequestException(Translator::translate('Https protocol required!'));
			} else {
				$route = self::getRoute();
				if (AccessControls::check($route)) {
					self::execute($route);
				} else {
					if (!Request::isRemoteRequest() && Config::get('defaultRoutes')->login) {
						self::redirect(Router::getUrl(Config::get('defaultRoutes')->login, [], '?redirect=' . self::getCurrentRequestUri()));
					} else {
						throw new UnauthorizedException(Translator::translate('Authorization Required'));
					}
				}
			}
		}
		exit;
	}

	public static function getCurrentHttpProtocol(): string
	{
		$serverPort = Request::getHeader('SERVER_PORT');
		if (!empty($serverPort)) {
			return ($serverPort == '443') ? 'https' : 'http';
		}
		$https = Request::getHeader('HTTPS');
		$http_x_forwarded_proto = Request::getHeader('HTTP_X_FORWARDED_PROTO');
		return (isset($https) && ((!empty($https) || $https !== 'on') || (!empty($http_x_forwarded_proto) && $http_x_forwarded_proto === 'https'))) ? 'https' : 'http';
	}

	private static function getRoute(): Route
	{
		$requestUri = Request::get('request_uri');
		Request::remove('GET', 'request_uri');
		$requestMethod = self::getRequestMethod();
		/** @var Route $route */
		$route = null;
		$routes = Routes::get();
		/** @var Route[] $routeValues */
		foreach ($routes as $routeValues) {
			$arguments_values = [];
			$routeValuesRequestMethod = (!empty($routeValues->getMethod())) ? strtoupper($routeValues->getMethod()) : null;
			if (($routeValuesRequestMethod === $requestMethod || $routeValuesRequestMethod === null) && preg_match('`^' . $routeValues->getPattern() . '$`', "/$requestUri", $matches) === 1) {
				if (!empty($routeValues->getArguments())) {
					$argumentsNames = $routeValues->getArguments();
					foreach ($matches as $key => $match) {
						if ($key !== 0) {
							$argumentName = $argumentsNames[$key - 1];
							$arguments_values[$argumentName] = $match;
							Request::set($argumentName, $match);
						}
					}
				}
				$route = $routeValues;
				$route
					->setMethod($requestMethod)
					->setArgumentsValues($arguments_values);
				break;
			}
		}
		if ($route === null) {
			throw new NotFoundException(Translator::translate('Not found. The server cannot find the requested resource.'));
		}
		return $route;
	}

	private static function getRequestMethod(): string
	{
		$requestMethod = Request::getHeader('REQUEST_METHOD');
		$requestMethod = (!empty($requestMethod)) ? $requestMethod : 'GET';
		return strtoupper($requestMethod);
	}

	private static function execute(Route $route): void
	{
		$controller = self::getController($route->getController());
		if (is_callable([$controller, $route->getFunction()]) && method_exists($controller, $route->getFunction())) {
			try {
				$result = call_user_func_array([$controller, $route->getFunction()], $route->getArgumentsValues());
			} catch (\Error $e) {
				throw new InternalServerErrorException($e->getMessage());
			}
			if (isset($result)) {
				Response::SetHeader(200);
				if ($result instanceof ResponseInterface) {
					$result->send();
				} else {
					Response::SendJson($result);
				}
			}
		} else {
			throw new \RuntimeException('Unable to call "' . get_class($controller) . ' -> ' . $route->getFunction() . '"!');
		}
	}

	private static function getController(string $controller): Controller
	{
		if (!class_exists($controller)) {
			throw new \RuntimeException("$controller not found!");
		}
		return new $controller();
	}

	public static function redirect(string $url): void
	{
		header('Content-Type: text/html; charset=UTF-8');
		header('Location: ' . $url);
		exit;
	}

	public static function getUrl(string $routeName, array $argumentsValues = [], string $urlParameters = ''): string
	{
		$routes = Routes::get();
		if (!isset($routes[$routeName])) {
			throw new \RuntimeException('Route: "' . $routeName . '" not set!');
		}
		$url = BASE_URL;
		if (count($routes[$routeName]->getArguments()) > 0) {
			if (empty($argumentsValues)) {
				throw new \RuntimeException('Invalid arguments for the route: "' . $routeName . '"!');
			}
			$pattern = str_replace("\\", '', $routes[$routeName]->getPattern());
			$patternArrayExplodeByParameters = preg_split('/[()]/', $pattern);
			foreach ($patternArrayExplodeByParameters as $index => $patternPart) {
				if (str_starts_with($patternPart, '[')) {
					$url .= (string)$argumentsValues[$index];
				} else {
					$url .= $patternPart;
				}
			}
		} else {
			$url .= $routes[$routeName]->getPattern();
		}
		if ($urlParameters) {
			$url .= $urlParameters;
		}
		return $url;
	}

	public static function getCurrentRequestUri(): string
	{
		return Request::getHeader('REQUEST_URI');
	}
}
