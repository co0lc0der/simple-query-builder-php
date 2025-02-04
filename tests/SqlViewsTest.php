<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';


/**
 * class SqlViewsTest
 */
class SqlViewsTest extends PHPUnit\Framework\TestCase
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

	public function testCreateViewEmptyViewName()
	{
		$result = $this->qb->createView('');

		$this->assertSame($this->qb, $result);
		$this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $viewName in QueryBuilder::createView');
	}

	public function testCreateViewNoSelect()
	{
        $result = $this->qb->delete('comments')->where([['user_id', 10]])
                ->createView('users_no_email');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'No SELECT found in QueryBuilder::createView');
	}

	public function testCreateView()
	{
		$result = $this->qb->select('users')->where([['email', 'is null'], 'or', ['email', '']])
                ->createView('users_no_email');

        $this->assertSame(false, $result->hasError());
		$this->assertSame("CREATE VIEW IF NOT EXISTS `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL) OR (`email` = '')", $result->getSql());
        $this->assertSame([''], $result->getParams());
	}

    public function testCreateViewExists()
    {
        $result = $this->qb->select('users')->isNull('email')->createView('users_no_email');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("CREATE VIEW IF NOT EXISTS `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testCreateViewNoExists()
    {
        $result = $this->qb->select('users')->isNull('email')->createView('users_no_email', false);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("CREATE VIEW `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL)", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testDropViewEmptyViewName()
    {
        $result = $this->qb->dropView('');

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $viewName in QueryBuilder::dropView');
    }

    public function testDropViewNoExists()
    {
        $result = $this->qb->dropView('users_no_email', false);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DROP VIEW `users_no_email`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    public function testDropViewExists()
    {
        $result = $this->qb->dropView('users_no_email');

        $this->assertSame(false, $result->hasError());
        $this->assertSame("DROP VIEW IF EXISTS `users_no_email`", $result->getSql());
        $this->assertSame([], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
