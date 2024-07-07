<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Session;
use uLogger\Component\Db;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Utils;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

/**
 * Exit with error status and message
 *
 * @param string $message Message
 * @return no-return
 */
function exitWithError(string $message): void {
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
 * @return no-return
 */
function exitWithSuccess(array $params = []): void {
  $response = [];
  $response['error'] = false;
  header('Content-Type: application/json');
  echo json_encode(array_merge($response, $params));
  exit();
}

$action = Utils::postString('action');

try {
  $mapperFactory = new MapperFactory(Db::createFromConfig());
  $config = Entity\Config::createFromMapper($mapperFactory);
  $auth = new Session($mapperFactory, $config);
  $auth->init();
  /** @var Mapper\Track $mapperTrack */
  $mapperTrack = $mapperFactory->getMapper(Mapper\Track::class);
  /** @var Mapper\Position $mapperPosition */
  $mapperPosition = $mapperFactory->getMapper(Mapper\Position::class);
} catch (DatabaseException|ServerException $e) {
  exitWithError("Server error");
}

if ($action !== "auth" && !$auth->isAuthenticated()) {
  $auth->exitWithUnauthorized();
}

switch ($action) {
  // action: authorize
  // TODO: replace with POST /api/session
  case "auth":
    $login = Utils::postString('user');
    $pass = Utils::postPass('pass');
    try {
      if ($auth->checkLogin($login, $pass)) {
        exitWithSuccess();
      } else {
        $auth->exitWithUnauthorized();
      }
    } catch (DatabaseException|ServerException|InvalidInputException $e) {
      exitWithError("Server error");
    }
    break;

  // action: addtrack
  // TODO: replace with POST /api/tracks
  case "addtrack":
    $trackName = Utils::postString('track');
    if (empty($trackName)) {
      exitWithError("Missing required parameter");
    }
    $track = new Entity\Track($auth->user->id, $trackName);
    try {
      $mapperTrack->create($track);
    } catch (DatabaseException $e) {
      exitWithError("Server error");
    }
    // return track id
    exitWithSuccess(['trackid' => $track->id]);
    break;

  // action: addposition
  // TODO: replace with POST /api/tracks/{id}/positions
  case "addpos":
    $latitude = Utils::postFloat('lat');
    $longitude = Utils::postFloat('lon');
    $timestamp = Utils::postInt('time');
    $altitude = Utils::postFloat('altitude');
    $speed = Utils::postFloat('speed');
    $bearing = Utils::postFloat('bearing');
    $accuracy = Utils::postInt('accuracy');
    $provider = Utils::postString('provider');
    $comment = Utils::postString('comment');
    $fileUpload = Utils::requestFile('image');
    $trackId = Utils::postInt('trackid');

    if (!is_float($latitude) || !is_float($longitude) || !is_int($timestamp) || !is_int($trackId)) {
      exitWithError("Missing required parameter");
    }

    $image = null;
    if ($fileUpload) {
      try {
        $image = $fileUpload->add($trackId);
      } catch (InvalidInputException|ServerException $e) {
        // save position anyway
      }
    }
    $position = new Entity\Position(
      timestamp: $timestamp,
      userId: $auth->user->id,
      trackId: $trackId,
      latitude: $latitude,
      longitude: $longitude
    );
    $position->altitude = $altitude;
    $position->speed = $speed;
    $position->bearing = $bearing;
    $position->accuracy = $accuracy;
    $position->provider = $provider;
    $position->comment = $comment;
    $position->image = $image;

    try {
      $mapperPosition->create($position);
    } catch (DatabaseException $e) {
      exitWithError("Server error");
    }

    exitWithSuccess();
    break;

  default:
    exitWithError("Unknown command");
    break;
}

?>
