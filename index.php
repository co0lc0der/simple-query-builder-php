<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/QueryBuilder/Connection.php';
require __DIR__ . '/QueryBuilder/QueryBuilder.php';
$query = new QueryBuilder(Connection::make($config['database']));

$query->getAll('groups');

$query->get('users', [['id', '>', 1]]);

$query->get('users', [
  ['id', '>', 1],
  'and',
  ['group_id', '=', 2],
]);

$query->getFields('posts', ['id', 'category', 'title'], [['views', '>=', 1000]], 'GROUP BY `category`');

$query->getFields('users', ['id', 'username', 'email'], [['id', '>', 2]]);

$query->getFields('users', ['count' => 'COUNT(id)']);

$query->join(
  ['u' => 'users', 'groups'],
  ['u.id', 'u.email', 'u.username', 'perms' => 'groups.permissions'],
  ['u.group_id', '=', 'groups.id']
);

$query->insert('groups', [
  'name' => 'Moderator',
  'permissions' => 'moderator'
]);

$query->update('users', 7, [
  'username' => 'John Doe',
  'status' => 'new status'
], 'LIMIT 1');

$query->delete('users', [['id', '=', 10]]);
