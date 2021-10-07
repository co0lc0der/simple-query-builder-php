<?php

class Connection
{
	public static function make($config) {
		try {
			if ($config['driver'] == 'sqlite') {
				return new PDO("sqlite:{$config['dbname']}", $config['username'], $config['password'], [
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => $config['fetchmode']
				]);
			} else if ($config['driver'] == 'memory') {
				return new PDO('sqlite::memory:', null, null, [
					PDO::ATTR_PERSISTENT => true,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => $config['fetchmode']
				]);
			} else if ($config['driver'] == 'mysql') {
				return new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => $config['fetchmode']
				]);
			}
		} catch (PDOException $exception) {
			die($exception->getMessage());
		}
		return null;
	}
}
