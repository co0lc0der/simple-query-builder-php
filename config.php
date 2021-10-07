<?php

// SQLite file DB example
return [
  'database' => [
		'driver' => 'sqlite',
    'dbname' => './users.sqlite3',
		'username' => '',
		'password' => '',
		'fetchmode' => PDO::FETCH_ASSOC
  ]
];

// // SQLite memory DB example
// return [
//   'database' => [
// 		'driver' => 'memory',
// 		'fetchmode' => PDO::FETCH_ASSOC
//   ]
// ];

// MySQL example
// return [
//   'database' => [
// 		'driver' => 'mysql',
// 		'dbhost' => 'localhost',
//    'dbname' => 'my_base',
// 		'username' => 'root',
// 		'password' => '',
// 		'charset' => 'utf8',
// 		'fetchmode' => PDO::FETCH_OBJ
//   ]
// ];
