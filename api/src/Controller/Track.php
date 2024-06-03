<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Auth;
use uLogger\Component\Response;
use uLogger\Entity;
use uLogger\Helper\Utils;

class Track {

  private Auth $auth;

  public function __construct(Auth $auth) {
    $this->auth = $auth;
  }

  public function get(int $trackId): Response {
    $track = new Entity\Track($trackId);
    if (!$track->isValid) {
      return Response::internalServerError("servererror");//$lang["servererror"]);
    }
    $result = [
      "id" => $track->id,
      "name" => $track->name,
      "userId" => $track->userId,
      "comment" => $track->comment
    ];
    return Response::success($result);
  }

  public function xxx($trackId): Response {
    $userId = Utils::getInt('userid');
//    $trackId = Utils::getInt('trackid');
    $afterId = Utils::getInt('afterid');
    $last = Utils::getBool('last');

    $positionsArr = [];
    if ($userId) {
      if ($this->auth->hasReadAccess($userId)) {
        if ($trackId) {
          // get all track data
          $positionsArr = Entity\Position::getAll($userId, $trackId, $afterId);
        } else if ($last) {
          // get data only for latest point
          $position = Entity\Position::getLast($userId);
          if ($position->isValid) {
            $positionsArr[] = $position;
          }
        }
      }
    } else if ($last) {
      if ($this->auth->hasPublicReadAccess() || $this->auth->isAdmin()) {
        $positionsArr = Entity\Position::getLastAllUsers();
      }
    }

    $result = [];
    if ($positionsArr === false) {
      $result = [ "error" => true ];
    } else if (!empty($positionsArr)) {
      if ($afterId) {
        $afterPosition = new Entity\Position($afterId);
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
    return Response::success($result);
  }
}
