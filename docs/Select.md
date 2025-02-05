# SELECT
More examples you can find in tests.
## Simple queries
### Selects with no tables
#### Math
```php
$results = $query->select("1+5 as 'res'")->one();
```
Result query
```sql
SELECT 1+5 as 'res';
```
#### Substring
```php
$results = $query->select("substr('Hello world!', 1, 5) as 'str'")->one();
```
Result query
```sql
SELECT substr('Hello world!', 1, 5) as 'str';
```
#### Current date and time
```php
$results = $query->select("strftime('%Y-%m-%d %H:%M', 'now')")->one();
```
Result query
```sql
SELECT strftime('%Y-%m-%d %H:%M', 'now');
```
#### SQLite functions
```php
$results = $query->select("sqlite_version() as ver")->one();
```
Result query
```sql
SELECT sqlite_version() as ver;
```
### Select all rows from a table
```php
$results = $query->select('users')->all();
```
Result query
```sql
SELECT * FROM `users`;
```
### Select a row with a condition
```php
$results = $query->select('users')->where([['id', '=', 10]])->one();
```
It's able not using equals `=` in `WHERE` conditions since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4) 
```php
$results = $query->select('users')->where([['id', 10]])->one();
```
Result query
```sql
SELECT * FROM `users` WHERE `id` = 10;
```
### Select rows with two conditions
```php
$results = $query->select('users')->where([['id', '>', 1], 'and', ['group_id', '=', 2]])->all();
```
or since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4)
```php
$results = $query->select('users')->where([['id', '>', 1], 'and', ['group_id', 2]])->all();
```
Result query
```sql
SELECT * FROM `users` WHERE (`id` > 1) AND (`group_id` = 2);
```
### Select a row with a `LIKE` and `NOT LIKE` condition
```php
$results = $query->select('users')->like(['name', '%John%'])->all();

# or with WHERE
$results = $query->select('users')->where([['name', 'LIKE', '%John%']])->all();
```
or it's able to use two strings (instead of an array) in parameters since [v0.3.5](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.5)
```php
$results = $query->select('users')->like('name', '%John%')->all();
```
Result query
```sql
SELECT * FROM `users` WHERE (`name` LIKE '%John%');
```
```php
$results = $query->select('users')->notLike(['name', '%John%'])->all();

# or with WHERE
$results = $query->select('users')->where([['name', 'NOT LIKE', '%John%']])->all();
```
or it's able to use two strings (instead of an array) in parameters since [v0.3.5](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.5)
```php
$results = $query->select('users')->notLike('name', '%John%')->all();
```
Result query
```sql
SELECT * FROM `users` WHERE (`name` NOT LIKE '%John%');
```
### Select a row with a `IS NULL` and `IS NOT NULL` condition
since [v0.3.5](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.5)
```php
$results = $query->select('users')->isNull('phone')->all();

# or with WHERE
$results = $query->select('users')->where([['phone', 'is null']])->all();
```
Result query
```sql
SELECT * FROM `users` WHERE (`phone` IS NULL);
```
```php
$results = $query->select('customers')->isNotNull('address')->all();

# or
$results = $query->select('customers')->notNull('address')->all();

# or with WHERE
$results = $query->select('customers')->where([['address', 'is not null']])->all();
```
Result query
```sql
SELECT * FROM `customers` WHERE (`address` IS NOT NULL);
```
### Select rows with `OFFSET` and `LIMIT`
```php
$results = $query->select('posts')
    ->where([['user_id', '=', 3]])
    ->offset(14)
    ->limit(7)
    ->all();
```
It's able not using equals `=` in `WHERE` conditions since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4) 
```php
$results = $query->select('posts')
    ->where([['user_id', 3]])
    ->offset(14)
    ->limit(7)
    ->all();
```
Result query
```sql
SELECT * FROM `posts` WHERE (`user_id` = 3) OFFSET 14 LIMIT 7;
```
### Select custom fields with additional SQL
#### `COUNT()`
```php
$results = $query->select('users', ['counter' => 'COUNT(*)'])->one();

# or 
$results = $query->count('users');
```
Result query
```sql
SELECT COUNT(*) AS `counter` FROM `users`;
```
#### `ORDER BY`
```php
$results = $query->select(['b' => 'branches'], ['b.id', 'b.name'])
    ->where([['b.id', '>', 1], 'and', ['b.parent_id', 1]])
    ->orderBy('b.id', 'desc')
    ->all();
```
It's able not using equals `=` in `WHERE` conditions since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4) 
```php
$results = $query->select(['b' => 'branches'], ['b.id', 'b.name'])
    ->where([['b.id', '>', 1], 'and', ['b.parent_id', 1]])
    ->orderBy('b.id desc')
    ->all();
```
Result query
```sql
SELECT `b`.`id`, `b`.`name` FROM `branches` AS `b`
WHERE (`b`.`id` > 1) AND (`b`.`parent_id` = 1)
ORDER BY `b`.`id` DESC;
```
#### `DISTINCT`
```php
$results = $query->select('customers', ['city', 'country'], true)->orderBy('country desc')->all();
```
Result query
```sql
SELECT DISTINCT `city`, `country` FROM `customers` ORDER BY `country` DESC;
```
#### `GROUP BY` and `HAVING`
```php
$results = $query->select('posts', ['id', 'category', 'title'])
    ->where([['views', '>=', 1000]])
    ->groupBy('category')
    ->all();
```
Result query
```sql
SELECT `id`, `category`, `title` FROM `posts`
WHERE (`views` >= 1000) GROUP BY `category`;
```
More complicated example
```php
$results = $query->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
    ->where([['YEAR(`created_at`)', '=', 2020]])
    ->groupBy('month_num')
    ->having([['total', '=', 20000]])
    ->all();
```
It's able not using equals `=` in `HAVING` conditions since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4) 
```php
$results = $query->select('orders', ['month_num' => 'MONTH(`created_at`)', 'total' => 'SUM(`total`)'])
    ->where([['YEAR(`created_at`)', 2020]])
    ->groupBy('month_num')
    ->having([['total', 20000]])
    ->all();
```
Result query
```sql
SELECT MONTH(`created_at`) AS `month_num`, SUM(`total`) AS `total`
FROM `orders` WHERE (YEAR(`created_at`) = 2020)
GROUP BY `month_num` HAVING (`total` = 20000);
```

To the [SELECT extensions section](Select_ext.md)

Back to [doc index](index.md) or [readme](../README.md)
