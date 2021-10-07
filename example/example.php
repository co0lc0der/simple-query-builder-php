<?php
$config = require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use co0lc0der\QueryBuilder\Connection;
use co0lc0der\QueryBuilder\QueryBuilder;

$query = new QueryBuilder(Connection::make($config['database']));

// ------------------------

$groups = $query->getAll('groups');
$count = $groups->getCount();
foreach ($groups->getResults() as $group) {
	echo $group['name'];
}

// ------------------------

$results = $query->get('users', [['id', '>', 1]])->getResults();
var_dump($results);
// ------------------------

$query->get('users', [
  ['id', '>', 1],
  'and',
  ['group_id', '=', 2],
]);

// ------------------------

$query->getFields('posts', ['id', 'category', 'title'], [['views', '>=', 1000]], 'GROUP BY `category`');

// ------------------------

$query->getFields('users', ['id', 'username', 'email'], [['id', '>', 2]]);

// ------------------------

$query->getFields('users', ['count' => 'COUNT(id)']);

// ------------------------

$query->join(
  ['u' => 'users', 'groups'],
  ['u.id', 'u.email', 'u.username', 'perms' => 'groups.permissions'],
  ['u.group_id', '=', 'groups.id']
);

// ------------------------

$query->insert('groups', [
  'name' => 'Moderator',
  'permissions' => 'moderator'
]);

// ------------------------

$query->update('users', 7, [
  'username' => 'John Doe',
  'status' => 'new status'
], 'LIMIT 1');

// ------------------------

$query->delete('users', [['id', '=', 10]]);
