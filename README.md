# QueryBuilder php component

[![Latest Version](https://img.shields.io/github/release/co0lc0der/simple-query-builder-php?style=flat-square)](https://github.com/co0lc0der/simple-query-builder-php/release)
![GitHub repo size](https://img.shields.io/github/repo-size/co0lc0der/simple-query-builder-php?color=orange&label=size&style=flat-square)
[![Packagist Downloads](https://img.shields.io/packagist/dt/co0lc0der/simple-query-builder?color=yellow&style=flat-square)](https://packagist.org/packages/co0lc0der/simple-query-builder)
[![GitHub license](https://img.shields.io/github/license/co0lc0der/simple-query-builder-php?style=flat-square)](https://github.com/co0lc0der/simple-query-builder-php/blob/main/LICENSE.md)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/co0lc0der/simple-query-builder?color=8993be&style=flat-square)

This is a small easy-to-use PHP component for working with a database by PDO. It provides some public methods to compose SQL queries and manipulate data. Each SQL query is prepared and safe. PDO (see `Connection` class) fetches data to _arrays_ by default. At present time the component supports MySQL and SQLite (file or memory).

**PAY ATTENTION! v0.2 and v0.3+ are incompatible.**

## Contributing

Bug reports and/or pull requests are welcome

## License

The package is available as open source under the terms of the [MIT license](https://github.com/co0lc0der/simple-query-builder-php/blob/main/LICENSE.md)

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```sh
composer require co0lc0der/simple-query-builder
```
or add
```json
"co0lc0der/simple-query-builder": "*"
```
to the `require section` of your `composer.json` file.

## How to use
### Main public methods
- `getSql()` returns SQL query string which will be executed
- `getParams()` returns an array of parameters for a query
- `getResult()` returns query's results
- `getCount()` returns results' rows count
- `hasError()` returns `true` if an error is had
- `getErrorMessage()` returns an error message if an error is had
- `setError($message)` sets `$error` to `true` and `$errorMessage`
- `getFirst()` returns the first item of results
- `getLast()` returns the last item of results
- `reset()` resets state to default values (except PDO property)
- `all()` executes SQL query and return all rows of result (`fetchAll()`)
- `one()` executes SQL query and return the first row of result (`fetch()`)
- `column($col)` executes SQL query and returns the needed column of result by its name, `col` is `'id'` by default
- `pluck($key, $col)` executes SQL query and returns an array (the key (usually ID) and the needed column of result) by their names, `key` is `id` and `col` is `''` by default
- `go()` this method is for non `SELECT` queries. it executes SQL query and return nothing (but returns the last inserted row ID for `INSERT` method)
- `count()` prepares a query with SQL `COUNT(*)` function and executes it
- `exists()` returns `true` if SQL query result has a row and `false` if it hasn't
- `query($sql, $params[], $fetch_type)` executes prepared `$sql` with `$params`. it can be used for custom queries
- 'SQL' methods are presented in [Usage section](#usage-examples)

### Edit `config.php` and set the parameters up. Choose DB driver, DB name etc
```php
$config = require_once __DIR__ . '/config.php';
```
### Use composer autoloader
```php
require_once __DIR__ . '/vendor/autoload.php';

use co0lc0der\QueryBuilder\Connection;
use co0lc0der\QueryBuilder\QueryBuilder;
```
### Init `QueryBuilder` with `Connection::make()`
```php
$query = new QueryBuilder(Connection::make($config['database'])); // $printErrors = false

// for printing errors (since 0.3.6)
$query = new QueryBuilder(Connection::make($config['database']), true)
```
### Usage examples
- Select all rows from a table
```php
$results = $query->select('users')->all();
```
```sql
SELECT * FROM `users`;
```
- Select a row with a condition
```php
$results = $query->select('users')->where([['id', '=', 10]])->one();
// or since 0.3.4
$results = $query->select('users')->where([['id', 10]])->one();
```
```sql
SELECT * FROM `users` WHERE `id` = 10;
```
- Select rows with two conditions
```php
$results = $query->select('users')->where([
  ['id', '>', 1],
  'and',
  ['group_id', '=', 2],
])->all();
// or since 0.3.4
$results = $query->select('users')->where([
  ['id', '>', 1],
  'and',
  ['group_id', 2],
])->all();
```
```sql
SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2);
```
- Select a row with a `LIKE` and `NOT LIKE` condition
```php
$results = $query->select('users')->like(['name', '%John%'])->all();
// or
$results = $query->select('users')->where([['name', 'LIKE', '%John%']])->all();
// or since 0.3.6
$results = $query->select('users')->like('name', '%John%')->all();
```
```sql
SELECT * FROM `users` WHERE (`name` LIKE '%John%');
```
```php
$results = $query->select('users')->notLike(['name', '%John%'])->all();
// or
$results = $query->select('users')->where([['name', 'NOT LIKE', '%John%']])->all();
// or since 0.3.6
$results = $query->select('users')->notLike('name', '%John%')->all();
```
```sql
SELECT * FROM `users` WHERE (`name` NOT LIKE '%John%');
```
- Select a row with a `IS NULL` and `IS NOT NULL` condition (since 0.3.5)
```php
$results = $query->select('users')->isNull('phone')->all();
# or
$results = $query->select('users')->where([['phone', 'is null']])->all();
```
```sql
SELECT * FROM `users` WHERE (`phone` IS NULL);
```
```php
$results = $query->select('customers')->isNotNull('address')->all();
# or
$results = $query->select('customers')->notNull('address')->all();
# or
$results = $query->select('customers')->where([['address', 'is not null']])->all();
```
```sql
SELECT * FROM `customers` WHERE (`address` IS NOT NULL);
```
- Select rows with `OFFSET` and `LIMIT`
```php
$results = $query->select('posts')
      ->where([['user_id', '=', 3]])
      ->offset(14)
      ->limit(7)
      ->all();
// or since 0.3.4
$results = $query->select('posts')
      ->where([['user_id', 3]])
      ->offset(14)
      ->limit(7)
      ->all();
```
```sql
SELECT * FROM `posts` WHERE (`user_id` = 3) OFFSET 14 LIMIT 7;
```
- Select custom fields with additional SQL
1. `COUNT()`
```php
$results = $query->select('users', ['counter' => 'COUNT(*)'])->one();
// or
$results = $query->count('users')->one();
```
```sql
SELECT COUNT(*) AS `counter` FROM `users`;
```
2. `ORDER BY`
```php
$results = $query->select(['b' => 'branches'], ['b.id', 'b.name'])
        ->where([['b.id', '>', 1], 'and', ['b.parent_id', '=', 1]])
        ->orderBy('b.id', 'desc')
        ->all();
// or since 0.3.4
$results = $query->select(['b' => 'branches'], ['b.id', 'b.name'])
        ->where([['b.id', '>', 1], 'and', ['b.parent_id', 1]])
        ->orderBy('b.id desc')
        ->all();
```
```sql
SELECT `b`.`id`, `b`.`name` FROM `branches` AS `b`
WHERE (`b`.`id` > 1) AND (`b`.`parent_id` = 1)
ORDER BY `b`.`id` DESC;
``` 
3. `GROUP BY` and `HAVING`
```php
$results = $query->select('posts', ['id', 'category', 'title'])
        ->where([['views', '>=', 1000]])
        ->groupBy('category')
        ->all();
```
```sql
SELECT `id`, `category`, `title` FROM `posts`
WHERE (`views` >= 1000) GROUP BY `category`;
```
```php
$groups = $query->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
        ->where([['YEAR(`created_at`)', '=', 2020]])
        ->groupBy('month_num')
        ->having([['total', '=', 20000]])
        ->all();
// or since 0.3.4
$groups = $query->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
        ->where([['YEAR(`created_at`)', 2020]])
        ->groupBy('month_num')
        ->having([['total', 20000]])
        ->all();
```
```sql
SELECT MONTH(`created_at`) AS `month_num`, SUM(`total`) AS `total`
FROM `orders` WHERE (YEAR(`created_at`) = 2020)
GROUP BY `month_num` HAVING (`total` = 20000);
```
4. `JOIN`. Supports `INNER`, `LEFT OUTER`, `RIGHT OUTER`, `FULL OUTER` and `CROSS` joins (`INNER` is by default)
```php
$results = $query->select(['u' => 'users'], [
        'u.id',
        'u.email',
        'u.username',
        'perms' => 'groups.permissions'
    ])
    ->join('groups', ['u.group_id', 'groups.id'])
    ->limit(5)
    ->all();
```
```sql
SELECT `u`.`id`, `u`.`email`, `u`.`username`, `groups`.`permissions` AS `perms`
FROM `users` AS `u`
INNER JOIN `groups` ON `u`.`group_id` = `groups`.`id`
LIMIT 5;
```
```php
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
```
```sql
SELECT `cp`.`id`, `cp`.`cab_id`, `cb`.`name` AS `cab_name`, `cp`.`printer_id`,
       `p`.`name` AS `printer_name`, `c`.`name` AS `cartridge_type`, `cp`.`comment`
FROM `cabs_printers` AS `cp`
INNER JOIN `cabs` AS `cb` ON `cp`.`cab_id` = `cb`.`id`
INNER JOIN `printer_models` AS `p` ON `cp`.`printer_id` = `p`.`id`
INNER JOIN `cartridge_types` AS `c` ON p.cartridge_id=c.id
WHERE (`cp`.`cab_id` IN (11,12,13)) OR (`cp`.`cab_id` = 5) AND (`p`.`id` > `c`.`id`)
```
```php
// or since 0.3.4
$results = $query->select(['cp' => 'cabs_printers'], [
        'cp.id',
        'cp.cab_id',
        'cab_name' => 'cb.name',
        'cp.printer_id',
        'cartridge_id' => 'c.id',
        'printer_name' => 'p.name',
        'cartridge_type' => 'c.name',
        'cp.comment'
    ])
    ->join(['cb' => 'cabs'], ['cp.cab_id', 'cb.id'])
    ->join(['p' => 'printer_models'], ['cp.printer_id', 'p.id'])
    ->join(['c' => 'cartridge_types'], ['p.cartridge_id', 'c.id'])
    ->groupBy(['cp.printer_id', 'cartridge_id'])
    ->orderBy(['cp.cab_id', 'cp.printer_id desc'])
    ->all();
```
```sql
SELECT `cp`.`id`, `cp`.`cab_id`, `cb`.`name` AS `cab_name`, `cp`.`printer_id`, `c`.`id` AS `cartridge_id`,
    `p`.`name` AS `printer_name`, `c`.`name` AS `cartridge_type`, `cp`.`comment`
FROM `cabs_printers` AS `cp`
INNER JOIN `cabs` AS `cb` ON `cp`.`cab_id` = `cb`.`id`
INNER JOIN `printer_models` AS `p` ON `cp`.`printer_id` = `p`.`id`
INNER JOIN `cartridge_types` AS `c` ON `p`.`cartridge_id` = `c`.`id`
GROUP BY `cp`.`printer_id`, `cartridge_id`
ORDER BY `cp`.`cab_id` ASC, `cp`.`printer_id` DESC;
```
- Insert a row
```php
$new_id = $query->insert('groups', [
    'name' => 'Moderator',
    'permissions' => 'moderator'
])->go();
```
```sql
INSERT INTO `groups` (`name`, `permissions`) VALUES ('Moderator', 'moderator');
```
- Insert many rows
```php
$query->insert('groups', [
	['name', 'role'],
	['Moderator', 'moderator'],
	['Moderator2', 'moderator'],
	['User', 'user'],
	['User2', 'user'],
])->go();
```
```sql
INSERT INTO `groups` (`name`, `role`)
VALUES ('Moderator', 'moderator'),
       ('Moderator2', 'moderator'),
       ('User', 'user'),
       ('User2', 'user');
```
- Update a row
```php
$query->update('users', [
            'username' => 'John Doe',
            'status' => 'new status'
        ])
        ->where([['id', '=', 7]])
        ->limit()
        ->go();
// or since 0.3.4
$query->update('users', [
            'username' => 'John Doe',
            'status' => 'new status'
        ])
        ->where([['id', 7]])
        ->limit()
        ->go();
```
```sql
UPDATE `users` SET `username` = 'John Doe', `status` = 'new status'
WHERE `id` = 7 LIMIT 1;
```
- Update rows
```php
$query->update('posts', ['status' => 'published'])
        ->where([['YEAR(`updated_at`)', '>', 2020]])
        ->go();
```
```sql
UPDATE `posts` SET `status` = 'published'
WHERE (YEAR(`updated_at`) > 2020);
```
- Delete a row
```php
$query->delete('users')
  ->where([['name', '=', 'John']])
  ->limit()
  ->go();
// or since 0.3.4
$query->delete('users')
  ->where([['name', 'John']])
  ->limit()
  ->go();
```
```sql
DELETE FROM `users` WHERE `name` = 'John' LIMIT 1;
```
- Delete rows
```php
$query->delete('comments')
  ->where([['user_id', '=', 10]])
  ->go();
// or since 0.3.4
$query->delete('comments')
  ->where([['user_id', 10]])
  ->go();
```
```sql
DELETE FROM `comments` WHERE `user_id` = 10;
```
- Truncate a table

This method will be moved to another class
```php
$query->truncate('users')->go();
```
```sql
TRUNCATE TABLE `users`;
```
- Drop a table

This method will be moved to another class
```php
$query->drop('temporary')->go(); // $add_exists = true
```
```sql
DROP TABLE IF EXISTS `temporary`;
```
```php
$query->drop('temp', false)->go(); // $add_exists = false
```
```sql
DROP TABLE `temp`;
```
