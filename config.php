<?php
namespace Nezimi;

$config = [
    'master' => [
        'type' => 'mysql',
        'dsn' => '',
        'hostname' => 'localhost',
        'database' => 'mycms',
        'username' => 'root',
        'password' => 'pyl',
        'hostport' => '',
        'prefix' => 'cms_',
        'charset' => 'utf8',
        'pconnect' => 0,
        'autoconnect' => 0
    ],
];

Db::setConfig($config);