<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Middleware;

use Exception;
use uLogger\Component\Auth;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity\Position;
use uLogger\Entity\Track;

class AccessControl implements MiddlewareInterface {

  private Auth $auth;
  private Request $request;
  private Route $route;

  public function __construct(Auth $auth) {
    $this->auth = $auth;
  }

  public function run(Request $request, Route $route): Response {
    $this->request = $request;
    $this->route = $route;
    $accessType = $this->auth->getAccessType();
    $routeAuth = $route->getAuth();
    $policies = null;
    if (isset($routeAuth[$accessType])) {
      $policies = $routeAuth[$accessType];
    } elseif (isset($routeAuth[Auth::ACCESS_ALL])) {
      $policies = $routeAuth[Auth::ACCESS_ALL];
    }

    if ($policies === null) {
      return Response::internalServerError('No policies found for route');
    }

    foreach ($policies as $policy) {
      switch ($policy) {
        case Auth::ALLOW_ALL:
          return Response::continue();

        case Auth::ALLOW_AUTHORIZED:
          if ($this->auth->isAuthenticated()) {
            return Response::continue();
          }
          break;

        case Auth::ALLOW_ADMIN:
          if ($this->auth->isAdmin()) {
            return Response::continue();
          }
          break;

        case Auth::ALLOW_OWNER:
          try {
            if ($this->isResourceOwner()) {
              return Response::continue();
            }
          } catch (Exception $e) {
            return Response::internalServerError($e->getMessage());
          }
          break;
      }
    }

    return Response::notAuthorized();
  }

  /**
   * @throws Exception
   */
  private function isResourceOwner(): bool {
    if (!$this->auth->isAuthenticated()) {
      return false;
    }
    $checks = 0;
    if (str_contains($this->route->getPath(), '{userId}')) {
      $checks++;
      if ($this->auth->user->id !== (int) $this->request->getParams()['userId']) {
        return false;
      }
    }
    if (str_contains($this->route->getPath(), '{trackId}')) {
      $checks++;
      $trackId = (int) $this->request->getParams()['trackId'];
      $track = new Track($trackId);
      if (!$track->isValid || $this->auth->user->id !== $track->userId) {
        return false;
      }
    }
    if (str_contains($this->route->getPath(), '{positionId}')) {
      $checks++;
      $positionId = (int) $this->request->getParams()['positionId'];
      $position = new Position($positionId);
      if (!$position->isValid || $this->auth->user->id !== $position->userId) {
        return false;
      }
    }
    if ($checks === 0) {
      throw new Exception('Route misconfigured: no private resource found');
    }
    return true;
  }

}
