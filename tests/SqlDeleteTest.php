<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';


/**
 * class SqlDeleteTest
 */
class SqlDeleteTest extends PHPUnit\Framework\TestCase
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

	public function testDeleteEmptyTable()
	{
		$result = $this->qb->delete('');

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table in QueryBuilder::delete');
	}

	public function testDeleteTableAsString()
	{
        $result = $this->qb->delete('groups');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DELETE FROM `groups`", $result->getSql());
	}

	public function testDeleteTableAsArray()
	{
		$result = $this->qb->delete(['g' => 'groups']);

        $this->assertSame(false, $result->hasError());
		$this->assertSame("DELETE FROM `groups` AS `g`", $result->getSql());
	}

    public function testDeleteEq()
    {
        $result = $this->qb->delete('comments')->where([['user_id', '=', 10]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DELETE FROM `comments` WHERE (`user_id` = 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
    }

    public function testDeleteNoEq()
    {
        $result = $this->qb->delete('comments')->where([['user_id', 10]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DELETE FROM `comments` WHERE (`user_id` = 10)", $result->getSql());
        $this->assertSame([10], $result->getParams());
    }

    public function testDeleteLimitEq()
    {
        $result = $this->qb->delete('users')->where([['name', '=', 'John']])->limit();

        $this->assertSame(false, $result->hasError());
        if ($this->qb->getDriver() == 'sqlite') {
            $this->assertSame("DELETE FROM `users` WHERE (`name` = 'John')", $result->getSql());
        } else {
            $this->assertSame("DELETE FROM `users` WHERE (`name` = 'John') LIMIT 1", $result->getSql());
        }
        $this->assertSame(['John'], $result->getParams());
    }

    public function testDeleteLimitNoEq()
    {
        $result = $this->qb->delete('users')->where([['name', 'John']])->limit();

        $this->assertSame(false, $result->hasError());
        if ($this->qb->getDriver() == 'sqlite') {
            $this->assertSame("DELETE FROM `users` WHERE (`name` = 'John')", $result->getSql());
        } else {
            $this->assertSame("DELETE FROM `users` WHERE (`name` = 'John') LIMIT 1", $result->getSql());
        }
        $this->assertSame(['John'], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
