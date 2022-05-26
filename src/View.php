<?php

namespace Stanejoun\FlamePHP;

class View extends AbstractCommon
{
	private string $name;
	#[ArrayOf('string')]
	private array $blocks;
	private string $layout;
	private mixed $data;

	public function __construct()
	{
		$this->initBlocks();
	}

	public function initBlocks(): void
	{
		$this->blocks = [];
		$this->blocks['script'] = '';
		$this->blocks['style'] = '';
		$this->layout = 'default';
	}

	public function setLayout(string $layout): void
	{
		$this->layout = $layout;
	}

	public function __get(string $name): mixed
	{
		if (!empty($this->data)) {
			if (is_array($this->data) && isset($this->data[$name])) {
				$value = $this->data[$name];
				if (is_string($value)) {
					$value = Security::purifyData($value);
				}
				return $value;
			}
			if (is_object($this->data) && property_exists($this->data, $name)) {
				$value = Helper::getPropertyValue($this->data, $name);
				if (is_string($value)) {
					$value = Security::purifyData($value);
				}
				return $value;
			}
		}
		return null;
	}

	public function __call($name, $arguments): mixed
	{
		if (!empty($this->data)) {
			return call_user_func_array([$this->data, $name], $arguments);
		}
		throw new \Exception('Call to undefined method: "' . $name . '".');
	}

	public function load(string $filename, mixed $data = null): void
	{
		$this->initBlocks();
		if (!file_exists($filename)) {
			throw new \Exception('File: "' . $filename . '"  not found!');
		}
		$this->data = $data;
		ob_start();
		require_once $filename;
		ob_end_clean();
		$this->loadLayout();
	}

	public function loadLayout(): void
	{
		$layoutPath = TEMPLATES . 'layouts/' . $this->layout;
		$layoutPath .= (!str_contains($this->layout, '.php')) ? '.php' : '';
		if (!file_exists($layoutPath)) {
			throw new \Exception("$layoutPath not found!");
		}
		require_once $layoutPath;
	}

	public function include(string $view): void
	{
		$filename = TEMPLATES . $view;
		$filename .= (!str_contains($filename, '.php')) ? '.php' : '';
		if (!file_exists($filename)) {
			throw new \Exception('File: "' . $filename . '"  not found!');
		}
		require $filename;
	}

	public function getContent(string $view, mixed $data = null): string
	{
		$this->initBlocks();
		$filename = TEMPLATES . $view;
		$filename .= (!str_contains($filename, '.php')) ? '.php' : '';
		if (!file_exists($filename)) {
			throw new \Exception('File: "' . $filename . '"  not found!');
		}
		$this->data = $data;
		ob_start();
		require $filename;
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function getBlock(string $name): string
	{
		$block = '';
		if (isset($this->blocks[$name])) {
			$block = $this->blocks[$name] . "\n";
			unset($this->blocks[$name]);
		}
		return $block;
	}

	public function startBlock(string $name): void
	{
		$this->name = $name;
		ob_start();
	}

	public function endBlock(): static
	{
		$this->setBlock($this->name, ob_get_clean());
		$this->name = '';
		return $this;
	}

	public function setBlock(string $name, string $value): void
	{
		$value = trim($value);
		if ($name == 'script' || $name == 'style') {
			$this->blocks[$name] .= $value;
		} else {
			$this->blocks[$name] = $value;
		}
	}

	public function url(string $routeName, array $params = []): string
	{
		return Router::getUrl($routeName, $params);
	}

	public function inputToken(string $tokenId, int $expire = 0, string $name = 'token'): string
	{
		if (empty($expire) || !is_int($expire)) {
			$expire = 1800 / 60;
		}
		$token = Security::token($tokenId, $expire);
		return '<input class="input-token" type="hidden" name="' . $name . '" value="' . $token . '" >' . "\n";
	}

	public function getTokenValue(string $tokenId = '', int $expire = 0): string
	{
		return Security::token($tokenId, $expire);
	}

	public function checked(bool $condition): string
	{
		if ($condition) {
			return ' checked ';
		}
		return '';
	}

	public function selected(bool $condition): string
	{
		if ($condition) {
			return ' selected ';
		}
		return '';
	}

	public function ternary(bool $condition, string $output): string
	{
		if ($condition) {
			return $output;
		}
		return '';
	}

	public function translate(string $key, array $replacementValues = []): string
	{
		return Translator::translate($key, $replacementValues);
	}
}