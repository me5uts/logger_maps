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
   * Start session for user and password
   * POST /session (log in; payload: {login, password}; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
   * @param string $login
   * @param string $password
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_POST, '/api/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_ALL ] ])]
  public function logIn(string $login, string $password): Response {
    try {
      if ($this->session->checkLogin($login, $password) === false) {
        return Response::notAuthorized();
      }
    } catch (Exception $e) {
      return $this->exceptionResponse($e);
    }
    return $this->ResponseWithSessionData();
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
    return $this->ResponseWithSessionData();
  }

  private function ResponseWithSessionData(): Response {
    $result = [
      "isAuthenticated" => $this->session->isAuthenticated()
    ];
    if ($this->session->isAuthenticated()) {
      $result["isAdmin"] = $this->session->isAdmin();
      $result["userId"] = $this->session->user->id;
      $result["userLogin"] = $this->session->user->login;
    }
    return Response::success($result);
  }
}
