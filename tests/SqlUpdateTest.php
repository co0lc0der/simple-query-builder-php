<?php
require_once __DIR__ . '/../src/Connection.php';
require_once __DIR__ . '/../src/QueryBuilder.php';


/**
 * class SqlDeleteTest
 */
class SqlUpdateTest extends PHPUnit\Framework\TestCase
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

    public function testUpdateEmptyTable()
    {
        $result = $this->qb->update('', ['param' => 'new_value']);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::update');
    }

    public function testUpdateEmptyFields()
    {
        $result = $this->qb->update('params', []);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $this->qb->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::update');
    }

    public function testUpdateEmptyTableAndFields()
    {
        $result = $this->qb->update('', []);

        $this->assertSame($this->qb, $result);
        $this->assertSame(true, $result->hasError());
        $this->assertSame($this->qb->getErrorMessage(), 'Empty $table or $fields in QueryBuilder::update');
    }
    
	public function testUpdateTableAsString()
	{
        $result = $this->qb->update('posts', ['status' => 'published'])->where([['YEAR(`updated_at`)', '>', 2020]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("UPDATE `posts` SET `status` = 'published' WHERE (YEAR(`updated_at`) > 2020)", $result->getSql());
        $this->assertSame(['published', 2020], $result->getParams());
	}

	public function testUpdateTableAsArray()
	{
		$result = $this->qb->update(['p' => 'posts'], ['status' => 'published'])->where([['YEAR(`updated_at`)', '>', 2020]]);

        $this->assertSame(false, $result->hasError());
		$this->assertSame("UPDATE `posts` AS `p` SET `status` = 'published' WHERE (YEAR(`updated_at`) > 2020)", $result->getSql());
        $this->assertSame(['published', 2020], $result->getParams());
	}

    public function testUpdateEq()
    {
        $result = $this->qb->update('posts', ['status' => 'draft'])->where([['user_id', '=', 10]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("UPDATE `posts` SET `status` = 'draft' WHERE (`user_id` = 10)", $result->getSql());
        $this->assertSame(['draft', 10], $result->getParams());
    }

    public function testUpdateNoEq()
    {
        $result = $this->qb->update('posts', ['status' => 'draft'])->where([['user_id', 10]]);

        $this->assertSame(false, $result->hasError());
        $this->assertSame("UPDATE `posts` SET `status` = 'draft' WHERE (`user_id` = 10)", $result->getSql());
        $this->assertSame(['draft', 10], $result->getParams());
    }

    public function testUpdateLimitEq()
    {
        $result = $this->qb->update('users', ['username' => 'John Doe', 'status' => 'new status'])
            ->where([['id', '=', 7]])->limit();

        $this->assertSame(false, $result->hasError());
        $this->assertSame("UPDATE `users` SET `username` = 'John Doe', `status` = 'new status' WHERE (`id` = 7) LIMIT 1", $result->getSql());
        $this->assertSame(['John Doe', 'new status', 7], $result->getParams());
    }

    public function testUpdateLimitNoEq()
    {
        $result = $this->qb->update('users', ['username' => 'John Doe', 'status' => 'new status'])
                ->where([['id', 7]])->limit();

        $this->assertSame(false, $result->hasError());
        $this->assertSame("UPDATE `users` SET `username` = 'John Doe', `status` = 'new status' WHERE (`id` = 7) LIMIT 1", $result->getSql());
        $this->assertSame(['John Doe', 'new status', 7], $result->getParams());
    }

    protected function tearDown(): void
    {
        $this->qb = null;
    }
}
