<?php

namespace Stanejoun\FlamePHP;

use Stanejoun\FlamePHP\Exceptions\InternalServerErrorException;

class DataBase
{
	private static ?\PDO $INSTANCE = null;

	final public static function executeQuery(string $sql, array $data = []): \PDOStatement
	{
		$dbh = self::getInstance();
		try {
			$bindValues = [];
			if (!empty($data)) {
				foreach ($data as $key => $value) {
					if (is_array($value)) {
						$placeholders = [];
						foreach ($value as $index => $item) {
							$newKey = ":$key" . '_' . $index;
							$placeholders[] = $newKey;
							$bindValues[$newKey] = $item;
						}
						if (!empty($placeholders)) {
							$sql = str_replace(":$key", implode(', ', $placeholders), $sql);
						}
					} else {
						$bindValues[$key] = $value;
					}
				}
			}
			/** @var \PDOStatement $stmt */
			$stmt = $dbh->prepare($sql);
			if (!$stmt->execute($bindValues)) {
				$errorMessage = 'PDO statement execution error!';
				Logger::debug($errorMessage, $dbh->errorInfo());
				throw new \RuntimeException($errorMessage);
			}
		} catch (\Exception $e) {
			throw new InternalServerErrorException($e->getMessage());
		}
		return $stmt;
	}

	final public static function getInstance(): \PDO
	{
		if (!isset(self::$INSTANCE)) {
			self::$INSTANCE = self::connection();
		}
		return self::$INSTANCE;
	}

	final public static function connection(): \PDO
	{
		try {
			return new \PDO(Config::get('database')->dsn, Config::get('database')->user, Config::get('database')->password);
		} catch (\PDOException $e) {
			throw new InternalServerErrorException($e->getMessage());
		}
	}

	final public static function changeDatabase(string $database): void
	{
		self::$INSTANCE->exec('USE ' . $database);
	}

	public static function executeSqlFile(string $filename): void
	{
		$configPath = __DIR__ . '/../db.cnf';
		if (file_exists($configPath)) {
			unlink($configPath);
		}
		$user = Config::get('database')->user;
		$password = Config::get('database')->password;
		$host = '127.0.0.1';
		$dbName = '';
		$explodedDsn = explode(';', Config::get('database')->dsn);
		foreach ($explodedDsn as $part) {
			if (str_contains($part, 'host')) {
				$hostPart = explode('=', $part);
				$host = $hostPart[1];
			}
			if (str_contains($part, 'dbname')) {
				$dbNamePart = explode('=', $part);
				$dbName = $dbNamePart[1];
			}
		}
		file_put_contents($configPath, "[client]\n", FILE_APPEND);
		file_put_contents($configPath, "user = $user\n", FILE_APPEND);
		file_put_contents($configPath, "password = $password\n", FILE_APPEND);
		file_put_contents($configPath, "host = $host\n", FILE_APPEND);
		$restoreDbReferencesData = 'mysql --defaults-extra-file=' . $configPath . ' ' . $dbName . ' < "' . realpath($filename) . '"';
		exec($restoreDbReferencesData, $output, $resultCode);
		unlink($configPath);
	}

	final public function __clone(): void
	{
	}
}
