<?php

namespace Stanejoun\LightPHP;

class Translator
{
	private static array $TRANSLATIONS = [];

	public static function load(string $locale): void
	{
		$translations = self::getLocaleFile($locale);
		if (str_contains($locale, '_')) {
			self::$TRANSLATIONS = array_merge(self::getLocaleFile(\Locale::getPrimaryLanguage($locale)), $translations);
		} else {
			self::$TRANSLATIONS = $translations;
		}
	}

	public static function getLocaleFile(string $locale): array
	{
		$localeFile = LOCALES . "$locale.json";
		if (is_readable($localeFile)) {
			$translations = file_get_contents($localeFile);
			return json_decode($translations, true, 512, \JSON_THROW_ON_ERROR);
		}
		return [];
	}

	public static function translate(string $key, array $replacementValues = []): string
	{
		$translation = self::get($key);
		if (!empty($replacementValues)) {
			$translation = self::replace($translation, $replacementValues);
		}
		return $translation;
	}

	public static function get(string $key): string
	{
		if (isset(self::$TRANSLATIONS[$key]) && (!empty(self::$TRANSLATIONS[$key]) || self::$TRANSLATIONS[$key] === '0')) {
			return self::$TRANSLATIONS[$key];
		}
		return $key;
	}

	public static function replace(string $translation, array $replacementValues): string
	{
		if (!empty($replacementValues)) {
			foreach ($replacementValues as $key => $value) {
				if (str_starts_with($key, ':')) {
					$translation = str_replace($key, $value, $translation);
				} else {
					$translation = str_replace(":$key", $value, $translation);
				}
			}
		}
		return $translation;
	}

	public static function getDecimal(): string
	{
		$format = self::getNumericFormattingInformation();
		return $format['decimal_point'];
	}

	public static function getNumericFormattingInformation(): array
	{
		return localeconv();
	}

	public static function getThousandsSeparator(): string
	{
		$format = self::getNumericFormattingInformation();
		return $format['thousands_sep'];
	}

	public static function getCurrency(): string
	{
		$format = self::getNumericFormattingInformation();
		return $format['currency_symbol'];
	}

	public static function getCurrencyTrigram(): string
	{
		$format = self::getNumericFormattingInformation();
		return $format['int_curr_symbol'];
	}
}