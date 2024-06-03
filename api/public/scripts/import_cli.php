#!/usr/bin/env php
<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use GetOpt\{GetOpt, Option, Operand};
use uLogger\Component\Lang;
use uLogger\Entity\Config;
use uLogger\Entity\Track;
use uLogger\Helper\Utils;

// check we are running in CLI mode
if (PHP_SAPI !== 'cli') {
  exit('Call me on CLI only!' . PHP_EOL);
}

if (!class_exists(GetOpt::class)) {
  exit('This script needs ulrichsg/getopt-php package. Please install dependencies via Composer.' . PHP_EOL);
}

// set up argument parsing
$getopt = new GetOpt();
$getopt->addOptions([
  Option::create('h', 'help')
    ->setDescription('Show usage/help'),

  Option::create('u', 'user-id', GetOpt::OPTIONAL_ARGUMENT)
    ->setDescription('Which user to import the track(s) for (default: 1)')
    ->setDefaultValue(1)
    ->setValidation('is_numeric', '%s has to be an integer'),

  Option::create('e', 'import-existing-track')
    ->setDescription('Import already existing tracks (based on track name)'),

  Option::create('l', 'skip-last-track')
    ->setDescription('Skip the last track (for special use cases)'),
]);

$getopt->addOperand(
  Operand::create('gpx', Operand::MULTIPLE + Operand::REQUIRED)
    ->setDescription('One or more GPX files to import')
    ->setValidation('is_readable', '%s: %s is not readable')
);

// process arguments and catch user errors
try {
  $getopt->process();
} catch (Exception $exception) {
  // be nice if the user just asked for help
  if (!$getopt->getOption('help')) {
    exit('ERROR: ' . $exception->getMessage() . PHP_EOL);
  }
}

// show help and quit
if ($getopt->getOption('help')) {
  exit($getopt->getHelpText());
}

// get all tracks for user id
$userId = (int) $getopt->getOption('user-id');

// lets import some GPX tracks!
$gpxFiles = $getopt->getOperand('gpx');
foreach ($gpxFiles as $i => $gpxFile) {
  // skip last track?
  if ($getopt->getOption('skip-last-track') && $i === count($gpxFiles) - 1) {
    continue;
  }

  $gpxName = basename($gpxFile);

  if (!$getopt->getOption('import-existing-track')) {
    $tracksArr = Track::getAll($userId);
    foreach ($tracksArr as $track) {
      if ($track->name === $gpxName) {
        print('WARNING: ' . $gpxName . ' already present, skipping...' . PHP_EOL);
        continue 2;
      }
    }
  }

  print('importing ' . $gpxFile.'...' . PHP_EOL);

  $config = Config::getInstance();
  $lang = (new Lang($config))->getStrings();

  $gpx = false;
  libxml_use_internal_errors(true);
  if ($gpxFile && file_exists($gpxFile)) {
    $gpx = simplexml_load_file($gpxFile);
  }

  if ($gpx === false) {
    $message = $lang["iparsefailure"];
    $parserMessages = [];
    foreach(libxml_get_errors() as $parseError) {
      $parserMessages[] = $parseError->message;
    }
    $parserMessage = implode(", ", $parserMessages);
    if (!empty($parserMessage)) {
      $message .= ": $parserMessage";
    }
    Utils::exitWithError($message);
  }
  elseif ($gpx->getName() !== "gpx") {
    Utils::exitWithError($lang["iparsefailure"]);
  }
  elseif (empty($gpx->trk)) {
    Utils::exitWithError($lang["idatafailure"]);
  }

  $trackList = [];
  foreach ($gpx->trk as $trk) {
    $trackName = empty($trk->name) ? $gpxName : (string) $trk->name;
    $metaName = empty($gpx->metadata->name) ? null : (string) $gpx->metadata->name;
    $trackId = Track::add($userId, $trackName, $metaName);
    if ($trackId === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    $track = new Track($trackId);
    $posCnt = 0;

    foreach($trk->trkseg as $segment) {
      foreach($segment->trkpt as $point) {
        if (!isset($point["lat"], $point["lon"])) {
          $track->delete();
          Utils::exitWithError($lang["iparsefailure"]);
        }
        $time = isset($point->time) ? strtotime((string) $point->time) : 1;
        $altitude = isset($point->ele) ? (double) $point->ele : null;
        $comment = !empty($point->desc) ? (string) $point->desc : null;
        $speed = null;
        $bearing = null;
        $accuracy = null;
        $provider = "gps";
        if (!empty($point->extensions)) {
          // parse ulogger extensions
          $ext = $point->extensions->children('ulogger', true);
          if (count($ext->speed)) { $speed = (double) $ext->speed; }
          if (count($ext->bearing)) { $bearing = (double) $ext->bearing; }
          if (count($ext->accuracy)) { $accuracy = (int) $ext->accuracy; }
          if (count($ext->provider)) { $provider = (string) $ext->provider; }
        }
        $ret = $track->addPosition($userId,
          $time, (double) $point["lat"], (double) $point["lon"], $altitude,
          $speed, $bearing, $accuracy, $provider, $comment);
        if ($ret === false) {
          $track->delete();
          Utils::exitWithError($lang["servererror"]);
        }
        $posCnt++;
      }
    }
    if ($posCnt) {
      array_unshift($trackList, [ "id" => $track->id, "name" => $track->name ]);
    } else {
      $track->delete();
    }
  }
}
?>
