# Initialization
## Edit `config.php` and set the parameters up. Choose DB driver, DB name etc
```php
$config = require_once __DIR__ . '/config.php';
```
#### Config example for SQLite DB in memory
```php
// config.php
return [
    'database' => [
        'driver' => 'memory',
    ]
];
```
#### Config example for SQLite DB file
```php
// config.php
return [
    'database' => [
        'driver' => 'sqlite',
        'dbname' => 'db.db',
        'username' => '',
        'password' => '',
    ]
];
```
#### Config example for MySQL
```php
// config.php
return [
    'database' => [
        'driver' => 'mysql',
        'dbhost' => 'localhost',
        'dbname' => _DATABASENAME_,
        'username' => _DBUSERNAME_,
        'password' => _DBPASSWORD_,
        'charset' => 'utf8',
    ]
];
```
## Use composer autoloader
```php
require_once __DIR__ . '/vendor/autoload.php';

use co0lc0der\QueryBuilder\Connection;
use co0lc0der\QueryBuilder\QueryBuilder;
```
## Init `QueryBuilder` with `Connection::make()`
```php
$query = new QueryBuilder(Connection::make($config['database'])); // $printErrors = false

// for printing errors (since 0.3.6)
$query = new QueryBuilder(Connection::make($config['database']), true)
```

To the [Methods section](Methods.md)

Back to [doc index](index.md) or [readme](../README.md)
