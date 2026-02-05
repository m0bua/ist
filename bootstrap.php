<?php

date_default_timezone_set('Europe/Kyiv');
defined('ROOT') or define('ROOT', __DIR__);
defined('IS_WEB') or define('IS_WEB', php_sapi_name() != 'cli');
require ROOT . '/vendor/autoload.php';
spl_autoload_register(function ($class) {
    $file = implode(
        DIRECTORY_SEPARATOR,
        array_merge(
            [ROOT, 'logic'],
            explode('\\', $class . '.php')
        )
    );
    if (file_exists($file)) include $file;
});

Helpers\DB::config(include 'cfg/db.php');
if (IS_WEB) (new Auth)->start();
