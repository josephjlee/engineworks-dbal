<?php
// report all errors
error_reporting(-1);

setlocale(LC_ALL, 'en_US');
date_default_timezone_set('UTC');

// composer
require_once __DIR__ . '/../vendor/autoload.php';

// environment
call_user_func(function () {
    $dotenv = \Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
    $dotenv->required('testMssql')->allowedValues(['yes', 'no']);
    $dotenv->required('testSqlsrv')->allowedValues(['yes', 'no']);
    $dotenv->required('testMysqli')->allowedValues(['yes', 'no']);
});
