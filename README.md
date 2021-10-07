# QueryBuilder php component

This is a small easy-to-use php component for working with a database by PDO. It provides some public methods to manipulate data. Each SQL query is prepared and safe. PDO fetches data to arrays. See `example/example.php` for examples.
### Public methods:
- `getResults()` returns query's results
- `getCount()` returns results' rows count
- `getError()` returns true if an error is
- `getFirst()` returns the fist item of results
- `getLast()` returns the last item of results
- other methods are presented in Usage section
## How to use
### 1. Edit `config.php` and set the parameters up. Choose DB driver, DB name etc. At present time the component supports MySQL and SQLite (file or memory).
```php
$config = require_once __DIR__ . '/config.php';
```
### 2. Load `Connection` and `QueryBuilder` classes.
```php
require_once __DIR__ . '/QueryBuilder/src/Connection.php';
require_once __DIR__ . '/QueryBuilder/src/QueryBuilder.php';
```
or using PSR-4 composer autoloader
```php
require_once __DIR__ . '/vendor/autoload.php';

use co0lc0der\QueryBuilder\Connection;
use co0lc0der\QueryBuilder\QueryBuilder;
```
### 3. Init QueryBuilder with `Connection::make()`.
```php
$query = new QueryBuilder(Connection::make($config['database']));
```
### 4. Usage examples.
- Select all rows
```php
$query->getAll('users');
```
```sql
SELECT * FROM `users`;
```
- Select rows with a condition
```php
$query->get('users', [['id', '=', 1]]);
```
```sql
SELECT * FROM `users` WHERE `id` = 1;
```
- Select rows with two conditions
```php
$query->get('users', [
  ['id', '>', 1],
  'and',
  ['group_id', '=', 2],
]);
```
```sql
SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2);
```
- Select custom fields with additional SQL
1.
```php
$query->getFields('users', ['count' => 'COUNT(id)']);
```
```sql
SELECT COUNT(id) AS `count` FROM `users`;
```
2.
```php
$query->getFields('posts', ['id', 'category', 'title'], [['views', '>=', 1000]], 'GROUP BY `category`');
```
```sql
SELECT id, category, title FROM `posts` WHERE (`views` >= 1000) GROUP BY `category`;
```
- Inner join
```php
$query->join(
  ['u' => 'users', 'groups'],
  ['u.id', 'u.email', 'u.username', 'perms' => 'groups.permissions'],
  ['u.group_id', '=', 'groups.id']
);
```
```sql
SELECT u.id, u.email, u.username, groups.permissions AS `perms`
FROM `users` AS `u` INNER JOIN `groups` ON u.group_id = groups.id;
```
- Insert a row
```php
$query->insert('groups', [
  'name' => 'Moderator',
  'permissions' => 'moderator'
]);
```
```sql
INSERT INTO `groups` (`name`, `permissions`) VALUES ('Moderator', 'moderator');
```
- Update a row
```php
$query->update('users', 7, [
  'username' => 'John Doe',
  'status' => 'new status'
], 'LIMIT 1');
```
```sql
UPDATE `users` SET `username` = 'John Doe', `status` = 'new status' WHERE `id` = 7 LIMIT 1;
```
- Delete a row
```php
$query->delete('users', [['id', '=', 10]]);
```
```sql
DELETE FROM `users` WHERE `id` = 10;
```
