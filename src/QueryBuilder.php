<?php
namespace co0lc0der\QueryBuilder;

use PDO;

/**
 * class QueryBuilder
 */
class QueryBuilder
{
	private const COND_OPERATORS = ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
	private const LOGICS = ['AND', 'OR', 'NOT'];
	private const SORT_TYPES = ['ASC', 'DESC'];
	private const JOIN_TYPES = ['INNER', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER', 'CROSS'];
    private const SQLITE_JOIN_TYPES = ['INNER', 'LEFT', 'LEFT OUTER', 'CROSS'];
    private const FIELD_SPEC_CHARS = ['+', '-', '*', '/', '%', '(', ')', '||'];
	private const NO_FETCH = 0;
	private const FETCH_ONE = 1;
	private const FETCH_ALL = 2;
	private const FETCH_COLUMN = 3;
	private PDO $pdo;
	private $query = null;
	private string $sql = '';
	private bool $error = false;
	private string $errorMessage = '';
	private bool $printErrors = false;
	private array $result = [];
	private array $params = [];
    private $fields = [];
	private int $count = -1;
    private bool $concat = false;

	/**
	 * @param PDO $pdo
	 * @param bool $printErrors
	 */
	public function __construct(PDO $pdo, bool $printErrors = false)
	{
		$this->pdo = $pdo;
		$this->printErrors = $printErrors;
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
     * @param bool $withValues
     * @return string
     */
	public function getSql(bool $withValues = true): string
	{
		$sql = $this->sql;
        $params = $this->params;

		if ($params && $withValues) {
			foreach ($params as $param) {
				if (is_string($param)) {
                    $sql = implode("'{$param}'", explode('?', $sql, 2));
				} else {
                    $sql = implode($param, explode('?', $sql, 2));
				}
			}
		}

		return $sql;
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
	public function hasError(): bool
	{
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage(): string
	{
		if ($this->printErrors && $this->error) {
			echo $this->errorMessage;
		}

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

		if ($this->printErrors && $this->error) {
			echo $this->errorMessage;
		}
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
	 * @return bool
	 */
	public function reset(): bool
	{
		$this->sql = '';
		$this->params = [];
        $this->fields = [];
		$this->query = null;
		$this->result = [];
		$this->count = -1;
		$this->setError();
        $this->concat = false;

		return true;
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
		$this->query($this->sql, $this->params, 0, self::FETCH_ONE);
		return $this->result;
	}

	/**
	 * @return int
	 */
	public function go(): int
	{
		$this->query($this->sql, $this->params, 0, self::NO_FETCH);
		return $this->pdo->lastInsertId();
	}

	/**
	 * @param array|string $table
	 * @param string $field
	 * @return string|$this
	 */
	public function count($table, string $field = '')
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		if (is_array($table) || is_string($table)) {
			$this->select($table, (empty($field) ? 'COUNT(*) AS counter' : "COUNT({$field}) AS counter"));
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array');
			return $this;
		}

		return $this->column();
	}

	/**
	 * @param string $column
	 * @return $this|string|array
	 */
	public function column(string $column = 'id')
	{
		if (!is_string($column)) {
			$this->setError('Incorrect type of $column in ' . __METHOD__);
			return $this;
		}

		if (empty($column)) {
			$this->setError('Empty $column in ' . __METHOD__);
			return $this;
		}

		$this->query();
		return array_column($this->result, $column);
		//$this->query('', [], $column, self::FETCH_COLUMN);
		//return $this->result;
	}

	/**
	 * @return bool
	 */
	public function exists(): bool
	{
		$result = $this->one();
		return $this->count > 0;
	}

	/**
	 * @param string $key
	 * @param string $column
	 * @return array|QueryBuilder
	 */
	public function pluck(string $key = 'id', string $column = '')
	{
		if (!(is_string($key) and is_string($column))) {
			$this->setError('Incorrect type of $key or $column in ' . __METHOD__);
			return $this;
		}

		if (empty($column)) {
			$this->setError('Empty $column in ' . __METHOD__);
			return $this;
		}

		$this->query();
		return array_column($this->result, $column, $key);
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

		$field = trim(str_replace(' as ', ' AS ', $field));

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
			if (strpos($items, ',') !== false) {
				if (strpos($items, ' as ') === false && strpos($items, ' AS ') === false) {
					$items = str_replace(' ', '', $items);
				}

				if ($new_items = explode(',', $items)) {
					$sql = $new_items;
				}
			} else {
				$sql[] = $items;
			}
		} else if (is_array($items)) {
			foreach ($items as $alias => $item) {
				$sql[] = is_numeric($alias) ? $item : "{$item} AS {$alias}";
			}
		} else {
			$this->setError('Incorrect type of items in ' . __METHOD__ . '. $items must be a string or an array');
			return '';
		}

		return $asArray ? $sql : $this->prepareFieldList($sql);
	}

	/**
	 * @param array|string $where
	 * @return array
	 */
	private function prepareConditions($where): array
	{
		$result = [
			'sql' => '',
			'values' => [],
			];
		$sql = '';

		if (empty($where)) {
			$this->setError('Empty $where in ' . __METHOD__);
			return $result;
		}

		if (is_string($where)) {
			$sql .= $where;
		} else if (is_array($where)) {
			foreach ($where as $key => $cond):
				if (is_array($cond)) {
					if (count($cond) === 2) {
						$field = $this->prepareField($cond[0]);
						$value = $cond[1];

						if (is_string($value) && strtolower($value) == 'is null') {
							$operator = 'IS NULL';
							$sql .= "({$field} {$operator})";
						} else if (is_string($value) && strtolower($value) == 'is not null') {
							$operator = 'IS NOT NULL';
							$sql .= "({$field} {$operator})";
						} else if (is_array($value)) {
							$operator = 'IN';
							$values = rtrim(str_repeat("?,", count($value)), ',');
							$sql .= "({$field} {$operator} ({$values}))";

							foreach ($value as $item) {
								$result['values'][] = $item;
							}
						} else {
							$operator = '=';
							$sql .= "({$field} {$operator} ?)";
							$result['values'][] = $value;
						}
					} else if (count($cond) === 3) {
						$field = $this->prepareField($cond[0]);
						$operator = strtoupper($cond[1]);
						$value = $cond[2];

						if (in_array($operator, self::COND_OPERATORS)) {
							if ($operator == 'IN' && is_array($value)) {
								$values = rtrim(str_repeat("?,", count($value)), ',');
                $sql .= "({$field} {$operator} ({$values}))";

								foreach ($value as $item) {
	                $result['values'][] = $item;
                }
							} else {
								if (is_numeric($value) || (is_string($value) && strpos($value, '.') === false)) {
									$sql .= "({$field} {$operator} ?)";
									$result['values'][] = $value;
								} else {
									$sql .= "({$field} {$operator} {$value})";
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
		} else {
			$this->setError('Incorrect type of $where in ' . __METHOD__);
			return $result;
		}

		$result['sql'] = $sql;

		return $result;
	}

    /**
     * @param string $str
     * @return bool
     */
    private function searchForSpecChars(string $str): bool
    {
        foreach (self::FIELD_SPEC_CHARS as $char) {
            if (strpos($char, $str) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $table
     * @return string
     */
    private function prepareTables($table): string
    {
        if (empty($table)) {
            $this->setError('Empty $table in ' . __METHOD__);
            return '';
        }

        if (is_string($table) && (mb_strpos(mb_strtolower($table), 'select') !== false)) {
            $this->concat = true;
            return "({$table})";
        } elseif (is_string($table) && $this->searchForSpecChars($table)) {
            return $table;
        }

        return $this->prepareAliases($table);
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
	 * @param int|string $column
	 * @param int $fetch
	 * @return $this
	 */
	public function query(string $sql = '', array $params = [], $column = 0, int $fetch = self::FETCH_ALL): QueryBuilder
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
				$this->result = $this->query->fetchColumn($column);
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
	public function select($table, $fields = '*', $dist = false): QueryBuilder
	{
		if (empty($table) || empty($fields)) {
			$this->setError('Empty $table or $fields in ' . __METHOD__);
			return $this;
		}

		$this->reset();

        $this->sql = "SELECT ";
        $this->sql .= $dist ? "DISTINCT " : '';

		if (is_array($fields) || is_string($fields)) {
			$this->sql .= $this->prepareAliases($fields);
		} else {
			$this->setError('Incorrect type of $fields in ' . __METHOD__ . '. $fields must be a string or an array');
			return $this;
		}

		if (is_array($table) || is_string($table)) {
			$this->sql .= " FROM {$this->prepareAliases($table)}";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array');
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
	 * @param array|string $field
	 * @param string $value
	 * @return $this
	 */
	public function like($field, string $value = ''): QueryBuilder
	{
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

    if (is_string($field) && !empty($field) && is_string($value) && !empty($value)) {
	    $this->where([[$field, 'LIKE', $value]]);
    } else if (is_string($field) && empty($value)) {
			$this->where($field);
		} else if (is_array($field)) {
			$this->where([[$field[0], 'LIKE', $field[1]]]);
		}

		return $this;
	}

	/**
	 * @param array|string $field
	 * @param string $value
	 * @return $this
	 */
	public function notLike($field, string $value = ''): QueryBuilder
	{
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

		if (is_string($field) && !empty($field) && is_string($value) && !empty($value)) {
			$this->where([[$field, 'NOT LIKE', $value]]);
		} else if (is_string($field) && empty($value)) {
			$this->where($field);
		} else if (is_array($field)) {
			$this->where([[$field[0], 'NOT LIKE', $field[1]]]);
		}

		return $this;
	}

	/**
	 * @param string $field
	 * @return $this
	 */
	public function isNull(string $field): QueryBuilder
    {
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

		$this->where([[$field, 'IS NULL']]);
		return $this;
	}

	/**
	 * @param string $field
	 * @return $this
	 */
	public function isNotNull(string $field): QueryBuilder
    {
		if (empty($field)) {
			$this->setError('Empty $field in ' . __METHOD__);
			return $this;
		}

		$this->where([[$field, 'IS NOT NULL']]);
		return $this;
	}

	/**
	 * @param string $field
	 * @return $this
	 */
	public function notNull(string $field): QueryBuilder
    {
		$this->isNotNull($field);
		return $this;
	}

	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit(int $limit = 1): QueryBuilder
	{
        if (mb_strpos(mb_strtolower($this->sql), 'delete') !== false && $this->getDriver() == 'sqlite') {
            return $this;
        }

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
				$this->sql .= " ORDER BY {$field} {$sort}";
			} else {
				$this->sql .= " ORDER BY {$field}";
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

		if (is_array($table) || is_string($table)) {
			$table = $this->prepareTables($table);
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array.');
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

		if (is_array($table) || is_string($table)) {
            $table = $this->prepareTables($table);
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array.');
			return $this;
		}

		$this->reset();
        $this->fields = $fields;

		if (isset($fields[0]) && is_array($fields[0])) {
			$names = array_shift($fields);
			$value = rtrim(str_repeat("?,", count($names)), ',');
			$values = rtrim(str_repeat("({$value}),", count($fields)), ',');
			$this->sql = "INSERT INTO {$table} (" . $this->prepareFieldList($names) . ") VALUES {$values}";

			$params = [];
			foreach ($fields as $item) {
				if (is_array($item)) {
					$params = array_merge($params, $item);
				}
			}

			$this->params = $params;
		} else {
			$values = rtrim(str_repeat("?,", count($fields)), ',');
			$this->sql = "INSERT INTO {$table} (" . $this->prepareFieldList(array_keys($fields)) . ") VALUES ({$values})";
			$this->params = array_values($fields);
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

        $this->fields = $fields;

		if (is_array($table) or is_string($table)) {
			$table = $this->prepareAliases($table);
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array.');
			return $this;
		}

		$this->reset();

		$sets = '';
		foreach ($fields as $key => $field) {
			$sets .= " {$this->prepareField($key)} = ?,";
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

		if (is_array($table) || is_string($table)) {
			$this->sql .= " {$join_type} JOIN {$this->prepareAliases($table)}";
		} else {
			$this->setError('Incorrect type of $table in ' . __METHOD__ . '. $table must be a string or an array.');
			return $this;
		}

		if ($on) {
			if (is_array($on)) {
				$this->sql .= " ON {$this->prepareField($on[0])} = {$this->prepareField($on[1])}";
			} else if (is_string($on)) {
				$this->sql .= " ON {$on}";
			} else {
				$this->setError('Incorrect type of $on in ' . __METHOD__ . '. $on must be a string or an array.');
				return $this;
			}
		}

		$this->setError();

		return $this;
	}

    /**
     * @param bool $unionAll
     * @return $this
     */
    public function union(bool $unionAll = false): QueryBuilder
    {
        $this->concat = true;
        $this->sql .= $unionAll ? ' UNION ALL ' : ' UNION ';
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSql();
    }

    /**
     * @param string $viewName
     * @param bool $addExists
     * @return $this
     */
    public function createView(string $viewName, bool $addExists = true): QueryBuilder
    {
        // this method will be moved to another class
        if (empty($viewName)) {
            $this->setError('Empty $viewName in ' . __METHOD__);
            return $this;
        }

        $exists = $addExists ? "IF NOT EXISTS " : "";

        if (mb_strpos(mb_strtolower($this->sql), 'select') === false) {
            $this->setError('No SELECT found in ' . __METHOD__);
            return $this;
        }

        $this->sql = "CREATE VIEW {$exists}`{$viewName}` AS " . $this->sql;

        return $this;
    }

    /**
     * @param string $viewName
     * @param bool $addExists
     * @return $this
     */
    public function dropView(string $viewName, bool $addExists = true): QueryBuilder
    {
        // this method will be moved to another class
        if (empty($viewName)) {
            $this->setError('Empty $viewName in ' . __METHOD__);
            return $this;
        }

        $exists = $addExists ? "IF EXISTS " : "";

        $this->reset();
        $this->sql = "DROP VIEW {$exists}`{$viewName}`";

        return $this;
    }

	/**
	 * @param string $table
	 * @param bool $addExists
	 * @return $this
	 */
	public function drop(string $table, bool $addExists = true): QueryBuilder
	{
		if (empty($table)) {
			$this->setError('Empty $table in ' . __METHOD__);
			return $this;
		}

		$exists = ($addExists) ? 'IF EXISTS ' : '';

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

    public function getDriver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
