<?php

date_default_timezone_set('UTC');
const PHPUNIT_AUTOLOAD = __DIR__ . '/../../../vendor/bin/.phpunit/phpunit-7.4/vendor/autoload.php';

require __DIR__ . '/../../../vendor/autoload.php';
// For behat tests to use php assertion
if (file_exists(PHPUNIT_AUTOLOAD)) {
    require PHPUNIT_AUTOLOAD;
}
require __DIR__ . '/AppKernel.php';
