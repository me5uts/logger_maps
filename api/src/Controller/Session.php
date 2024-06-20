<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\ServerException;

class Session {
  private Component\Session $session;
  private Entity\Config $config;

  /**
   * @param Component\Session $session
   * @param Entity\Config $config
   */
  public function __construct(Component\Session $session, Entity\Config $config) {
    $this->session = $session;
    $this->config = $config;
  }

  /**
   * End current session
   * DELETE /session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
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
   */
  #[Route(Request::METHOD_POST, '/api/session', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_ALL ] ])]
  public function logIn(string $login, string $password): Response {
    try {
      if ($this->session->checkLogin($login, $password) === false) {
        return Response::notAuthorized();
      }
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }
    return $this->ResponseWithSessionData();
  }

  /**
   * Get session data
   * GET /session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
   * @return Response
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
