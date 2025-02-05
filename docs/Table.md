# TABLE
## TRUNCATE
Truncate a table

**! This method will be moved into _TableBuilder_ class !**
```php
$query->truncate('users')->go();
```
Result query
```sql
TRUNCATE TABLE `users`;
```
## DROP
- Drop a table

**! This method will be moved into _TableBuilder_ class !**
```php
$query->drop('temporary')->go();
```
Result query
```sql
DROP TABLE IF EXISTS `temporary`;
```
- Without `IF EXISTS`
```php
$query->drop('temporary', false)->go();
```
Result query
```sql
DROP TABLE `temporary`;
```

To the [View section](View.md)

Back to [doc index](index.md) or [readme](../README.md)
