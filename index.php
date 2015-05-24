<?php

require 'Pastebin.php';

$method   = isset($argv[1]) ? trim($argv[1]) : 'process';

$runner = new \CC\Pastebin(__DIR__ . DIRECTORY_SEPARATOR . 'files', [1, 2]);

if (is_callable(array($runner, $method))) {
    $runner->$method();
} else {
    echo 'Invalid method "' . $method . '"';
}