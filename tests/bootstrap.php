<?php

declare(strict_types=1);

namespace {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require $autoload;
    }

    if (!defined('WICKET_ORGROSTER_DOINGTESTS')) {
        define('WICKET_ORGROSTER_DOINGTESTS', true);
    }

    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . '/');
    }

    $shimFile = __DIR__ . '/helpers/wp-shims.php';
    if (file_exists($shimFile)) {
        require_once $shimFile;
    }

    $configFile = __DIR__ . '/../src/config/config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    }
}
