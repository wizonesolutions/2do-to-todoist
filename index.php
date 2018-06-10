<?php

use League\Csv\Reader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use WizOneSolutions\TwoDoToTodoist\Todoist\Importer;
use WizOneSolutions\TwoDoToTodoist\TwoDo\CSVConverter;

require_once(__DIR__ . '/vendor/autoload.php');

if (empty($GLOBALS['argv'][1])) {
    die("Usage: php index.php <FILE PATH>");
}

// Load config from YAML. Determine if this is a dry run or not.
try {
    $config = Yaml::parse(file_get_contents(__DIR__ . '/config.yml'));

    if (empty($config['api_token'])) {
        die("You need to configure your api_token before running this importer. See config-example.yml in the same directory as this script and copy it to config.yml.");
    }

    $reader = Reader::createFromPath($GLOBALS['argv'][1]);
    $tasks = CSVConverter::parse($reader, $config);

    Importer::import($tasks, $config);
} catch (ParseException $exception) {
    die('Unable to parse the YAML string: ' . $exception->getMessage());
} catch (\Exception $exception) {
    die('Unexpected exception encountered: ' . $exception->getMessage());
}
