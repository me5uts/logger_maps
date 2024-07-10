<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use Exception;
use uLogger\Attribute\Route;
use uLogger\Component;
use uLogger\Component\Request;
use uLogger\Component\Response;

class Session extends AbstractController {

  /**
   * End current session
   * DELETE /session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_DELETE, '/api/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_AUTHORIZED ] ])]
  public function logOut(): Response {
    $this->session->logOut();
    return Response::success();
  }

  /**
   * Start session for user with login and password
   * POST /session (log in; payload: {login, password}; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
   * @param string $login
   * @param string $password
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_POST, '/api/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_ALL ] ])]
  #[Route(Request::METHOD_POST, '/api/client/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_ALL ] ])]
  public function logIn(string $login, string $password): Response {
    try {
      if ($this->session->checkLogin($login, $password) === false) {
        return Response::notAuthorized();
      }
    } catch (Exception $e) {
      return Response::exception($e);
    }
    return Response::created($this->sessionDataResult());
  }

  /**
   * Get session data
   * GET /session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_GET, '/api/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_AUTHORIZED ] ])]
  public function check(): Response {
    if ($this->config->requireAuthentication && !$this->session->isAuthenticated()) {
      return Response::notAuthorized();
    }
    return Response::success($this->sessionDataResult());
  }

  private function sessionDataResult(): array {
    $result = [
      "isAuthenticated" => $this->session->isAuthenticated()
    ];
    if ($this->session->isAuthenticated()) {
      $result["user"] = $this->session->user;
    }
    return $result;
  }
}
