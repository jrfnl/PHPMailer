<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (defined('__PHPUNIT_PHAR__')) {
    $partialPath = '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
    if (file_exists(dirname(__DIR__) . $partialPath)) {
        // Composer local install.
        require_once dirname(__DIR__) . $partialPath;
    } else {
        // Try and find the package in the Composer global install.
        $lastLine = exec('composer global config home --absolute --quiet', $output);
        if (is_string($lastLine) && $lastLine !== '' && file_exists($lastLine . $partialPath)) {
            require_once $lastLine . $partialPath;
        }
    }
}
