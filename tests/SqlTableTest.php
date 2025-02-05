<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';


/**
 * class SqlTableTest
 */
class SqlTableTest extends PHPUnit\Framework\TestCase
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

	public function testDropTableEmptyName()
	{
		$result = $this->qb->drop('');

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table in QueryBuilder::drop');
	}

    public function testDropTableExists()
    {
        $result = $this->qb->drop('temporary');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DROP TABLE IF EXISTS `temporary`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testDropTableNoExists()
    {
        $result = $this->qb->drop('temporary', false);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DROP TABLE `temporary`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testTruncateTableEmptyName()
    {
        $result = $this->qb->truncate('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table in QueryBuilder::truncate');
    }

    public function testTruncateTable()
    {
        $result = $this->qb->truncate('users');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("TRUNCATE TABLE `users`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
