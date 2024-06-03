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
