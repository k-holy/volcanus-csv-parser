<?php
/**
 * Volcanus libraries for PHP 8.1~
 *
 * @copyright k-holy <k.holy74@gmail.com>
 * @license The MIT License (MIT)
 */
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

$loader = include realpath(__DIR__ . '/../vendor/autoload.php');
$loader->add('Volcanus\CsvParser\Test', __DIR__);
