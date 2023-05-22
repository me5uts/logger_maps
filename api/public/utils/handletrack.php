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
use uLogger\Controller\Config;
use uLogger\Controller\Lang;
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
