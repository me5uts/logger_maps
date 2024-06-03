<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Controller\Auth;
use uLogger\Controller\Config;
use uLogger\Entity\Position;
use uLogger\Helper\Utils;

$auth = new Auth();
$config = Config::getInstance();

$userId = Utils::getInt('userid');
$trackId = Utils::getInt('trackid');
$afterId = Utils::getInt('afterid');
$last = Utils::getBool('last');

$positionsArr = [];
if ($userId) {
  if ($auth->hasReadAccess($userId)) {
    if ($trackId) {
      // get all track data
      $positionsArr = Position::getAll($userId, $trackId, $afterId);
    } else if ($last) {
      // get data only for latest point
      $position = Position::getLast($userId);
      if ($position->isValid) {
        $positionsArr[] = $position;
      }
    }
  }
} else if ($last) {
  if ($auth->hasPublicReadAccess() || $auth->isAdmin()) {
    $positionsArr = Position::getLastAllUsers();
  }
}

$result = [];
if ($positionsArr === false) {
  $result = [ "error" => true ];
} else if (!empty($positionsArr)) {
  if ($afterId) {
    $afterPosition = new Position($afterId);
    if ($afterPosition->isValid) {
      $prevPosition = $afterPosition;
    }
  }
  foreach ($positionsArr as $position) {
    $meters = !$last && isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
    $seconds = !$last && isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
    $result[] = [
      "id" => $position->id,
      "latitude" => $position->latitude,
      "longitude" => $position->longitude,
      "altitude" => ($position->altitude) ? round($position->altitude) : $position->altitude,
      "speed" => $position->speed,
      "bearing" => $position->bearing,
      "timestamp" => $position->timestamp,
      "accuracy" => $position->accuracy,
      "provider" => $position->provider,
      "comment" => $position->comment,
      "image" => $position->image,
      "username" => $position->userLogin,
      "trackid" => $position->trackId,
      "trackname" => $position->trackName,
      "meters" => $meters,
      "seconds" => $seconds
    ];
    $prevPosition = $position;
  }
}
header("Content-type: application/json");
echo json_encode($result);

?>
