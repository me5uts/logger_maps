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

class Position {

  private Auth $auth;

  public function __construct(Auth $auth) {
    $this->auth = $auth;
  }

  public function getAll(int $trackId, ?int $afterId = null): Response {
    $positionsArr = Entity\Position::getAll(null, $trackId, $afterId);
    $result = [];
    if ($positionsArr === false) {
      $result = [ "error" => true ];
    } elseif (!empty($positionsArr)) {
      if ($afterId) {
        $afterPosition = new Entity\Position($afterId);
        if ($afterPosition->isValid) {
          $prevPosition = $afterPosition;
        }
      }
      foreach ($positionsArr as $position) {
        $meters = isset($prevPosition) ? $position->distanceTo($prevPosition) : 0;
        $seconds = isset($prevPosition) ? $position->secondsTo($prevPosition) : 0;
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

  public function save($trackId): Response {
    return Response::internalServerError("Not implemented");
  }
}
