<?php
namespace co0lc0der\QueryBuilder;

use PDO;

/**
 * class QueryBuilder
 */
class QueryBuilder
{
	private const OPERATORS = ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
	private const LOGICS = ['AND', 'OR', 'NOT'];
	private const SORT_TYPES = ['ASC', 'DESC'];
	private const JOIN_TYPES = ['INNER', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER', 'CROSS'];
	private const NO_FETCH = 0;
	private const FETCH_ONE = 1;
	private const FETCH_ALL = 2;
	private const FETCH_COLUMN = 3;
	private $pdo = null;
	private $query = null;
	private $sql = '';
	private $error = false;
	private $errorMessage = '';
	private $result = [];
	private $params = [];
	private $count = -1;

	/**
	 * @param PDO $pdo
	 */
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function addSemicolon(string $sql = ''): string
	{
		$new_sql = (empty($sql)) ? $this->sql : $sql;

		$new_sql .= (substr($new_sql, -1) != ';') ? ';' : '';

		if (empty($sql)) {
			$this->sql = $new_sql;
		}

		return $new_sql;
	}

	/**
	 * @return string
	 */
	public function getSql(): string
	{
		return $this->sql;
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * @return bool
	 */
	public function getError(): bool
	{
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function setError(string $message = ''): void
	{
		$this->error = !empty($message);
		$this->errorMessage = $message;
	}

	/**
	 * @return array
	 */
	public function getResult(): array
	{
		return $this->result;
	}

	/**
	 * @return int
	 */
	public function getCount(): int
	{
		return $this->count;
	}

	/**
	 * @return string
	 */
	public function getFirst(): string
	{
		return $this->getResult()[0];
	}

	/**
	 * @return array
	 */
	public function getLast(): array
	{
		return end($this->result);
	}

	/**
	 * @return void
	 */
	public function reset()
	{
		$this->sql = '';
		$this->params = [];
		$this->query = null;
		$this->result = [];
		$this->count = -1;
		$this->setError();
	}

	/**
	 * @return array
	 */
	public function all(): array
	{
		$this->query();
		return $this->result;
	}

	/**
	 * @return array
	 */
	public function one(): array
	{
		$this->query($this->sql, $this->params, self::FETCH_ONE);
		return $this->result;
	}

	/**
	 * @return int
	 */
	public function go(): int
	{
		$this->query($this->sql, $this->params, self::NO_FETCH);
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param array|string $table
	 * @param string $field
	 * @return $this
	 */
	public function count($table, string $field = ''): QueryBuilder
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		if (empty($field)) {
			$this->select($table, 'COUNT(*) AS `counter`');
		} else {
			$this->select($table, "COUNT(`{$field}`) AS `counter`");
		}

		return $this;
	}

	/**
	 * @return array|string
	 */
	public function column()
	{
		$this->query('', [], self::FETCH_COLUMN);

		return $this->result;
	}

	/**
	 * @param string $field
	 * @return string
	 */
	private function prepareField(string $field = ''): string
	{
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return '';
		}

		if (strpos($field, '(') !== false || strpos($field, ')') !== false || strpos($field, '*') !== false) {
			if (strpos($field, ' AS ') !== false) {
				$field = str_replace(' AS ', ' AS `', $field);
				return "{$field}`";
			} else {
				return $field;
			}
		} else{
			$field = str_replace('.', '`.`', $field);
			$field = str_replace(' AS ', '` AS `', $field);
		}

		return "`{$field}`";
	}

	/**
	 * @param string|array $fields
	 * @return string
	 */
	private function prepareFieldList($fields = ''): string
	{
		$result = '';

		if (empty($fields)) {
			$this->setError('Empty $fields in ' . __METHOD__);
			return $result;
		}

		if (is_string($fields)) {
			$result = $this->prepareField($fields);
		} elseif (is_array($fields)) {
			$new_fields = [];

			foreach ($fields as $field) {
				$new_fields[] = $this->prepareField($field);
			}

			$result = implode(', ', $new_fields);
		}

		return $result;
	}

	/**
	 * @param array|string $items
	 * @param bool $asArray
	 * @return array|string
	 */
	private function prepareAliases($items, bool $asArray = false)
	{
		if (empty($items)) {
			$this->setError('Empty $items in ' . __METHOD__);
			return '';
		}

		$sql = [];
		if (is_string($items)) {
			$sql[] = $items;
		} else if (is_array($items)) {
			foreach ($items as $alias => $item) {
				$new_item = str_replace('.', '`.`', $item);
				if (strpos($item, '(') !== false || strpos($item, ')') !== false) {
					$sql[] = is_numeric($alias) ? "{$new_item}" : "{$new_item} AS `{$alias}`";
				} else {
					$sql[] = is_numeric($alias) ? "`{$new_item}`" : "`{$new_item}` AS `{$alias}`";
				}
			}
		}

		return $asArray ? $sql : implode(', ', $sql);
	}

	/**
	 * @param array|string $where
	 * @return array
	 */
	private function prepareConditions($where): array
	{
		$result = ['sql' => '', 'values' => []];
		$sql = '';

		if (empty($where)) {
			$this->setError('Empty $where in ' . __METHOD__);
			return $result;
		}

		if (is_string($where)) {
			$sql .= $where;
		} else {
			foreach ($where as $key => $cond):
				if (is_array($cond)) {
					if (count($cond) === 3) {
						$field = str_replace('.', '`.`', $cond[0]);
						$operator = strtoupper($cond[1]);
						$value = $cond[2];
						if (!is_numeric($value) && is_string($value)) {
							$value = str_replace('.', '`.`', $value);
						}

						if (in_array($operator, self::OPERATORS)) {
							if ($operator == 'IN' && is_array($value)) {
								$values = rtrim(str_repeat("?,", count($value)), ',');
                $sql .= "(`{$field}` {$operator} ({$values}))";
                foreach ($value as $item) {
	                $result['values'][] = $item;
                }
							} else {
								if (is_numeric($value) || (is_string($value) && strpos($value, '.') === false)) {
									if (strpos($field, '(') !== false || strpos($item, ')') !== false) {
										$sql .= "({$field} {$operator} ?)";
									} else {
										$sql .= "(`{$field}` {$operator} ?)";
									}
									$result['values'][] = $value;
								} else {
									if (strpos($field, '(') !== false || strpos($item, ')') !== false) {
										$sql .= "({$field} {$operator} `{$value}`)";
									} else {
										$sql .= "(`{$field}` {$operator} `{$value}`)";
									}
								}
							}
						}
					}
				} else if (is_string($cond)) {
					$new_cond = strtoupper($cond);
					if (in_array($new_cond, self::LOGICS)) {
						$sql .= " {$new_cond} ";
					}
				}
			endforeach;
		}

		$result['sql'] = $sql;

		return $result;
	}

	/**
	 * @param string $field
	 * @param string $sort
	 * @return array
	 */
	private function prepareSorting(string $field = '', string $sort = ''): array
	{
		if (strpos($field, ' ') !== false) {
			$splitted = explode(' ', $field);
      $field = $splitted[0];
      $sort = $splitted[1];
		}

    $field = $this->prepareField($field);

		$sort =  ($sort == '') ? 'ASC' : strtoupper($sort);

    return [$field, $sort];
	}

	/**
	 * @param string $sql
	 * @param array $params
	 * @param bool $one
	 * @return $this
	 */
	public function query(string $sql = '', array $params = [], int $fetch = self::FETCH_ALL): QueryBuilder
	{
		$this->setError();

		if (!empty($sql)) {
			$this->sql = $sql;
		}

		$this->addSemicolon();

		$this->query = $this->pdo->prepare($this->sql);

		if (!empty($params)) {
			$this->params = $params;
		}

		if (!empty($this->params)) {
			$i = 1;
			foreach ($this->params as $param) {
				$this->query->bindValue($i, $param);
				$i++;
			}
		}

		if (!$this->query->execute()) {
			$this->setError('Error executing query in ' . __METHOD__);
		} else {
			if ($fetch === self::FETCH_ONE) {
				$this->result = $this->query->fetch();
			} else if ($fetch === self::FETCH_ALL) {
				$this->result = $this->query->fetchAll();
			} else if ($fetch === self::FETCH_COLUMN) {
				$this->result = $this->query->fetchColumn();
			}

			if (is_array($this->result)) {
				$this->count = count($this->result);
			}
		}

		return $this;
	}

	/**
	 * @param array|string $table
	 * @param array|string $fields
	 * @return $this
	 */
	public function select($table, $fields = '*'): QueryBuilder
	{
		if (empty($table) || empty($fields)) {
			$this->setError('Empty $table or $fields in ' . __METHOD__);
			return $this;
		}

		$this->reset();

		if (is_array($fields)) {
			$this->sql = "SELECT {$this->prepareAliases($fields)}";
		} else if (is_string($fields)) {
			$this->sql = "SELECT {$fields}";
		}

		if (is_array($table)) {
			$this->sql .= " FROM {$this->prepareAliases($table)}";
		} else if (is_string($table)) {
			$this->sql .= " FROM `{$table}`";
		}

		return $this;
	}

	/**
	 * @param array|string $where
	 * @param string $addition
	 * @return $this
	 */
	public function where($where, string $addition = ''): QueryBuilder
	{
		if (empty($where)) {
			$this->setError('Empty $where in ' . __METHOD__);
			return $this;
		}

		$conditions = $this->prepareConditions($where);

		if (!empty($addition)) {
			$this->sql .= " WHERE {$conditions['sql']} {$addition}";
		} else {
			$this->sql .= " WHERE {$conditions['sql']}";
		}

		if (!empty($conditions['values'])) {
			$this->params = array_merge($this->params, $conditions['values']);
		}

		return $this;
	}

	/**
	 * @param array|string $having
	 * @return $this
	 */
	public function having($having): QueryBuilder
	{
		if (empty($having)) {
			$this->setError('Empty $having in ' . __METHOD__);
			return $this;
		}

		$conditions = $this->prepareConditions($having);

		$this->sql .= " HAVING {$conditions['sql']}";

		if (!empty($conditions['values'])) {
			$this->params = array_merge($this->params, $conditions['values']);
		}

		return $this;
	}

	/**
	 * @param array|string $cond
	 * @return $this
	 */
	public function like($cond = []): QueryBuilder
	{
		if ($cond) {
			if (is_string($cond)) {
				$this->where($cond);
			} else if (is_array($cond)) {
				$this->where([[$cond[0], 'LIKE', $cond[1]]]);
			}
		}

		return $this;
	}

	/**
	 * @param array|string $cond
	 * @return $this
	 */
	public function notLike($cond = []): QueryBuilder
	{
		if ($cond) {
			if (is_string($cond)) {
				$this->where($cond);
			} else if (is_array($cond)) {
				$this->where([[$cond[0], 'NOT LIKE', $cond[1]]]);
			}
		}

		return $this;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit(int $limit = 1): QueryBuilder
	{
		$this->sql .= " LIMIT {$limit}";
		return $this;
	}

	/**
	 * @param int $offset
	 * @return $this
	 */
	public function offset(int $offset = 0): QueryBuilder
	{
		$this->sql .= " OFFSET {$offset}";
		return $this;
	}

	/**
	 * @param string|array $field
	 * @param string $sort
	 * @return $this
	 */
	public function orderBy($field = '', string $sort = ''): QueryBuilder
	{
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

		if (is_string($field)) {
			$prepared_field = $this->prepareSorting($field, $sort);
			$field = $prepared_field[0];
			$sort = $prepared_field[1];

			if (in_array($sort, self::SORT_TYPES)) {
				$this->sql .= " ORDER BY `{$field}` {$sort}";
			} else {
				$this->sql .= " ORDER BY `{$field}`";
			}
		} elseif (is_array($field)) {
			$new_list = [];

			foreach ($field as $item) {
				$new_item = $this->prepareSorting($item);
				$new_list[] = "{$new_item[0]} {$new_item[1]}";
			}

			$this->sql .= ' ORDER BY ' . implode(', ', $new_list);
		}

		return $this;
	}

	/**
	 * @param string|array $field
	 * @return $this
	 */
	public function groupBy($field = ''): QueryBuilder
	{
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

		$this->sql .= " GROUP BY {$this->prepareFieldList($field)}";

		return $this;
	}

	/**
	 * @param array|string $table
	 * @return $this
	 */
	public function delete($table): QueryBuilder
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		if (is_array($table)) {
			$table = "`{$this->prepareAliases($table)}`";
		} else if (is_string($table)) {
			$table = "`{$table}`";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be String or Array.');
			return $this;
		}

		$this->reset();

		$this->sql = "DELETE FROM {$table}";

		return $this;
	}

	/**
	 * @param array|string $table
	 * @param array $fields
	 * @return $this
	 */
	public function insert($table, array $fields = []): QueryBuilder
	{
		if (empty($table) || empty($fields)) {
			$this->setError('Empty $table or $fields in ' . __METHOD__);
			return $this;
		}

		if (is_array($table)) {
			$table = "`{$this->prepareAliases($table)}`";
		} else if (is_string($table)) {
			$table = "`{$table}`";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be String or Array.');
			return $this;
		}

		$this->reset();

		if (isset($fields[0]) && !is_array($fields[0])) {
			$values = rtrim(str_repeat("?,", count($fields)), ',');
			$this->sql = "INSERT INTO {$table} (`" . implode('`, `', array_keys($fields)) . "`) VALUES ({$values})";
			$this->params = array_values($fields);
		} else {
			$names = array_shift($fields);
			$value = rtrim(str_repeat("?,", count($names)), ',');
			$values = rtrim(str_repeat("({$value}),", count($fields)), ',');
			$this->sql = "INSERT INTO {$table} (`" . implode('`, `', $names) . "`) VALUES {$values}";
			$params = [];

			foreach ($fields as $item) {
				if (is_array($item)) {
					$params = array_merge($params, $item);
				}
			}

			$this->params = $params;
		}

		return $this;
	}

	/**
	 * @param array|string $table
	 * @param array $fields
	 * @return $this
	 */
	public function update($table, array $fields = []): QueryBuilder
	{
		if (empty($table) || empty($fields)) {
			$this->setError('Empty $table or $fields in ' . __METHOD__);
			return $this;
		}

		if (is_array($table)) {
			$table = "`{$this->prepareAliases($table)}`";
		} else if (is_string($table)) {
			$table = "`{$table}`";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be String or Array.');
			return $this;
		}

		$this->reset();

		$sets = '';
		foreach ($fields as $key => $field) {
			$new_key = str_replace('.', '`.`', $key);
			$sets .= " `{$new_key}` = ?,";
		}
		$sets = rtrim($sets, ',');

		$this->sql = "UPDATE {$table} SET{$sets}";
		$this->params = array_values($fields);

		return $this;
	}

	/**
	 * @param array|string $table
	 * @param $on
	 * @param string $join_type
	 * @return $this
	 */
	public function join($table, $on, string $join_type = 'INNER'): QueryBuilder
	{
		$join_type = strtoupper($join_type);
		if (empty($join_type) || !in_array($join_type, self::JOIN_TYPES)) {
			$this->setError('Empty $join_type or is not allowed in ' . __METHOD__);
			return $this;
		}

		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		if (is_array($table)) {
			$this->sql .= " {$join_type} JOIN {$this->prepareAliases($table)}";
		} else if (is_string($table)) {
			$this->sql .= " {$join_type} JOIN `{$table}`";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be String or Array.');
			return $this;
		}

		if ($on) {
			if (is_array($on)) {
				$field1 = str_replace('.', '`.`', $on[0]);
				$field1 = "`{$field1}`";
				$field2 = str_replace('.', '`.`', $on[1]);
				$field2 = "`{$field2}`";
				$this->sql .= " ON {$field1} = {$field2}";
			} else if (is_string($on)) {
				$this->sql .= " ON {$on}";
			}
		}

		$this->setError();

		return $this;
	}

	/**
	 * @param string $table
	 * @param bool $add_exists
	 * @return $this
	 */
	public function drop(string $table, bool $add_exists = true): QueryBuilder
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		$exists = ($add_exists) ? 'IF EXISTS ' : '';

		$this->reset();
		$this->sql = "DROP TABLE {$exists}`{$table}`";

		return $this;
	}

	/**
	 * @param string $table
	 * @return $this
	 */
	public function truncate(string $table): QueryBuilder
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		$this->reset();
		$this->sql = "TRUNCATE TABLE `{$table}`";

		return $this;
	}
}
