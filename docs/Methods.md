# Main public methods
## QueryBuilder class
- `query($sql, $params[], $fetch_type)` executes prepared `$sql` with `$params`. it can be used for custom queries
- `getSql()` returns SQL query string which will be executed
- `getParams()` returns an array of parameters for a query
- `getResult()` returns query's results
- `getCount()` returns results' rows count
- `getDriver()` returns DB driver name in lowercase
- `hasError()` returns `true` if an error is had
- `getErrorMessage()` returns an error message if an error is had
- `setError($message)` sets `$error` to `true` and `$errorMessage`
- `getFirst()` returns the first item of results
- `getLast()` returns the last item of results
- `reset()` resets state to default values (except PDO property)
- `all()` executes SQL query and returns **all rows** of result (`fetchAll()`)
- `one()` executes SQL query and returns **the first row** of result (`fetch()`)
- `column($col)` executes SQL query and returns the needed column of result by its name, `col` is `'id'` by default
- `pluck($key, $col)` executes SQL query and returns an array (the key (usually ID) and the needed column of result) by their names, `key` is `id` and `col` is `''` by default
- `go()` this method is for non `SELECT` queries. it executes SQL query and returns nothing (but returns the last inserted row ID for `INSERT` method)
- `count()` prepares a query with SQL `COUNT(*)` function and _executes it_
- `exists()` returns `true` if SQL query has at least one row and `false` if it hasn't

'SQL' methods are presented in the [next section](Select.md).

Back to [doc index](index.md) or [readme](../README.md)
