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

require_once('../vendor/autoload.php');

use uLogger\Controller\Auth;
use uLogger\Controller\Config;
use uLogger\Helper\Utils;

$route = Utils::postString('route');
$method = Utils::postString('method');

$config = Config::getInstance();
$auth = new Auth();

// check session route
if ($route === 'session') {
  if ($method === 'get' && $config->requireAuthentication && !$auth->isAuthenticated()) {
    $auth->exitWithUnauthorized();
  }
  elseif ($method === 'post') {
    $login = Utils::postString('user');
    $pass = Utils::postPass('pass');
    if ($auth->checkLogin($login, $pass) === false) {
      $auth->exitWithUnauthorized();
    }
  }
  $result = [
    "isAdmin" => $auth->isAdmin(),
    "isAuthenticated" => $auth->isAuthenticated()
  ];
  if ($auth->isAuthenticated()) {
    $result["userId"] = $auth->user->id;
    $result["userLogin"] = $auth->user->login;
  }
  Utils::exitWithSuccess($result);
}

if ($config->requireAuthentication && !$auth->isAuthenticated()) {
  $auth->exitWithUnauthorized();
}

switch ($route) {
  case 'session':
    if ($method === 'delete') {
      $auth->logOut();
    }


}

Utils::exitWithSuccess();

// Routes
/*
/config
 GET - get configuration
 POST - save configuration

/session
 GET - get session data
 POST - log in
 DELETE - log out
/users
/users/{id}
/tracks
/tracks/{id}
/positions
/positions/{id}
/locale
*/

?>
