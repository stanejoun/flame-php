<?php

namespace Stanejoun\FlamePHP;

use Stanejoun\FlamePHP\Exceptions\BusinessException;

class ValidatorHelper
{
	public static function maxLength(string $str, $val): bool
	{
		if (preg_match('/[^0-9]/', $val)) {
			return false;
		}
		if (function_exists('mb_strlen')) {
			return !((mb_strlen($str) > $val));
		}
		return strlen($str) <= $val;
	}

	public static function validInt(mixed $variable, ?string $customMessage = null): void
	{
		self::validInteger($variable, $customMessage);
	}

	public static function validInteger(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_int($variable) && !ctype_digit($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid integer.');
			throw new BusinessException($message);
		}
	}

	public static function validBool(mixed $variable, ?string $customMessage = null): void
	{
		self::validBoolean($variable, $customMessage);
	}

	public static function validBoolean(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_bool($variable) && $variable != '1' && $variable != '0') {
			$message = $customMessage ?? Translator::translate('This value is not a valid boolean.');
			throw new BusinessException($message);
		}
	}

	public static function validArray(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_array($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid array.');
			throw new BusinessException($message);
		}
	}

	public static function validArrayOfStrings(mixed $variable, ?string $customMessage = null): void
	{
		$message = $customMessage ?? Translator::translate('This value is not a valid array of strings.');
		if (!is_array($variable)) {
			throw new BusinessException($message);
		}
		foreach ($variable as $value) {
			self::validString($value, $customMessage);
		}
	}

	public static function validString(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_string($variable) || is_numeric($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid string.');
			throw new BusinessException($message);
		}
	}

	public static function validArrayOfTexts(mixed $variable, ?string $customMessage = null): void
	{
		$message = $customMessage ?? Translator::translate('This value is not a valid array of texts.');
		if (!is_array($variable)) {
			throw new BusinessException($message);
		}
		foreach ($variable as $value) {
			self::validText($value, $customMessage);
		}
	}

	public static function validText(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_string($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid text.');
			throw new BusinessException($message);
		}
	}

	public static function validArrayOfNumerics(mixed $variable, ?string $customMessage = null): void
	{
		$message = $customMessage ?? Translator::translate('This value is not a valid array of numerics.');
		if (!is_array($variable)) {
			throw new BusinessException($message);
		}
		foreach ($variable as $value) {
			self::validNumeric($value, $customMessage);
		}
	}

	public static function validNumeric(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_numeric($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid numeric.');
			throw new BusinessException($message);
		}
	}

	public static function validObject(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_object($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid object.');
			throw new BusinessException($message);
		}
	}

	public static function validFloat(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_float($variable) && !(is_numeric($variable))) {
			$message = $customMessage ?? Translator::translate('This value is not a valid float.');
			throw new BusinessException($message);
		}
	}

	public static function validNumber(mixed $variable, ?string $customMessage = null): void
	{
		self::validNumeric($variable, $customMessage);
	}

	public static function validPassword(mixed $variable, ?string $customMessage = null): void
	{
		if (!is_string($variable) && self::minLength($variable, 8)) {
			$message = $customMessage ?? Translator::translate('This password is not valid.');
			throw new BusinessException($message);
		}
	}

	public static function minLength(string $str, $val): bool
	{
		if (preg_match('/[^0-9]/', $val)) {
			return false;
		}
		if (function_exists('mb_strlen')) {
			return !((mb_strlen($str) < $val));
		}
		return strlen($str) >= $val;
	}

	public static function validEmail(mixed $variable, ?string $customMessage = null): void
	{
		if (!filter_var($variable, FILTER_VALIDATE_EMAIL)) {
			$message = $customMessage ?? Translator::translate('This email is not valid.');
			throw new BusinessException($message);
		}
	}

	public static function validIp(mixed $variable, ?string $customMessage = null): void
	{
		if (!filter_var($variable, FILTER_VALIDATE_IP)) {
			$message = $customMessage ?? Translator::translate('This ip is not valid.');
			throw new BusinessException($message);
		}
	}

	public static function validUrl(mixed $variable, ?string $customMessage = null): void
	{
		if (!filter_var($variable, FILTER_VALIDATE_URL)) {
			$message = $customMessage ?? Translator::translate('This url is not valid.');
			throw new BusinessException($message);
		}
	}

	public static function validDate(mixed $variable, ?string $customMessage = null): void
	{
		if (!self::date($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid date.');
			throw new BusinessException($message);
		}
	}

	private static function date(string $check): bool
	{
		$regex = [];
		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';
		$regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep)(tember)?|(Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';
		$regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]|[2-9]\\d)\\d{2})$%';
		$regex['my'] = '%^(((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))))$%';
		$dateFormats = ['dmy', 'mdy', 'ymd', 'dMy', 'Mdy', 'My', 'my'];
		foreach ($dateFormats as $key) {
			if (preg_match($regex[$key], $check) === true) {
				return true;
			}
		}
		return false;
	}

	public static function validDatetime(mixed $variable, ?string $customMessage = null): void
	{
		if (!self::datetime($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid date time.');
			throw new BusinessException($message);
		}
	}

	private static function datetime(string $check): bool
	{
		$valid = false;
		if ($check instanceof \DateTime) {
			$check = $check->format('Y-m-d H:i');
		}
		$parts = explode(' ', $check);
		if (!empty($parts) && count($parts) > 1) {
			$time = array_pop($parts);
			$date = implode(' ', $parts);
			$valid = self::date($date) && self::time($time);
		}
		return $valid;
	}

	private static function time(string $check): bool
	{
		return preg_match('%^((0?[1-9]|1[012])(:[0-5]\d){0,2} ?([AP]M|[ap]m))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$%', $check);
	}

	public static function validTime(mixed $variable, ?string $customMessage = null): void
	{
		if (!self::time($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid hour.');
			throw new BusinessException($message);
		}
	}

	public static function validPhone(mixed $variable, ?string $customMessage = null): void
	{
		if (!self::phone($variable)) {
			$message = $customMessage ?? Translator::translate('This value is not a valid phone number.');
			throw new BusinessException($message);
		}
	}

	private static function phone(string $check): bool
	{
		return preg_match('`^([(]?[+]\s*(\d{1,3}[-]?\d{1,3}?)[)]?)?\s*([(]?0\d*[)]?)?[\s.-]*(\d[\s.-]*){8,10}$`i', $check);
	}

	public static function validFileFormats(string $inputName, array $allowedFileFormats, ?string $customMessage = null): void
	{
		if (Request::hasFiles($inputName)) {
			if (is_array($_FILES[$inputName]['tmp_name'])) {
				foreach (array_keys($_FILES[$inputName]['tmp_name']) as $index) {
					if (!self::isAllowedFileFormat($allowedFileFormats, $_FILES[$inputName]['tmp_name'][$index])) {
						$message = $customMessage ?? Translator::translate('The files formats are not accepted.');
						throw new BusinessException($message);
					}
				}
			} else if (!self::isAllowedFileFormat($allowedFileFormats, $_FILES[$inputName]['tmp_name'])) {
				$message = $customMessage ?? Translator::translate('This file format is not accepted.');
				throw new BusinessException($message);
			}
		}
	}

	public static function isAllowedFileFormat(array $allowedFileFormats, string $filename): bool
	{
		$fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		$fileMimeType = $fileInfo->file($filename);
		foreach (File::AVAILABLE_MIME_TYPES as $extensions => $mimeType) {
			if (in_array($extensions, $allowedFileFormats) && $mimeType === $fileMimeType) {
				return true;
			}
		}
		return false;
	}

	public static function validFileSize(string $inputName, int $maxSize, ?string $customMessage = null): void
	{
		if (Request::hasFiles($inputName)) {
			if (is_array($_FILES[$inputName]['tmp_name'])) {
				foreach (array_keys($_FILES[$inputName]['tmp_name']) as $index) {
					$fileSize = filesize($_FILES[$inputName]['tmp_name'][$index]);
					if ($fileSize > $maxSize) {
						$message = $customMessage ?? Translator::translate('The files sizes are larger than the maximum allowed.');
						throw new BusinessException($message);
					}
				}
			} else {
				$fileSize = filesize($_FILES[$inputName]['tmp_name']);
				if ($fileSize > $maxSize) {
					$message = $customMessage ?? Translator::translate('This file size is larger than the maximum allowed.');
					throw new BusinessException($message);
				}
			}
		}
	}

	public static function validFile(string $inputName, ?string $customMessage = null): void
	{
		if (Request::hasFiles($inputName)) {
			if (is_array($_FILES[$inputName]['tmp_name'])) {
				foreach (array_keys($_FILES[$inputName]['tmp_name']) as $index) {
					if (!is_uploaded_file($_FILES[$inputName]['tmp_name'][$index])) {
						$message = $customMessage ?? Translator::translate('Internal server error, unable to upload the files.');
						throw new BusinessException($message);
					}
				}
			} else if (!is_uploaded_file($_FILES[$inputName]['tmp_name'])) {
				$message = $customMessage ?? Translator::translate('Internal server error, unable to upload this file.');
				throw new BusinessException($message);
			}
		}
	}
}