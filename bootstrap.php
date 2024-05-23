<?php

defined('ROOT') or define('ROOT', __DIR__);
spl_autoload_register(function ($class) {
    include ROOT . '/logic/' . $class . '.php';
});
