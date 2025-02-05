# INSERT
## Insert a row
```php
$new_id = $query->insert('groups', [
    'name' => 'Moderator',
    'permissions' => 'moderator'
])->go();
```
Result query
```sql
INSERT INTO `groups` (`name`, `permissions`) VALUES ('Moderator', 'moderator');
```
## Insert many rows
```php
$query->insert('groups', [
	['name', 'role'],
	['Moderator', 'moderator'],
	['Moderator2', 'moderator'],
	['User', 'user'],
	['User2', 'user'],
])->go();
```
Result query
```sql
INSERT INTO `groups` (`name`, `role`)
VALUES ('Moderator', 'moderator'),
       ('Moderator2', 'moderator'),
       ('User', 'user'),
       ('User2', 'user');
```

To the [UPDATE section](Update.md)

Back to [doc index](index.md) or [readme](../README.md)
