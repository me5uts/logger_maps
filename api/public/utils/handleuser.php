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
use uLogger\Entity\User;
use uLogger\Helper\Utils;

$auth = new Auth();
$config = Config::getInstance();

$action = Utils::postString('action');
$login = Utils::postString('login');
$pass = Utils::postPass('pass');
$admin = Utils::postBool('admin', false);

$lang = (new Lang($config))->getStrings();

if (($auth->user && $auth->user->login === $login) || !$auth->isAdmin()) {
  Utils::exitWithError($lang["notauthorized"]);
}

if (empty($action) || empty($login)) {
  Utils::exitWithError($lang["servererror"]);
}

$aUser = new User($login);
$data = null;

switch ($action) {
  case 'add':
    if ($aUser->isValid) {
      Utils::exitWithError($lang["userexists"]);
    }
    if (empty($pass) || !$config->validPassStrength($pass) || ($userId = User::add($login, $pass, $admin)) === false) {
      Utils::exitWithError($lang["servererror"]);
    } else {
      $data = [ 'id' => $userId ];
    }
    break;

  case 'update':
    if ($aUser->setAdmin($admin) === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    if (!empty($pass) && (!$config->validPassStrength($pass) || $aUser->setPass($pass) === false)) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  case 'delete':
    if ($aUser->delete() === false) {
      Utils::exitWithError($lang["servererror"]);
    }
    break;

  default:
    Utils::exitWithError($lang["servererror"]);
}

Utils::exitWithSuccess($data);

?>
