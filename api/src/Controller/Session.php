<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Auth;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity\Config;

class Session {
  private Auth $auth;
  private Config $config;

  /**
   * @param Auth $auth
   * @param Config $config
   */
  public function __construct(Auth $auth, Config $config) {
    $this->auth = $auth;
    $this->config = $config;
  }

  /**
   * End current session
   * DELETE /session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/session', [ Auth::ACCESS_ALL => [ Auth::ALLOW_AUTHORIZED ] ])]
  public function logOut(): Response {
    $this->auth->logOut();
    return Response::success();
  }

  /**
   * Start session for user and password
   * POST /session (log in; payload: {login, password}; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
   * @param string $login
   * @param string $password
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/session', [ Auth::ACCESS_ALL => [ Auth::ALLOW_ALL ] ])]
  public function logIn(string $login, string $password): Response {
    if ($this->auth->checkLogin($login, $password) === false) {
      return Response::notAuthorized();
    }
    return $this->ResponseWithSessionData();
  }

  /**
   * Get session data
   * GET /session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/session', [ Auth::ACCESS_ALL => [ Auth::ALLOW_AUTHORIZED ] ])]
  public function check(): Response {
    if ($this->config->requireAuthentication && !$this->auth->isAuthenticated()) {
      return Response::notAuthorized();
    }
    return $this->ResponseWithSessionData();
  }

  private function ResponseWithSessionData(): Response {
    $result = [
      "isAuthenticated" => $this->auth->isAuthenticated()
    ];
    if ($this->auth->isAuthenticated()) {
      $result["isAdmin"] = $this->auth->isAdmin();
      $result["userId"] = $this->auth->user->id;
      $result["userLogin"] = $this->auth->user->login;
    }
    return Response::success($result);
  }
}
