<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Default
    |--------------------------------------------------------------------------
    */

    'default' => 'pgsql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [
        'mysql' => [
            'driver'         => 'mysql',
            'url'            => env('MYSQL_URL'),
            'host'           => env('MYSQL_HOST'),
            'port'           => env('MYSQL_PORT'),
            'database'       => env('MYSQL_NAME'),
            'username'       => env('MYSQL_USER'),
            'password'       => env('MYSQL_PASS'),
            'unix_socket'    => env('MYSQL_SOCKET'),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,

            'options' => extension_loaded('pdo_mysql')
                ? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')])
                : [],
        ],

        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('PGSQL_URL'),
            'host'           => env('PGSQL_HOST'),
            'port'           => env('PGSQL_PORT'),
            'database'       => env('PGSQL_NAME'),
            'username'       => env('PGSQL_USER'),
            'password'       => env('PGSQL_PASS'),
            'charset'        => 'utf8',
            'collation'      => 'utf8_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => 'prefer',
        ],

        'mysql_milliy' => [
            'driver'         => 'mysql',
            'url'            => env('MYSQL_MILLIY_URL'),
            'host'           => env('MYSQL_MILLIY_HOST'),
            'port'           => env('MYSQL_MILLIY_PORT'),
            'database'       => env('MYSQL_MILLIY_NAME'),
            'username'       => env('MYSQL_MILLIY_USER'),
            'password'       => env('MYSQL_MILLIY_PASS'),
            'unix_socket'    => env('MYSQL_MILLIY_SOCKET'),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,

            'options' => extension_loaded('pdo_mysql')
                ? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')])
                : [],
        ],

        'mysql_bpm' => [
            'driver'         => 'mysql',
            'url'            => env('MYSQL_BPM_URL'),
            'host'           => env('MYSQL_BPM_HOST'),
            'port'           => env('MYSQL_BPM_PORT'),
            'database'       => env('MYSQL_BPM_NAME'),
            'username'       => env('MYSQL_BPM_USER'),
            'password'       => env('MYSQL_BPM_PASS'),
            'unix_socket'    => env('MYSQL_BPM_SOCKET'),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,

            'options' => extension_loaded('pdo_mysql')
                ? array_filter([PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')])
                : [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Migrations
    |--------------------------------------------------------------------------
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Database Redis
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'client' => 'phpredis',

        'queue' => [
            'url'             => env('REDIS_URL'),
            'host'            => env('REDIS_HOST'),
            'port'            => env('REDIS_PORT'),
            'username'        => env('REDIS_USER'),
            'password'        => env('REDIS_PASS'),
            'database'        => env('REDIS_QUEUE_DB'),
            'prefix'          => '',
            'lock_connection' => 'default',
        ],

        'cache' => [
            'url'             => env('REDIS_URL'),
            'host'            => env('REDIS_HOST'),
            'port'            => env('REDIS_PORT'),
            'username'        => env('REDIS_USER'),
            'password'        => env('REDIS_PASS'),
            'database'        => env('REDIS_CACHE_DB'),
            'prefix'          => '',
            'lock_connection' => 'default',
        ],

        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST'),
            'port'     => env('REDIS_PORT'),
            'username' => env('REDIS_USER'),
            'password' => env('REDIS_PASS'),
            'database' => env('REDIS_DEFAULT_DB'),
            'prefix'   => '',
        ],
    ],
];
