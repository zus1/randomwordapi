<?php
include_once("classes/httpparser.php");
include_once("classes/httpcodes.php");
include_once("config/config.php");

Config::init("init.ini");
return
    [
        'paths' => [
            'migrations' => "db/migrations",
            'seeds' => "db/seeds"
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            //'default_database' => Config::get(Config::DB_NAME),
            'production' => [
                'adapter' => Config::get(Config::DB_CONNECTION),
                'host' => Config::get(Config::DB_HOST),
                'name' => Config::get(Config::DB_NAME),
                'user' => Config::get(Config::DB_USERNAME),
                'pass' => Config::get(Config::DB_PASSWORD),
                'port' => Config::get(Config::DB_PORT),
                'charset' => Config::get(Config::DB_CHARSET),
                'collation' => 'utf8_unicode_ci',
                'table_prefix' => ''
            ],
            'development' => [
                'adapter' => Config::get(Config::DB_CONNECTION),
                'host' => Config::get(Config::DB_HOST),
                'name' => Config::get(Config::DB_NAME),
                'user' => Config::get(Config::DB_USERNAME),
                'pass' => Config::get(Config::DB_PASSWORD),
                'port' => Config::get(Config::DB_PORT),
                'charset' => Config::get(Config::DB_CHARSET),
                'collation' => 'utf8_unicode_ci',
                'table_prefix' => ''
            ]
        ],
        'version_order' => 'creation'
    ];
