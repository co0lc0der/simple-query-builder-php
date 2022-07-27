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
	private $pdo = null;
	private $query = null;
	private $sql = '';
	private $error = false;
	private $results = [];
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
	 * @return array
	 */
	public function getResults(): array
	{
		return $this->results;
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
		return $this->getResults()[0];
	}

	/**
	 * @return false|mixed
	 */
	public function getLast()
	{
		return end($this->getResults());
	}

	/**
	 * @return void
	 */
	public function reset()
	{
		$this->sql = '';
		$this->params = [];
		$this->query = null;
		$this->results = [];
		$this->count = -1;
		$this->error = false;
	}

	/**
	 * @param array|string $items
	 * @param bool $asArray
	 * @return array|string
	 */
	private function prepareAliases($items, bool $asArray = false)
	{
		if (empty($items)) return '';

		$sql = [];
		if (is_string($items)) {
			$sql[] = $items;
		} else if (is_array($items)) {
			foreach ($items as $alias => $item) {
				$new_item = str_replace('.', '`.`', $item);
				$sql[] = is_numeric($alias) ? "`{$new_item}`" : "`{$new_item}` AS `{$alias}`";
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

		if (empty($where)) return $result;

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
									$sql .= "(`{$field}` {$operator} ?)";
									$result['values'][] = $value;
								} else {
									$sql .= "(`{$field}` {$operator} `{$value}`)";
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
	 * @param string $sql
	 * @param array $params
	 * @return $this
	 */
	public function query(string $sql, array $params = []): QueryBuilder
	{
		$this->error = false;
		$this->query = $this->pdo->prepare($sql);

		if (count($params)) {
			$i = 1;
			foreach ($params as $param) {
				$this->query->bindValue($i, $param);
				$i++;
			}
		}

		if (!$this->query->execute()) {
			$this->error = true;
		} else {
			$this->results = $this->query->fetchAll();
			$this->count = count($this->results);
		}

		return $this;
	}

	/**
	 * @param array|string $fields
	 * @param array|string $table
	 * @return $this
	 */
	public function select($fields = '*', $table = ''): QueryBuilder
	{
		if (empty($fields) || empty($table)) {
			$this->error = true;
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
		$conditions = $this->prepareConditions($where);
		if (!empty($addition)) {
			$this->sql .= " WHERE {$conditions['sql']} {$addition}";
		} else {
			$this->sql .= " WHERE {$conditions['sql']}";
		}
		$this->params = array_merge($this->params, $conditions['values']);
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
	 * @param string $field
	 * @param string $sort
	 * @return $this
	 */
	public function orderBy(string $field = '', string $sort = 'ASC'): QueryBuilder
	{
		if (empty($field) || empty($sort)) return $this;

		$sort = strtoupper($sort);
		$field = str_replace('.', '`.`', $field);

		if (in_array($sort, self::SORT_TYPES)) {
			$this->sql .= " ORDER BY `{$field}` {$sort}";
		} else {
			$this->sql .= " ORDER BY `{$field}`";
		}

		return $this;
	}

	/**
	 * @param string $field
	 * @return $this
	 */
	public function groupBy(string $field = ''): QueryBuilder
	{
		if (empty($field)) return $this;

		$field = str_replace('.', '`.`', $field);
		$this->sql .= " GROUP BY `{$field}`";

		return $this;
	}

	/**
	 * @param string $table
	 * @param array $where
	 * @param string $addition
	 * @return $this|false
	 */
	public function get(string $table, array $where = [], string $addition = '')
	{
		return $this->action('SELECT *', $table, $where, $addition);
	}

	/**
	 * @param string $table
	 * @param string $addition
	 * @return $this|false
	 */
	public function getAll(string $table, string $addition = '')
	{
		return $this->action('SELECT *', $table, [], $addition);
	}

	/**
	 * @param string $table
	 * @param array $fields
	 * @param array $where
	 * @param string $addition
	 * @return $this|false
	 */
	public function getFields(string $table, array $fields, array $where = [], string $addition = '')
	{
		if (is_array($fields)) {
			return $this->action("SELECT {$this->prepareAliases($fields)}", $table, $where, $addition);
		} else if (is_string($fields)) {
			return $this->action("SELECT {$fields}", $table, $where, $addition);
		}

		return false;
	}

	/**
	 * @param string$table
	 * @return $this
	 */
	public function delete(string $table): QueryBuilder
	{
		$this->sql = "DELETE FROM {$table}";
		return $this;
	}

	/**
	 * @param string $action
	 * @param string $table
	 * @param array $where
	 * @param string $addition
	 * @return $this|false
	 */
	public function action(string $action, string $table, array $where = [], string $addition = '')
	{
		if (empty($where)) {
			$sql = "{$action} FROM `{$table}` {$addition}";
			if (!$this->query($sql)->getError()) return $this;
		}

		$condition = $this->prepareCondition($where);

		$sql = "{$action} FROM `{$table}` WHERE {$condition['sql']} {$addition}";
		if(!$this->query($sql, $condition['values'])->getError()) return $this;

		return false;
	}

	/**
	 * @param string $table
	 * @param array $fields
	 * @return bool
	 */
	public function insert(string $table, array $fields = []): bool
	{
		$values = '';
		foreach ($fields as $field) {
			$values .= "?,";
		}
		$val = rtrim($values, ',');

		$sql = "INSERT INTO `{$table}` (" . '`' . implode('`, `', array_keys($fields)) . '`' . ") VALUES ({$val})";
		if ($this->query($sql, $fields)->getError()) return false;

		return true;
	}

	/**
	 * @param string $table
	 * @param array $fields
	 * @return $this
	 */
	public function update(string $table, array $fields = []): QueryBuilder
	{
		$sets = '';
		foreach ($fields as $key => $field) {
			$sets .= " `{$key}` = :{$key},";
		}
		$sets = rtrim($sets, ',');

		$this->sql = "UPDATE `{$table}` SET{$sets}";
		$this->params = $fields;

		return $this;
	}

	/**
	 * @param array|string $table
	 * @param array $on
	 * @param string $join_type
	 * @return $this
	 */
	public function join($table = '', $on = [], string $join_type = 'INNER')
	{
		$join_type = strtoupper($join_type);
    if (empty($join_type) || !in_array($join_type, self::JOIN_TYPES)) return $this;

		if (is_array($table)) {
			$this->sql .= " {$join_type} JOIN {$this->prepareAliases($table)}";
		} else if (is_string($table)) {
			$this->sql .= " {$join_type} JOIN `{$table}`";
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

		return $this;
	}
}
