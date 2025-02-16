<?php

defined('IS_WEB') or define('IS_WEB', php_sapi_name() != 'cli');
spl_autoload_register(function ($class) {
    include implode(
        DIRECTORY_SEPARATOR,
        array_merge(
            [__DIR__, 'logic'],
            explode('\\', $class . '.php')
        )
    );
});

Helpers\DB::config(include 'cfg/db.php');
if (IS_WEB) (new Auth)->start();
