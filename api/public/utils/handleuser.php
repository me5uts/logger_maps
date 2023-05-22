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

$action = Utils::postString('action');
$login = Utils::postString('login');
$pass = Utils::postPass('pass');
$admin = Utils::postBool('admin', false);

$lang = (new Lang($config))->getStrings();

if (($auth->user && $auth->user->login === $login) || empty($action) || empty($login) || !$auth->isAuthenticated() || !$auth->isAdmin()) {
  Utils::exitWithError($lang["servererror"]);
}

if ($admin && !$auth->isAdmin()) {
  Utils::exitWithError($lang["notauthorized"]);
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
