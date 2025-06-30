<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/../../vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/../../.env.test')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/../../.env.test');
}

if (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG'] === '1') {
    umask(0000);
}