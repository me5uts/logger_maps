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
use uLogger\Entity\User;
use uLogger\Helper\Utils;

$auth = new Auth();
$config = Config::getInstance();
$lang = (new Lang($config))->getStrings();

if (!$auth->isAuthenticated()) {
  $auth->exitWithUnauthorized($lang["notauthorized"]);
}

$login = Utils::postString('login');
$oldpass = Utils::postPass('oldpass');
$pass = Utils::postPass('pass');

if (empty($pass)) {
  Utils::exitWithError($lang["passempty"]);
}
if (!$config->validPassStrength($pass)) {
  Utils::exitWithError($lang["passstrengthwarn"]);
}
if (empty($login)) {
  Utils::exitWithError($lang["loginempty"]);
}
if ($auth->user->login === $login) {
  // current user
  $passUser = $auth->user;
  if (is_null($oldpass) || !$passUser->validPassword($oldpass)) {
    Utils::exitWithError($lang["oldpassinvalid"]);
  }
} else if ($auth->isAdmin()) {
  // different user, only admin
  $passUser = new User($login);
  if (!$passUser->isValid) {
    Utils::exitWithError($lang["userunknown"]);
  }
} else {
  Utils::exitWithError($lang["notauthorized"]);
}
if ($passUser->setPass($pass) === false) {
  Utils::exitWithError($lang["servererror"]);
}
$auth->updateSession();
Utils::exitWithSuccess();

?>
