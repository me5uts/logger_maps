<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Component\Lang;
use uLogger\Entity\Config;
use uLogger\Entity\Track;
use uLogger\Helper\Upload;
use uLogger\Helper\Utils;

$auth = new Auth();

$config = Config::getInstance();
$lang = (new Lang($config))->getStrings();

if (!$auth->isAuthenticated()) {
  Utils::exitWithError($lang["private"]);
}

try {
  $fileMeta = Utils::requireFile("gpx");
} catch (ErrorException $ee) {
  $message = $lang["servererror"];
  $message .= ": {$ee->getMessage()}";
  Utils::exitWithError($message);
} catch (Exception $e) {
  $message = $lang["iuploadfailure"];
  $message .= ": {$e->getMessage()}";
  Utils::exitWithError($message);
}

$gpxFile = $fileMeta[Upload::META_TMP_NAME];
$gpxName = basename($fileMeta[Upload::META_NAME]);
libxml_use_internal_errors(true);
/** @noinspection SimpleXmlLoadFileUsageInspection */
$gpx = simplexml_load_file($gpxFile);
unlink($gpxFile);

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
  $trackId = Track::add($auth->user->id, $trackName, $metaName);
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
      $ret = $track->addPosition($auth->user->id,
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

header("Content-type: application/json");
echo json_encode($trackList);
?>
