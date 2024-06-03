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
use uLogger\Helper\Utils;

$auth = new Auth();

$action = Utils::postString("action");
$trackId = Utils::postInt("trackid");
$trackName = Utils::postString("trackname");

$config = Config::getInstance();
$lang = (new Lang($config))->getStrings();

if (empty($action) || empty($trackId)) {
  Utils::exitWithError($lang["servererror"]);
}
$track = new Track($trackId);
if (!$track->isValid) {
  Utils::exitWithError($lang["servererror"]);
}
if (($action === "getmeta" && !$auth->hasReadAccess($track->userId)) ||
  ($action !== "getmeta" && !$auth->hasReadWriteAccess($track->userId))) {
  Utils::exitWithError($lang["notauthorized"]);
}

$result = null;

switch ($action) {

  case "update":
    if (empty($trackName) || $track->update($trackName) === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  case "delete":
    if ($track->delete() === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  case "getmeta":
    $result = [
      "id" => $track->id,
      "name" => $track->name,
      "userId" => $track->userId,
      "comment" => $track->comment
    ];
    break;

  default:
    Utils::exitWithError($lang["servererror"]);
}

Utils::exitWithSuccess($result);

?>
