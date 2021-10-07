<?php

class QueryBuilder
{
	private const OPERATORS = ['=', '>', '<', '>=', '<=', '!='];
	private const LOGICS = ['AND', 'OR'];
	private $pdo, $query, $error = false, $results, $count;

	public function __construct(PDO $pdo) {
		$this->pdo = $pdo;
	}

	public function error()	{
		return $this->error;
	}

	public function results()	{
		return $this->results;
	}

	public function count()	{
		return $this->count;
	}

	public function first() {
		return $this->results()[0];
	}

	public function last() {
		return end($this->results());
	}

	private function prepareAliases($list, $asArray = false) {
		if (empty($list)) return false;

		$sql = [];
		foreach($list as $alias => $item) {
			$sql[] = (is_numeric($alias)) ? "{$item}" : "{$item} AS `{$alias}`";
		}

		return $asArray ? $sql : implode(', ', $sql);
	}

	private function prepareCondition($where) {
		if (empty($where)) return false;

		$result = [];
		$sql = '';

		foreach($where as $key => $cond):
			if (is_array($cond)) {
				if (count($cond) === 3) {
					$field = $cond[0];
					$operator = $cond[1];
					$value = $cond[2];

					if (in_array($operator, self::OPERATORS)) {
						$sql .= "(`{$field}` {$operator} :{$field})";
						$result['values'][$field] = $value;
					}
				}
			} else {
				if (in_array(strtoupper($cond), self::LOGICS)) {
					$sql .= ' ' . strtoupper($cond) . ' ';
				}
			}
		endforeach;
		$result['sql'] = $sql;

		return $result;
	}

	public function query($sql, $params = []) {
		$this->error = false;
		$this->query = $this->pdo->prepare($sql);

		if(count($params)) {
			$i = 1;
			foreach($params as $param) {
				$this->query->bindValue($i, $param);
				$i++;
			}
		}

		if(!$this->query->execute()) {
			$this->error = true;
		} else {
			$this->results = $this->query->fetchAll();
			$this->count = count($this->results);
		}

		return $this;
	}

	public function get($table, $where = [], $addition = '') {
		return $this->action('SELECT *', $table, $where, $addition);
	}

	public function getAll($table, $addition = '') {
		return $this->action('SELECT *', $table, $addition);
	}

	public function getFields($table, $fields, $where = [], $addition = '') {
		if (is_array($fields)) {
			return $this->action("SELECT {$this->prepareAliases($fields)}", $table, $where, $addition);
		} else if (is_string($fields)) {
			return $this->action("SELECT {$fields}", $table, $where, $addition);
		}

		return null;
	}

	public function delete($table, $where = [], $addition = '') {
		return $this->action('DELETE', $table, $where, $addition);
	}

	public function action($action, $table, $where = [], $addition = '') {
		if (empty($where)) {
			$sql = "{$action} FROM `{$table}` {$addition}";
			if (!$this->query($sql)->error()) return $this;
		}

		$condition = $this->prepareCondition($where);

		$sql = "{$action} FROM `{$table}` WHERE {$condition['sql']} {$addition}";
		if(!$this->query($sql, $condition['values'])->error()) return $this;
	}

	public function insert($table, $fields = []) {
		$values = '';
		foreach ($fields as $field) {
			$values .= "?,";
		}
		$val = rtrim($values, ',');

		$sql = "INSERT INTO `{$table}` (" . '`' . implode('`, `', array_keys($fields)) . '`' . ") VALUES ({$val})";
		if ($this->query($sql, $fields)->error()) return false;

		return true;
	}

	public function update($table, $id, $fields = [], $addition = '') {
		$set = '';
		foreach ($fields as $key => $field) {
			$set .= "`{$key}` = ?,"; // username = ?, password = ?,
		}
		$set = rtrim($set, ','); // username = ?, password = ?

		$sql = "UPDATE `{$table}` SET {$set} WHERE `id` = {$id} {$addition}";
		if ($this->query($sql, $fields)->error()) return false;

		return true;
	}

	public function join($tables, $fields, $on) {
		if (count($tables) !== 2 || count($on) !== 3) return false;

		$field1 = $on[0];
		$operator = $on[1];
		$field2 = $on[2];

		if (!in_array($operator, self::OPERATORS)) return false;

		$sql_tables = $this->prepareAliases($tables, true);
		$sql = "SELECT {$this->prepareAliases($fields)} FROM {$sql_tables[0]} INNER JOIN {$sql_tables[1]} ON {$field1} {$operator} {$field2}";
		if(!$this->query($sql)->error()) return $this;
	}
}
