<?php

use League\Csv\Reader;
use Symfony\Component\Yaml\Yaml;
use WizOneSolutions\TwoDoToTodoist\CSVConverter;

require_once(__DIR__ . '/vendor/autoload.php');

if (empty($GLOBALS['argv'][1])) {
    die("Usage: php index.php <FILE PATH>");
}

// Load config from YAML. Determine if this is a dry run or not.
try {
    $config = Yaml::parse(__DIR__ . '/config.yml');
   $reader = Reader::createFromPath($GLOBALS['argv'][1]);
    $tasks = CSVConverter::parse($reader, $config);
} catch (\Exception $exception) {
    die('Unable to parse the YAML string: ' . $exception->getMessage());
}
