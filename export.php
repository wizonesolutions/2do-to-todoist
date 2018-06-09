<?php
/**
 * @file
 *
 * This is a handy utility to export an enriched version of the 2Do CSV file, which 2Do to Todoist
 * supports. Use this if you want to avoid "lossy" conversions of repeat date information.
 */

require_once(__DIR__ . '/vendor/autoload.php');

if (empty($GLOBALS['argv'][1]) || empty($GLOBALS['argv'][2])) {
    die("Usage: php export.php <PATH TO 2DO BACKUP> <WHERE TO SAVE EXPORTED CSV>");
}

$db = new PDO("sqlite:{$GLOBALS['argv'][1]}");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//$tables = $db->query()
