<?php
declare(strict_types = 1);
/* μlogger
 *
 * Copyright(C) 2017 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

require_once('../../vendor/autoload.php');

use uLogger\Controller\Auth;
use uLogger\Entity\Position;
use uLogger\Entity\Track;
use uLogger\Entity\User;
use uLogger\Helper\Upload;
use uLogger\Helper\Utils;

/**
 * Exit with error status and message
 *
 * @param string $message Message
 */
function exitWithError(string $message) {
  $response = [];
  $response['error'] = true;
  $response['message'] = $message;
  header('Content-Type: application/json');
  echo json_encode($response);
  exit();
}

/**
 * Exit with success status
 *
 * @param array $params Optional params
 * @return void
 */
function exitWithSuccess(array $params = []) {
  $response = [];
  $response['error'] = false;
  header('Content-Type: application/json');
  echo json_encode(array_merge($response, $params));
  exit();
}

$action = Utils::postString('action');

$auth = new Auth();
if ($action !== "auth" && !$auth->isAuthenticated()) {
  $auth->exitWithUnauthorized();
}

switch ($action) {
  // action: authorize
  case "auth":
    $login = Utils::postString('user');
    $pass = Utils::postPass('pass');
    if ($auth->checkLogin($login, $pass)) {
      exitWithSuccess();
    } else {
      $auth->exitWithUnauthorized();
    }
    break;

  // action: adduser (currently unused)
  case "adduser":
    if (!$auth->user->isAdmin) {
      exitWithError("Not allowed");
    }
    $login = Utils::postString('login');
    $pass = Utils::postPass('password');
    if (empty($login) || empty($pass)) {
      exitWithError("Empty login or password");
    }
    $newId = User::add($login, $pass);
    if ($newId === false) {
      exitWithError("Server error");
    }
    exitWithSuccess(['userid' => $newId]);
    break;

  // action: addtrack
  case "addtrack":
    $trackName = Utils::postString('track');
    if (empty($trackName)) {
      exitWithError("Missing required parameter");
    }
    $trackId = Track::add($auth->user->id, $trackName);
    if ($trackId === false) {
      exitWithError("Server error");
    }
    // return track id
    exitWithSuccess(['trackid' => $trackId]);
    break;

  // action: addposition
  case "addpos":
    $lat = Utils::postFloat('lat');
    $lon = Utils::postFloat('lon');
    $timestamp = Utils::postInt('time');
    $altitude = Utils::postFloat('altitude');
    $speed = Utils::postFloat('speed');
    $bearing = Utils::postFloat('bearing');
    $accuracy = Utils::postInt('accuracy');
    $provider = Utils::postString('provider');
    $comment = Utils::postString('comment');
    $imageMeta = Utils::requestFile('image');
    $trackId = Utils::postInt('trackid');

    if (!is_float($lat) || !is_float($lon) || !is_int($timestamp) || !is_int($trackId)) {
      exitWithError("Missing required parameter");
    }

    $image = null;
    if (!empty($imageMeta)) {
      $image = Upload::add($imageMeta, $trackId);
    }

    $positionId = Position::add($auth->user->id, $trackId,
      $timestamp, $lat, $lon, $altitude, $speed, $bearing, $accuracy, $provider, $comment, $image);

    if ($positionId === false) {
      exitWithError("Server error");
    }
    exitWithSuccess();
    break;

  default:
    exitWithError("Unknown command");
    break;
}

?>
