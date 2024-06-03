<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Entity\User;
use uLogger\Helper\Utils;

/**
 * Authentication
 */
class Auth {

  /** @var bool Is user authenticated */
  private $isAuthenticated = false;
  /** @var null|User */
  public $user;

  public function __construct() {
    $this->sessionStart();

    $user = User::getFromSession();
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
   * @param User $user
   * @return void
   */
  private function setAuthenticated(User $user): void {
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
      $user = new User($login);
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
   * Log out
   *
   * @return void
   */
  public function logOut(): void {
    $this->sessionEnd();
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
    Utils::exitWithError($message);
  }

  /**
   * Redirect browser and exit
   *
   * @param string $path Redirect URL path (without leading slash)
   * @return void
   */
  public function exitWithRedirect(string $path = ""): void {
    $location = Utils::getBaseUrl() . $path;
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
    return $this->hasReadWriteAccess($ownerId) || $this->hasPublicReadAccess();
  }

  /**
   * Check session user has RO access to all resources

   * @return bool True if has access
   */
  public function hasPublicReadAccess(): bool {
    return ($this->isAuthenticated() || !Config::getInstance()->requireAuthentication) && Config::getInstance()->publicTracks;
  }

}
