<?php

$_SERVER['APP_DEBUG'] = false;
$_ENV['APP_DEBUG'] = false;
@ini_set('display_errors', '0');
error_reporting(0);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], false);
};