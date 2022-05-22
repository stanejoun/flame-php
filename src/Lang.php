<?php

namespace Stanejoun\LightPHP;

class Lang
{
	public static string $CURRENT = 'en';
	public static string $PRIMARY = 'en';

	public static function getAvailableLocales(): array
	{
		$files = scandir(LOCALES);
		$availableLocales = [];
		foreach ($files as $file) {
			if (str_contains($file, '.json')) {
				$availableLocales[] = str_replace('.json', '', $file);
			}
		}
		return $availableLocales;
	}

	public static function setLocale(?string $locale = null): void
	{
		if (!$locale) {
			$locale = Request::get('lang');
			if ($locale === null) {
				$locale = Request::data('lang');
			}
			$acceptLanguage = Request::getHeader('HTTP_ACCEPT_LANGUAGE');
			if ($locale === null && $acceptLanguage !== null) {
				$locale = \Locale::acceptFromHttp($acceptLanguage);
			}
		}
		if ($locale) {
			$isAvailable = false;
			$availableLocales = self::getAvailableLocales();
			foreach ($availableLocales as $availableLocale) {
				if (\Locale::getPrimaryLanguage($locale) === \Locale::getPrimaryLanguage($availableLocale)) {
					$isAvailable = true;
					break;
				}
			}
			if (!$isAvailable) {
				$locale = null;
			}
		}
		if (!$locale) {
			$locale = Config::get('defaultLocale', 'en');
		}
		self::$CURRENT = $locale;
		self::$PRIMARY = \Locale::getPrimaryLanguage($locale);
		putenv("LANG=$locale");
		setlocale(LC_ALL, $locale);
		Translator::load($locale);
	}
}
