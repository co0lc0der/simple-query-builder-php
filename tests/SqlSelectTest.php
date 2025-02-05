<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';

/**
 * class SqlSelectTest
 */
class SqlSelectTest extends PHPUnit\Framework\TestCase
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

	public function testEmptyTable()
	{
		$result = $this->qb->select('', 'param');

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::select');
	}

	public function testEmptyFields()
	{
		$result = $this->qb->select('users', []);

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($result->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::select');
	}

	public function testEmptyTableAndFields()
	{
		$result = $this->qb->select('', []);

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::select');
	}

    public function testGetSql()
    {
        $result = $this->qb->select('users')->where([['id', 1]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` = 1)", $result->getSql());
        $this->assertSame([1], $result->getParams());
    }

    public function testGetSqlNoValues()
    {
        $result = $this->qb->select('users')->where([['id', 1]]);

        $this->assertSame($this->qb, $result);
        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` = ?)", $result->getSql(false));
        $this->assertSame([1], $result->getParams());
    }

	public function testSelectAll()
	{
        $result = $this->qb->select('users');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users`", $result->getSql());
        $this->assertSame([], $result->getParams());
	}

	public function testSelectWhereEq()
	{
		$result = $this->qb->select('users')->where([['id', '=', 10]]);

        $this->assertSame(false, $result->hasError());
		$this->assertSame("SELECT * FROM `users` WHERE (`id` = 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
	}

    public function testSelectWhereNoEq()
    {
        $result = $this->qb->select('users')->where([['id', 10]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` = 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
    }

    public function testSelectWhereAndEq()
    {
        $result = $this->qb->select('users')->where([['id', '>', 1], 'and', ['group_id', '=', 2]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2)", $result->getSql());
        $this->assertSame([1, 2], $result->getParams());
    }

    public function testSelectWhereAndNoEq()
    {
        $result = $this->qb->select('users')->where([['id', '>', 1], 'and', ['group_id', 2]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2)", $result->getSql());
        $this->assertSame([1, 2], $result->getParams());
    }

    public function testSelectWhereOrEq()
    {
        $result = $this->qb->select('users')->where([['id', '>', 1], 'or', ['group_id', '=', 2]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` > 1) OR (`group_id` = 2)", $result->getSql());
        $this->assertSame([1, 2], $result->getParams());
    }

    public function testSelectWhereOrNoEq()
    {
        $result = $this->qb->select('users')->where([['id', '>', 1], 'or', ['group_id', 2]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`id` > 1) OR (`group_id` = 2)", $result->getSql());
        $this->assertSame([1, 2], $result->getParams());
    }

    public function testSelectWhereLike()
    {
        $result = $this->qb->select('users')->where([['name', 'LIKE', '%John%']]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectLikeArray()
    {
        $result = $this->qb->select('users')->like(['name', '%John%']);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectLikeStr()
    {
        $result = $this->qb->select('users')->like('name', '%John%');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectWhereNotLike()
    {
        $result = $this->qb->select('users')->where([['name', 'NOT LIKE', '%John%']]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` NOT LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectNotLikeArray()
    {
        $result = $this->qb->select('users')->notLike(['name', '%John%']);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` NOT LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectNotLikeStr()
    {
        $result = $this->qb->select('users')->notLike('name', '%John%');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`name` NOT LIKE '%John%')", $result->getSql());
        $this->assertSame(['%John%'], $result->getParams());
    }

    public function testSelectWhereIsNull()
    {
        $result = $this->qb->select('users')->where([['phone', 'is null']]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`phone` IS NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectIsNull()
    {
        $result = $this->qb->select('users')->isNull('phone');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `users` WHERE (`phone` IS NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectWhereIsNotNull()
    {
        $result = $this->qb->select('customers')->where([['address', 'is not null']]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `customers` WHERE (`address` IS NOT NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectIsNotNull()
    {
        $result = $this->qb->select('customers')->isNotNull('address');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `customers` WHERE (`address` IS NOT NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectNotNull()
    {
        $result = $this->qb->select('customers')->notNull('address');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `customers` WHERE (`address` IS NOT NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectOffset()
    {
        $result = $this->qb->select('posts')->where([['user_id', 3]])->offset(14);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `posts` WHERE (`user_id` = 3) OFFSET 14", $result->getSql());
        $this->assertSame([3], $result->getParams());
    }

    public function testSelectLimit()
    {
        $result = $this->qb->select('posts')->where([['id', '>', 42]])->limit(7);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT * FROM `posts` WHERE (`id` > 42) LIMIT 7", $result->getSql());
        $this->assertSame([42], $result->getParams());
    }

    public function testSelectCounter()
    {
        $result = $this->qb->select('users', ['counter' => 'COUNT(*)']);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT COUNT(*) AS `counter` FROM `users`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectDistinctOrderBy()
    {
        $result = $this->qb->select('customers', 'city', true)->orderBy('city');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT DISTINCT `city` FROM `customers` ORDER BY `city` ASC", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectDistinctOrderBy2Col()
    {
        $result = $this->qb->select('customers', ['city', 'country'], true)->orderBy('country desc');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT DISTINCT `city`, `country` FROM `customers` ORDER BY `country` DESC", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectOrderByTwoParams()
    {
        $result = $this->qb->select(['b' => 'branches'], ['b.id', 'b.name'])
                ->where([['b.id', '>', 1], 'and', ['b.parent_id', 1]])
                ->orderBy('b.id', 'desc');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `b`.`id`, `b`.`name` FROM `branches` AS `b` WHERE (`b`.`id` > 1) AND (`b`.`parent_id` = 1) ORDER BY `b`.`id` DESC", $result->getSql());
        $this->assertSame([1, 1], $result->getParams());
    }

    public function testSelectOrderByOneParam()
    {
        $result = $this->qb->select(['b' => 'branches'], ['b.id', 'b.name'])
            ->where([['b.id', '>', 1], 'and', ['b.parent_id', 1]])
            ->orderBy('b.id desc');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `b`.`id`, `b`.`name` FROM `branches` AS `b` WHERE (`b`.`id` > 1) AND (`b`.`parent_id` = 1) ORDER BY `b`.`id` DESC", $result->getSql());
        $this->assertSame([1, 1], $result->getParams());
    }

    public function testSelectGroupBy()
    {
        $result = $this->qb->select('posts', ['id', 'category', 'title'])
            ->where([['views', '>=', 1000]])->groupBy('category');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `id`, `category`, `title` FROM `posts` WHERE (`views` >= 1000) GROUP BY `category`", $result->getSql());
        $this->assertSame([1000], $result->getParams());
    }

    public function testSelectGroupByHavingEq()
    {
        $result = $this->qb->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
            ->where([['YEAR(`created_at`)', 2020]])->groupBy('month_num')->having([['total', '=', 20000]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT MONTH(`created_at`) AS `month_num`, SUM(`total`) AS `total` FROM `orders` WHERE (YEAR(`created_at`) = 2020) GROUP BY `month_num` HAVING (`total` = 20000)", $result->getSql());
        $this->assertSame([2020, 20000], $result->getParams());
    }

    public function testSelectGroupByHavingNoEqSum()
    {
        $result = $this->qb->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
            ->where([['YEAR(`created_at`)', 2020]])->groupBy('month_num')->having([['total', 20000]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT MONTH(`created_at`) AS `month_num`, SUM(`total`) AS `total` FROM `orders` WHERE (YEAR(`created_at`) = 2020) GROUP BY `month_num` HAVING (`total` = 20000)", $result->getSql());
        $this->assertSame([2020, 20000], $result->getParams());
    }

    public function testSelectGroupByHavingMax()
    {
        $result = $this->qb->select('employees', ['department', 'Highest salary' => 'MAX(`salary`)'])
            ->where([['favorite_website', 'Google.com']])->groupBy('department')->having([['MAX(`salary`)', '>=', 30000]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department`, MAX(`salary`) AS `Highest salary` FROM `employees` WHERE (`favorite_website` = 'Google.com') GROUP BY `department` HAVING (MAX(`salary`) >= 30000)", $result->getSql());
        $this->assertSame(['Google.com', 30000], $result->getParams());
    }

    public function testSelectGroupByHavingCount()
    {
        $result = $this->qb->select('employees', ['department', 'Number of employees' => 'COUNT(*)'])
            ->where([['state', 'Nevada']])->groupBy('department')->having([['COUNT(*)', '>', 20]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT `department`, COUNT(*) AS `Number of employees` FROM `employees` WHERE (`state` = 'Nevada') GROUP BY `department` HAVING (COUNT(*) > 20)", $result->getSql());
        $this->assertSame(['Nevada', 20], $result->getParams());
    }

    public function testSelectSumm()
    {
        $result = $this->qb->select("1+5 as 'res'");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT 1+5 as 'res'", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectSub()
    {
        $result = $this->qb->select("10 - 3 as 'res'");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT 10 - 3 as 'res'", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectSubStr()
    {
        $result = $this->qb->select("substr('Hello world!', 1, 5) as 'str'");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT substr('Hello world!', 1, 5) as 'str'", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectConcatStr()
    {
        $result = $this->qb->select("'Hello' || ' world!' as 'str'");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT 'Hello' || ' world!' as 'str'", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectSqliteVersion()
    {
        $result = $this->qb->select("sqlite_version() as ver");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT sqlite_version() as ver", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testSelectTime()
    {
        $result = $this->qb->select("strftime('%Y-%m-%d %H:%M', 'now')");

        $this->assertSame(false, $result->hasError());
        $this->assertSame("SELECT strftime('%Y-%m-%d %H:%M', 'now')", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
