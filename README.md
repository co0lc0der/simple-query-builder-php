# QueryBuilder php component

[![Latest Version](https://img.shields.io/github/release/co0lc0der/simple-query-builder-php?style=flat-square)](https://github.com/co0lc0der/simple-query-builder-php/release)
![GitHub repo size](https://img.shields.io/github/repo-size/co0lc0der/simple-query-builder-php?color=orange&label=size&style=flat-square)
[![Packagist Downloads](https://img.shields.io/packagist/dt/co0lc0der/simple-query-builder?color=yellow&style=flat-square)](https://packagist.org/packages/co0lc0der/simple-query-builder)
[![GitHub license](https://img.shields.io/github/license/co0lc0der/simple-query-builder-php?style=flat-square)](https://github.com/co0lc0der/simple-query-builder-php/blob/main/LICENSE.md)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/co0lc0der/simple-query-builder?color=8993be&style=flat-square)

This is a small easy-to-use PHP component for working with a database by PDO. It provides some public methods to compose SQL queries and manipulate data. Each SQL query is prepared and safe. QueryBuilder fetches data to _arrays_ by default. At present time the component supports MySQL and SQLite (file or memory).

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
#### Select all rows from a table
```php
$results = $query->select('users')->all();
```
Result query
```sql
SELECT * FROM `users`;
```
#### Select rows with two conditions
```php
$results = $query->select('users')->where([
  ['id', '>', 1],
  'and',
  ['group_id', 2],
])->all();
```
Result query
```sql
SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2);
```
#### Update a row
```php
$query->update('posts', ['status' => 'published'])
        ->where([['YEAR(`updated_at`)', '>', 2020]])
        ->go();
```
Result query
```sql
UPDATE `posts` SET `status` = 'published'
WHERE (YEAR(`updated_at`) > 2020);
```
More examples you can find in [documentation](https://github.com/co0lc0der/simple-query-builder-php/blob/main/docs/index.md) or tests.

## ToDo
I'm going to add the next features into future versions
- write more unit testes
- add subqueries for QueryBuilder
- add `BETWEEN`
- add `WHERE EXISTS`
- add TableBuilder class (for beginning `CREATE TABLE`, move `$query->drop()` and `$query->truncate()` into it)
- add PostgreSQL support
- add `WITH`
- and probably something more
