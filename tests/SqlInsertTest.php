<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';


/**
 * class SqlInsertTest
 */
class SqlInsertTest extends PHPUnit\Framework\TestCase
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

	public function testInsertEmptyTable()
	{
		$result = $this->qb->insert('', ['param' => 'new_value']);

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::insert');
	}

	public function testInsertEmptyFields()
	{
		$result = $this->qb->insert('params', []);

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $this->qb->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::insert');
	}

	public function testInsertEmptyTableAndFields()
	{
		$result = $this->qb->insert('', []);

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::insert');
	}

	public function testInsertTableAsString()
	{
        $result = $this->qb->insert('groups', [
			'name' => 'Moderator',
			'permissions' => 'moderator'
		]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("INSERT INTO `groups` (`name`, `permissions`) VALUES ('Moderator','moderator')", $result->getSql());
        $this->assertSame(['Moderator', 'moderator'], $result->getParams());
	}

	public function testInsertTableAsArray()
	{
		$result = $this->qb->insert(['g' => 'groups'], [
			'name' => 'Moderator',
			'permissions' => 'moderator'
		]);

        $this->assertSame(false, $result->hasError());
		$this->assertSame("INSERT INTO `groups` AS `g` (`name`, `permissions`) VALUES ('Moderator','moderator')", $result->getSql());
        $this->assertSame(['Moderator', 'moderator'], $result->getParams());
	}

    public function testInsertMultipleTableAsString()
    {
        $result = $this->qb->insert('groups', [
            ['name', 'role'],
            ['Moderator', 'moderator'], ['Moderator2', 'moderator'],
            ['User', 'user'], ['User2', 'user']
        ]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("INSERT INTO `groups` (`name`, `role`) VALUES ('Moderator','moderator'),('Moderator2','moderator'),('User','user'),('User2','user')", $result->getSql());
        $this->assertSame(['Moderator', 'moderator', 'Moderator2', 'moderator', 'User', 'user', 'User2', 'user'], $result->getParams());
    }

    public function testInsertMultipleTableAsArray()
    {
        $result = $this->qb->insert(['g' => 'groups'], [
            ['name', 'role'],
            ['Moderator', 'moderator'], ['Moderator2', 'moderator'],
            ['User', 'user'], ['User2', 'user']
        ]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("INSERT INTO `groups` AS `g` (`name`, `role`) VALUES ('Moderator','moderator'),('Moderator2','moderator'),('User','user'),('User2','user')", $result->getSql());
        $this->assertSame(['Moderator', 'moderator', 'Moderator2', 'moderator', 'User', 'user', 'User2', 'user'], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
