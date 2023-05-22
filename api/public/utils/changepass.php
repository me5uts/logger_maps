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
