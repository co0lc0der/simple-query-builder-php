<?php
$config = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use co0lc0der\QueryBuilder\Connection;
use co0lc0der\QueryBuilder\QueryBuilder;

$query = new QueryBuilder(Connection::make($config['database']));

// ------------------------

$results = $query->select('users')->all();

// ------------------------

$results = $query->select('users')->where([['id', '=', 10]])->one();

// ------------------------

$results = $query->select('users')->where([
	['id', '>', 1],
	'and',
	['group_id', '=', 2],
])->all();

// ------------------------

$results = $query->select('users')->like(['name', '%John%'])->all();
// or
$results = $query->select('users')->where([['name', 'LIKE', '%John%']])->all();

// ------------------------

$results = $query->select('users')->notLike(['name', '%John%'])->all();
// or
$results = $query->select('users')->where([['name', 'NOT LIKE', '%John%']])->all();

// ------------------------

$results = $query->select('posts')
	->where([['user_id', '=', 3]])
	->offset(14)
	->limit(7)
	->all();

// ------------------------

$results = $query->select('users', ['counter' => 'COUNT(*)'])->one();
// or
$results = $query->count('users')->one();

// ------------------------

$results = $query->select(['b' => 'branches'], ['b.id', 'b.name'])
	->where([['b.id', '>', 1], 'and', ['b.parent_id', '=', 1]])
	->orderBy('b.id', 'desc')
	->all();

// ------------------------

$results = $query->select('posts', ['id', 'category', 'title'])
	->where([['views', '>=', 1000]])
	->groupBy('category')
	->all();

// ------------------------

$groups = $query->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
	->where([['YEAR(`created_at`)', '=', 2020]])
	->groupBy('month_num')
	->having([['total', '>', 20000]])
	->all();

// ------------------------

$results = $query->select(['u' => 'users'], [
	'u.id',
	'u.email',
	'u.username',
	'perms' => 'groups.permissions'
])
	->join('groups', ['u.group_id', 'groups.id'])
	->limit(5)
	->all();

// ------------------------

$results = $query->select(['cp' => 'cabs_printers'], [
	'cp.id',
	'cp.cab_id',
	'cab_name' => 'cb.name',
	'cp.printer_id',
	'printer_name' => 'p.name',
	'cartridge_type' => 'c.name',
	'cp.comment'
])
	->join(['cb' => 'cabs'], ['cp.cab_id', 'cb.id'])
	->join(['p' => 'printer_models'], ['cp.printer_id', 'p.id'])
	->join(['c' => 'cartridge_types'], 'p.cartridge_id=c.id')
	->where([['cp.cab_id', 'in', [11, 12, 13]], 'or', ['cp.cab_id', '=', 5], 'and', ['p.id', '>', 'c.id']])
	->all();

// ------------------------

$new_id = $query->insert('groups', [
	'name' => 'Moderator',
	'permissions' => 'moderator'
])->go();

// ------------------------

$query->insert('groups', [
	['name', 'role'],
	['Moderator', 'moderator'],
	['Moderator2', 'moderator'],
	['User', 'user'],
	['User2', 'user'],
])->go();

// ------------------------

$query->update('users', [
	'username' => 'John Doe',
	'status' => 'new status'
])
	->where([['id', '=', 7]])
	->limit()
	->go();

// ------------------------

$query->update('posts', ['status' => 'published'])
	->where([['YEAR(`updated_at`)', '>', 2020]])
	->go();

// ------------------------

$query->delete('users')
	->where([['name', '=', 'John']])
	->limit()
	->go();

// ------------------------

$query->delete('comments')
	->where([['user_id', '=', 10]])
	->go();

// ------------------------

$query->truncate('users')->go();

// ------------------------

$query->drop('temporary')->go();
