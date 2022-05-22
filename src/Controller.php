<?php

namespace Stanejoun\LightPHP;

use Stanejoun\LightPHP\Exceptions\ForbiddenException;

class Controller extends AbstractCommon
{
	protected View $view;

	public function render(string $view, $data = null): void
	{
		$filename = TEMPLATES . $view;
		$filename .= (!str_contains($filename, '.php')) ? '.php' : '';
		$this->getView()->load($filename, $data);
	}

	public function getView(): View
	{
		if (!isset($this->view)) {
			$this->view = new View();
		}
		return $this->view;
	}

	public function renderContent(string $view, $object = null): void
	{
		$filename = TEMPLATES . $view;
		$filename .= (!str_contains($filename, '.php')) ? '.php' : '';
		Response::SendHtml($this->getView()->getContent($filename, $object));
	}

	public function checkToken(string $name): void
	{
		$valid = Security::checkToken($name);
		if (!$valid) {
			throw new ForbiddenException('Invalid token "' . $name . '"!');
		}
	}
}