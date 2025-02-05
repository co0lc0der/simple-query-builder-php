<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';

/**
 * class SqlSelectExtTest
 */
class SqlSelectExtTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var QueryBuilder|null
     */
    private $qb = null;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../example/config.php';

        if (!$this->qb) {
            $this->qb = new QueryBuilder(Connection::make($config['database']));
        }
    }

    public function testSelectInnerJoinEmptyTable()
    {
        $result = $this->qb->select('users')->join('', []);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table in QueryBuilder::join');
    }

    public function testSelectInnerJoinIncorrectTable()
    {
        $result = $this->qb->select('users')->join(2, []);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Incorrect type of $table in QueryBuilder::join. $table must be a string or an array.');
    }

    public function testSelectInnerJoinEmptyJoinType()
    {
        $result = $this->qb->select('users')->join('clients', [], '');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $join_type in QueryBuilder::join');
    }

    public function testSelectInnerJoinIncorrectJoinType()
    {
        $result = $this->qb->select('users')->join('clients', [], 'asdasd');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        if ($this->qb->getDriver() == 'sqlite') {
            $this->assertSame($result->getErrorMessage(), '$join_type is not allowed in SQLite in QueryBuilder::join');
        } else {
            $this->assertSame($result->getErrorMessage(), '$join_type is not allowed in QueryBuilder::join');
        }
    }

    public function testSelectInnerJoinIncorrectSqliteJoinType()
    {
        if ($this->qb->getDriver() == 'sqlite') {
            $result = $this->qb->select('users')->join('clients', [], 'full');

            $this->assertSame($this->qb, $result);
            $this->assertSame(true, $result->hasError());
            $this->assertSame($result->getErrorMessage(), '$join_type is not allowed in SQLite in QueryBuilder::join');
        }
    }

    public function testSelectInnerJoinIncorrectOnType()
    {
        $result = $this->qb->select('users')->join('clients', 2);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Incorrect type of $on in QueryBuilder::join. $on must be a string or an array.');
    }

    public function testSelectInnerJoinArray()
    {
        $result = $this->qb->select(['u' => 'users'], ['u.id', 'u.email', 'u.username', 'perms' => 'groups.permissions'])
                ->join('groups', ['u.group_id', 'groups.id']);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `u`.`id`, `u`.`email`, `u`.`username`, `groups`.`permissions` AS `perms` FROM `users` AS `u` INNER JOIN `groups` ON `u`.`group_id` = `groups`.`id`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectInnerJoin3StrWhere()
    {
        $result = $this->qb->select(['cp' => 'cabs_printers'], ['cp.id', 'cp.cab_id', 'cab_name' => 'cb.name',
                    'cp.printer_id', 'printer_name' => 'p.name', 'cartridge_type' => 'c.name', 'cp.comment'])
                ->join(['cb' => 'cabs'], ['cp.cab_id', 'cb.id'])
                ->join(['p' => 'printer_models'], ['cp.printer_id', 'p.id'])
                ->join(['c' => 'cartridge_types'], 'p.cartridge_id=c.id')
                ->where([['cp.cab_id', 'in', [11, 12, 13]], 'or', ['cp.cab_id', 5], 'and', ['p.id', '>', 'c.id']]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `cp`.`id`, `cp`.`cab_id`, `cb`.`name` AS `cab_name`, `cp`.`printer_id`, `p`.`name` AS `printer_name`, `c`.`name` AS `cartridge_type`, `cp`.`comment` FROM `cabs_printers` AS `cp` INNER JOIN `cabs` AS `cb` ON `cp`.`cab_id` = `cb`.`id` INNER JOIN `printer_models` AS `p` ON `cp`.`printer_id` = `p`.`id` INNER JOIN `cartridge_types` AS `c` ON p.cartridge_id=c.id WHERE (`cp`.`cab_id` IN (11,12,13)) OR (`cp`.`cab_id` = 5) AND (`p`.`id` > c.id)", $result->getSql());
        $this->assertSame([11, 12, 13, 5], $result->getParams());
    }

    public function testSelectInnerJoin3GroupByOrderBy()
    {
        $result = $this->qb->select(['cp' => 'cabs_printers'], ['cp.id', 'cp.cab_id', 'cab_name' => 'cb.name', 'cp.printer_id',
                'cartridge_id' => 'c.id', 'printer_name' => 'p.name', 'cartridge_type' => 'c.name', 'cp.comment'])
            ->join(['cb' => 'cabs'], ['cp.cab_id', 'cb.id'])
            ->join(['p' => 'printer_models'], ['cp.printer_id', 'p.id'])
            ->join(['c' => 'cartridge_types'], ['p.cartridge_id', 'c.id'])
            ->groupBy(['cp.printer_id', 'cartridge_id'])
            ->orderBy(['cp.cab_id', 'cp.printer_id desc']);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `cp`.`id`, `cp`.`cab_id`, `cb`.`name` AS `cab_name`, `cp`.`printer_id`, `c`.`id` AS `cartridge_id`, `p`.`name` AS `printer_name`, `c`.`name` AS `cartridge_type`, `cp`.`comment` FROM `cabs_printers` AS `cp` INNER JOIN `cabs` AS `cb` ON `cp`.`cab_id` = `cb`.`id` INNER JOIN `printer_models` AS `p` ON `cp`.`printer_id` = `p`.`id` INNER JOIN `cartridge_types` AS `c` ON `p`.`cartridge_id` = `c`.`id` GROUP BY `cp`.`printer_id`, `cartridge_id` ORDER BY `cp`.`cab_id` ASC, `cp`.`printer_id` DESC", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectLeftJoin()
    {
        $result = $this->qb->select('employees', ['employees.employee_id', 'employees.last_name', 'positions.title'])
            ->join('positions', ['employees.position_id', 'positions.position_id'], 'left');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `employees`.`employee_id`, `employees`.`last_name`, `positions`.`title` FROM `employees` LEFT JOIN `positions` ON `employees`.`position_id` = `positions`.`position_id`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectLeftOuterJoin()
    {
        $result = $this->qb->select(['e' => 'employees'], ['e.employee_id', 'e.last_name', 'p.title'])
            ->join(['p' => 'positions'], ['e.position_id', 'p.position_id'], 'left outer');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `e`.`employee_id`, `e`.`last_name`, `p`.`title` FROM `employees` AS `e` LEFT OUTER JOIN `positions` AS `p` ON `e`.`position_id` = `p`.`position_id`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectCrossJoin()
    {
        $result = $this->qb->select('positions')->join('departments', [], 'cross');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `positions` CROSS JOIN `departments`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

	public function testSelectUnionWhere()
	{
		$result = $this->qb->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.1'])
                ->where([['account_sum', '<', 3000]])
                ->union()
                ->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.3'])
                ->where([['account_sum', '>=', 3000]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `name`, `age`, account_sum + account_sum * 0.1 AS `total_sum` FROM `clients` WHERE (`account_sum` < 3000) UNION SELECT `name`, `age`, account_sum + account_sum * 0.3 AS `total_sum` FROM `clients` WHERE (`account_sum` >= 3000)", $result->getSql());
        $this->assertSame([3000, 3000], $result->getParams());
	}

    public function testSelectUnionAllWhere()
    {
        $result = $this->qb->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.1'])
            ->where([['account_sum', '<', 3000]])
            ->union(true)
            ->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.3'])
            ->where([['account_sum', '>=', 3000]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `name`, `age`, account_sum + account_sum * 0.1 AS `total_sum` FROM `clients` WHERE (`account_sum` < 3000) UNION ALL SELECT `name`, `age`, account_sum + account_sum * 0.3 AS `total_sum` FROM `clients` WHERE (`account_sum` >= 3000)", $result->getSql());
        $this->assertSame([3000, 3000], $result->getParams());
    }

    public function testSelectUnionAllMethodWhere()
    {
        $result = $this->qb->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.1'])
            ->where([['account_sum', '<', 3000]])
            ->unionAll()
            ->select('clients', ['name', 'age', 'total_sum' => 'account_sum + account_sum * 0.3'])
            ->where([['account_sum', '>=', 3000]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `name`, `age`, account_sum + account_sum * 0.1 AS `total_sum` FROM `clients` WHERE (`account_sum` < 3000) UNION ALL SELECT `name`, `age`, account_sum + account_sum * 0.3 AS `total_sum` FROM `clients` WHERE (`account_sum` >= 3000)", $result->getSql());
        $this->assertSame([3000, 3000], $result->getParams());
    }

	public function testSelectUnionWhereOrderBy()
	{
        $result = $this->qb->select('departments', ['department_id', 'department_name'])
            ->where([['department_id', 'in', [1, 2]]])
            ->union()
            ->select('employees', ['employee_id', 'last_name'])
            ->where([['hire_date', '2024-02-08']])->orderBy('2');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id`, `department_name` FROM `departments` WHERE (`department_id` IN (1,2)) UNION SELECT `employee_id`, `last_name` FROM `employees` WHERE (`hire_date` = '2024-02-08') ORDER BY `2` ASC", $result->getSql());
        $this->assertSame([1, 2, '2024-02-08'], $result->getParams());
	}

    public function testSelectUnionAllWhereOrderBy()
    {
        $result = $this->qb->select('departments', ['department_id', 'department_name'])
            ->where([['department_id', '>=', 10]])
            ->union(true)
            ->select('employees', ['employee_id', 'last_name'])
            ->where([['last_name', 'Rassohin']])->orderBy('2');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id`, `department_name` FROM `departments` WHERE (`department_id` >= 10) UNION ALL SELECT `employee_id`, `last_name` FROM `employees` WHERE (`last_name` = 'Rassohin') ORDER BY `2` ASC", $result->getSql());
        $this->assertSame([10, 'Rassohin'], $result->getParams());
    }

    public function testSelectUnionAllMethodWhereOrderBy()
    {
        $result = $this->qb->select('departments', ['department_id', 'department_name'])
            ->where([['department_id', '>=', 10]])
            ->unionAll()
            ->select('employees', ['employee_id', 'last_name'])
            ->where([['last_name', 'Rassohin']])->orderBy('2');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id`, `department_name` FROM `departments` WHERE (`department_id` >= 10) UNION ALL SELECT `employee_id`, `last_name` FROM `employees` WHERE (`last_name` = 'Rassohin') ORDER BY `2` ASC", $result->getSql());
        $this->assertSame([10, 'Rassohin'], $result->getParams());
    }

    public function testUnionSelectEmptyTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->unionSelect('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table in QueryBuilder::unionSelect');
    }

    public function testUnionSelectAllMethodEmptyTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->unionSelectAll('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table in QueryBuilder::unionSelectAll');
    }

    public function testUnionSelectIncorrectTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->unionSelect(2);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Incorrect type of $table in QueryBuilder::unionSelect. $table must be a string or an array');
    }

    public function testUnionSelectWhere()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->where([['id', '<', 10]])
                ->unionSelect('employees')->where([['id', 1]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `name`, `age` FROM `clients` WHERE (`id` < 10) UNION SELECT `name`, `age` FROM `employees` WHERE (`id` = 1)", $result->getSql());
        $this->assertSame([10, 1], $result->getParams());
    }

    public function testUnionAllSelectWhere()
    {
        $result = $this->qb->select('cabs', ['id', 'name'])
                ->unionSelect('printer_models', true)->where([['id', '<', 10]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `id`, `name` FROM `cabs` UNION ALL SELECT `id`, `name` FROM `printer_models` WHERE (`id` < 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
    }

    public function testUnionSelectAllMethodWhere()
    {
        $result = $this->qb->select('cabs', ['id', 'name'])
                ->unionSelectAll('printer_models')->where([['id', '<', 10]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `id`, `name` FROM `cabs` UNION ALL SELECT `id`, `name` FROM `printer_models` WHERE (`id` < 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
    }

    public function testSelectExceptsWhere()
    {
        $result = $this->qb->select('contacts', ['contact_id', 'last_name', 'first_name'])
            ->where([['contact_id', '>=', 74]])
            ->excepts()
            ->select('employees', ['employee_id', 'last_name', 'first_name'])
            ->where([['first_name', 'Sandra']]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `contact_id`, `last_name`, `first_name` FROM `contacts` WHERE (`contact_id` >= 74) EXCEPT SELECT `employee_id`, `last_name`, `first_name` FROM `employees` WHERE (`first_name` = 'Sandra')", $result->getSql());
        $this->assertSame([74, 'Sandra'], $result->getParams());
    }

    public function testSelectExceptsWhereOrderBy()
    {
        $result = $this->qb->select('suppliers', ['supplier_id', 'state'])->where([['state', 'Nevada']])
            ->excepts()
            ->select('companies', ['company_id', 'state'])->where([['company_id', '<', 2000]])->orderBy('1 desc');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `supplier_id`, `state` FROM `suppliers` WHERE (`state` = 'Nevada') EXCEPT SELECT `company_id`, `state` FROM `companies` WHERE (`company_id` < 2000) ORDER BY `1` DESC", $result->getSql());
        $this->assertSame(['Nevada', 2000], $result->getParams());
    }

    public function testExpectSelectEmptyTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->exceptSelect('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table in QueryBuilder::exceptSelect');
    }

    public function testExpectSelectIncorrectTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->exceptSelect(2);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Incorrect type of $table in QueryBuilder::exceptSelect. $table must be a string or an array');
    }

    public function testExpectSelectDoubleExpect()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->excepts()->exceptSelect('clients');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'SQL has already EXCEPT in QueryBuilder::exceptSelect');
    }

    public function testExceptSelect()
    {
        $result = $this->qb->select('departments', 'department_id')->exceptSelect('employees');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id` FROM `departments` EXCEPT SELECT `department_id` FROM `employees`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectIntersectWhere()
    {
        $result = $this->qb->select('departments', 'department_id')->where([['department_id', '>=', 25]])
            ->intersect()
            ->select('employees', 'department_id')->where([['last_name', '<>', 'Petrov']]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id` FROM `departments` WHERE (`department_id` >= 25) INTERSECT SELECT `department_id` FROM `employees` WHERE (`last_name` <> 'Petrov')", $result->getSql());
        $this->assertSame([25, 'Petrov'], $result->getParams());
    }

    public function testSelectIntersectWhere2()
    {
        $result = $this->qb->select('contacts', ['contact_id', 'last_name', 'first_name'])
            ->where([['contact_id', '>', 50]])
            ->intersect()
            ->select('customers', ['customer_id', 'last_name', 'first_name'])
            ->where([['last_name', '<>', 'Zagoskin']]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `contact_id`, `last_name`, `first_name` FROM `contacts` WHERE (`contact_id` > 50) INTERSECT SELECT `customer_id`, `last_name`, `first_name` FROM `customers` WHERE (`last_name` <> 'Zagoskin')", $result->getSql());
        $this->assertSame([50, 'Zagoskin'], $result->getParams());
    }

    public function testSelectIntersectWhereOrderBy()
    {
        $result = $this->qb->select('departments', ['department_id', 'state'])
            ->where([['department_id', '>=', 25]])
            ->intersect()
            ->select('companies', ['company_id', 'state'])
            ->like('company_name', 'G%')->orderBy('1');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id`, `state` FROM `departments` WHERE (`department_id` >= 25) INTERSECT SELECT `company_id`, `state` FROM `companies` WHERE (`company_name` LIKE 'G%') ORDER BY `1` ASC", $result->getSql());
        $this->assertSame([25, 'G%'], $result->getParams());
    }

    public function testIntersectSelectEmptyTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->intersectSelect('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table in QueryBuilder::intersectSelect');
    }

    public function testIntersectSelectIncorrectTable()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->intersectSelect(2);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Incorrect type of $table in QueryBuilder::intersectSelect. $table must be a string or an array');
    }

    public function testIntersectSelectDoubleExpect()
    {
        $result = $this->qb->select('clients', ['name', 'age'])->intersect()->intersectSelect('clients');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'SQL has already INTERSECT in QueryBuilder::intersectSelect');
    }

    public function testIntersectSelect()
    {
        $result = $this->qb->select('departments', 'department_id')->intersectSelect('employees');

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department_id` FROM `departments` INTERSECT SELECT `department_id` FROM `employees`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
