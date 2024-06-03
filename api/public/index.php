<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
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
  if ($method === 'delete') {
    $auth->logOut();
    Utils::exitWithSuccess();
  }
  elseif ($method === 'get' && $config->requireAuthentication && !$auth->isAuthenticated()) {
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
