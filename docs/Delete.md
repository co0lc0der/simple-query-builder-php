# DELETE
## Delete a row
```php
$query->delete('users')
  ->where([['name', '=', 'John']])
  ->go();
```
or since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4)
```php
$query->delete('users')
  ->where([['name', 'John']])
  ->go();
```
Result query
```sql
DELETE FROM `users` WHERE `name` = 'John';
```
## Delete a row with `LIMIT`
**Pay attention!** SQLite doesn't support this mode.
```php
$query->delete('users')
  ->where([['name', 'John']])
  ->limit()
  ->go();
```
Result query
```sql
DELETE FROM `users` WHERE `name` = 'John' LIMIT 1;
```
## Delete rows
```php
$query->delete('comments')
  ->where([['user_id', '=', 10]])
  ->go();
```
or since [v0.3.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.3.4)
```php
$query->delete('comments')
  ->where([['user_id', 10]])
  ->go();
```
Result query
```sql
DELETE FROM `comments` WHERE `user_id` = 10;
```

To the [TABLE section](Table.md)

Back to [doc index](index.md) or [readme](../README.md)
