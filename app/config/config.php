<?php
return array(
    'debug' => true,
    'profiler' => true,
    'baseUrl' => '/api/',
    'dbMaster' =>
        array(
            'adapter' => 'Mysql',
            'host' => '172.16.8.113',
            'port' => '3306',
            'username' => 'root',
            'password' => 'root',
            'dbname' => 'phpcms',
            'prefix' => 'wj_',
            'charset' => 'UTF8',
        ),
    'dbSlave' =>
        array(
            'adapter' => 'Mysql',
            'host' => '172.16.8.113',
            'port' => '3306',
            'username' => 'root',
            'password' => 'root',
            'dbname' => 'phpcms',
            'prefix' => 'wj_',
            'charset' => 'UTF8',
        ),
    'cache' =>
        array(
            'lifetime' => '86400',
            'adapter' => 'Redis',
            'host' => '172.16.8.113',
            'port' => 6379,
            'prefix' => 'wj_',
            'persistent' => true,
            'cacheDir' => CACHE_PATH . '/data/',
        ),
    'queue' =>
        array(
            'host' => '172.16.8.113',
            'port' => 6379,
//            'auth'=>'',
            'persistent' => true,
        ),
    'logger' =>
        array(
            'enabled' => true,
            'path' => DATA_PATH . '/logs/',
            'format' => '[%date%][%type%] %message%',
        )

);

