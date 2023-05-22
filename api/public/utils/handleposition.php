<?php
declare(strict_types = 1);
/* μlogger
 *
 * Copyright(C) 2020 Bartek Fabiszewski (www.fabiszewski.net)
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
use uLogger\Entity\Position;
use uLogger\Helper\Utils;

$auth = new Auth();

$action = Utils::postString("action");
$positionId = Utils::postInt("posid");
$comment = Utils::postString("comment");

$config = Config::getInstance();
$lang = (new Lang($config))->getStrings();

if (empty($action) || empty($positionId)) {
  Utils::exitWithError($lang["servererror"]);
}
$position = new Position($positionId);
if (!$position->isValid || !$auth->hasReadWriteAccess($position->userId)) {
  Utils::exitWithError($lang["notauthorized"]);
}

$data = null;

switch ($action) {

  case "update":
    $position->comment = $comment;
    if ($position->update() === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  case "delete":
    if ($position->delete() === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  case "imageadd":
    try {
      $fileMeta = Utils::requireFile("image");
      if ($position->setImage($fileMeta) === false) {
        Utils::exitWithError($lang["servererror"]);
      }
      $data = [ "image" => $position->image ];
    } catch (ErrorException $ee) {
      $message = $lang["servererror"];
      $message .= ": {$ee->getMessage()}";
      Utils::exitWithError($message);
    } catch (Exception $e) {
      $message = $lang["iuploadfailure"];
      $message .= ": {$e->getMessage()}";
      Utils::exitWithError($message);
    }
    break;

  case "imagedel":
    if ($position->removeImage() === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  default:
    Utils::exitWithError($lang["servererror"]);
}

Utils::exitWithSuccess($data);

?>
