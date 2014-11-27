<?php
/**
 * Entry point
 */

$foundationPath = __DIR__ . '/../../foundation';
require($foundationPath . '/bootstrap.php');

$appPath = __DIR__.'/..';
require($appPath . '/Application.php');

(new Application('main', __DIR__, $appPath, $foundationPath))->run();