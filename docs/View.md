# VIEW
Since [v0.4](https://github.com/co0lc0der/simple-query-builder-php/releases/tag/v0.4)
## CREATE
Create a view from SELECT query
```php
$query->select('users')
    ->where([['email', 'is null'], 'or', ['email', '']])
    ->createView('users_no_email')
    ->go();
```
Result query
```sql
CREATE VIEW IF NOT EXISTS `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL) OR (`email` = '');
```
One more example
```php
$query->select('users')
    ->isNull('email')
    ->createView('users_no_email')
    ->go();
```
Result query
```sql
CREATE VIEW IF NOT EXISTS `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL);
```
- Without `IF EXISTS`
```php
$query->select('users')
    ->isNull('email')
    ->createView('users_no_email', false)
    ->go();
```
Result query
```sql
CREATE VIEW `users_no_email` AS SELECT * FROM `users` WHERE (`email` IS NULL);
```
## DROP
- Drop a view
```php
$query->dropView('users_no_email')->go();
```
Result query
```sql
DROP VIEW IF EXISTS `users_no_email`;
```
- Without `IF EXISTS`
```php
$query->dropView('users_no_email', false)->go();
```
Result query
```sql
DROP VIEW `users_no_email`;
```

Back to [doc index](index.md) or [readme](../README.md)
