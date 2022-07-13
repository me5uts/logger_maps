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

if (!defined('ROOT_DIR')) { define('ROOT_DIR', dirname(__DIR__)); }
require_once(ROOT_DIR . "/helpers/user.php");
require_once(ROOT_DIR . "/helpers/utils.php");
if (!defined('BASE_URL')) { define('BASE_URL', uUtils::getBaseUrl()); }

/**
 * Authentication
 */
class uAuth {

  /** @var bool Is user authenticated */
  private $isAuthenticated = false;
  /** @var null|uUser */
  public $user;

  public function __construct() {
    $this->sessionStart();

    $user = uUser::getFromSession();
    if ($user->isValid) {
      $this->setAuthenticated($user);
    }
  }

  /**
   * Update user instance stored in session
   */
  public function updateSession(): void {
    if ($this->isAuthenticated()) {
      $this->user->storeInSession();
    }
  }

  /**
   * Is user authenticated
   *
   * @return boolean True if authenticated, false otherwise
   */
  public function isAuthenticated(): bool {
    return $this->isAuthenticated;
  }

  /**
   * Is authenticated user admin
   *
   * @return boolean True if admin, false otherwise
   */
  public function isAdmin(): bool {
    return ($this->isAuthenticated && $this->user->isAdmin);
  }

  /**
   * Start php session
   *
   * @return void
   */
  private function sessionStart(): void {
    session_name("ulogger");
    session_start();
  }

  /**
   * Terminate php session
   *
   * @return void
   */
  private function sessionEnd(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies") && isset($_COOKIE[session_name()])) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
      );
    }
    session_destroy();
  }

  /**
   * Clean session variables
   *
   * @return void
   */
  private function sessionCleanup(): void {
    $_SESSION = [];
  }

  /**
   * Mark as authenticated, set user
   *
   * @param uUser $user
   * @return void
   */
  private function setAuthenticated(uUser $user): void {
    $this->isAuthenticated = true;
    $this->user = $user;
  }

  /**
   * Check valid pass for given login
   *
   * @param string $login
   * @param string $pass
   * @return boolean True if valid
   */
  public function checkLogin(string $login, string $pass): bool {
    if (!empty($login) && !empty($pass)) {
      $user = new uUser($login);
      if ($user->isValid && $user->validPassword($pass)) {
        $this->setAuthenticated($user);
        $this->sessionCleanup();
        $user->storeInSession();
        return true;
      }
    }
    return false;
  }

  /**
   * Log out with redirect
   *
   * @param string $path URL path (without leading slash)
   * @return void
   */
  public function logOutWithRedirect(string $path = ""): void {
    $this->sessionEnd();
    $this->exitWithRedirect($path);
  }

  /**
   * Send 401 headers
   *
   * @return void
   */
  public function sendUnauthorizedHeader(): void {
    header('WWW-Authenticate: OAuth realm="users@ulogger"');
    header('HTTP/1.1 401 Unauthorized', true, 401);
  }

  /**
   * Send 401 headers and exit
   *
   * @param string $message
   * @return void
   */
  public function exitWithUnauthorized(string $message = "Unauthorized"): void {
    $this->sendUnauthorizedHeader();
    uUtils::exitWithError($message);
  }

  /**
   * Redirect browser and exit
   *
   * @param string $path Redirect URL path (without leading slash)
   * @return void
   */
  public function exitWithRedirect(string $path = ""): void {
    $location = BASE_URL . $path;
    header("Location: $location");
    exit();
  }

  /**
   * Check session user has RW access to resource owned by given user
   *
   * @param int $ownerId
   * @return bool True if has access
   */
  public function hasReadWriteAccess(int $ownerId): bool {
    return $this->isAuthenticated() && ($this->isAdmin() || $this->user->id === $ownerId);
  }

  /**
   * Check session user has RO access to resource owned by given user
   *
   * @param int $ownerId
   * @return bool True if has access
   */
  public function hasReadAccess(int $ownerId): bool {
    return $this->hasReadWriteAccess($ownerId) || uConfig::getInstance()->publicTracks;
  }

}
