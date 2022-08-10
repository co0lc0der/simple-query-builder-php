<?php
namespace co0lc0der\QueryBuilder;

use PDO;
use PDOException;

/**
 * class Connection
 */
class Connection
{
	/**
	 * @param array $config
	 * @return false|PDO
	 */
	public static function make(array $config) {
		try {
			if ($config['driver'] == 'sqlite') {
				return new PDO("sqlite:{$config['dbname']}", $config['username'], $config['password'], [
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
				]);
			} else if ($config['driver'] == 'memory') {
				return new PDO('sqlite::memory:', '', '', [
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
				]);
			} else if ($config['driver'] == 'mysql') {
				return new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
				]);
			}
		} catch (PDOException $exception) {
			die($exception->getMessage());
		}
		return false;
	}
}
