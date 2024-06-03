<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

require_once('../../vendor/autoload.php');

use uLogger\Component\Auth;
use uLogger\Entity\Config;
use uLogger\Entity\User;

$auth = new Auth();
$config = Config::getInstance();

$usersArr = [];
if ($auth->hasPublicReadAccess() || $auth->isAdmin()) {
  $usersArr = User::getAll();
} else if ($auth->isAuthenticated()) {
  $usersArr = [ $auth->user ];
}

$result = [];
if ($usersArr === false) {
  $result = [ "error" => true ];
} else if (!empty($usersArr)) {
  foreach ($usersArr as $user) {
    // only load admin status on admin user request
    $isAdmin = $auth->isAdmin() ? $user->isAdmin : null;
    $result[] = [ "id" => $user->id, "login" => $user->login, "isAdmin" => $isAdmin ];
  }
}
header("Content-type: application/json");
echo json_encode($result);
?>
