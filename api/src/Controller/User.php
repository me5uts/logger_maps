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

class User {
  private Auth $auth;

  public function __construct(Auth $auth) {
    $this->auth = $auth;
  }

  // FIXME: private access is not handled!
  public function getAll(): Response {
    $result = [];
    $usersArr = Entity\User::getAll();
    if ($usersArr === false) {
      $result = [ "error" => true ];
    } elseif (!empty($usersArr)) {
      foreach ($usersArr as $user) {
        // only load admin status on admin user request
        $isAdmin = $this->auth->isAdmin() ? $user->isAdmin : null;
        $result[] = [ "id" => $user->id, "login" => $user->login, "isAdmin" => $isAdmin ];
      }
    }
    return Response::success($result);
  }

  public function getTracks($userId): Response {
    $tracksArr = Entity\Track::getAll($userId);

    $result = [];
    if ($tracksArr === false) {
      $result = [ "error" => true ];
    } elseif (!empty($tracksArr)) {
      foreach ($tracksArr as $track) {
        $result[] = [ "id" => $track->id, "name" => $track->name ];
      }
    }
    return Response::success($result);
  }

  public function getPosition(int $userId): Response {
    $position = Entity\Position::getLast($userId);
    if ($position->isValid) {
      $result = [
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
        "meters" => 0,
        "seconds" => 0
      ];
    } else {
      $result = [ "error" => true ];
    }

    return Response::success($result);
  }

  public function getAllPosition(): Response {
    $positionsArr = Entity\Position::getLastAllUsers();
    $result = [];
    if ($positionsArr === false) {
      $result = [ "error" => true ];
    } elseif (!empty($positionsArr)) {

      foreach ($positionsArr as $position) {
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
          "meters" => 0,
          "seconds" => 0
        ];
      }
    }
    return Response::success($result);
  }

}
