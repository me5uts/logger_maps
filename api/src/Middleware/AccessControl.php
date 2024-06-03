<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Middleware;

use uLogger\Component\Auth;
use uLogger\Component\Request;
use uLogger\Component\Route;
use uLogger\Entity\Position;
use uLogger\Entity\Track;

class AccessControl implements Middleware {

  private Auth $auth;
  private Request $request;
  private Route $route;

  public function __construct(Auth $auth) {
    $this->auth = $auth;
  }

  public function run(Request $request, Route $route): bool {
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
      return false;
    }

    foreach ($policies as $policy) {
      switch ($policy) {
        case Auth::ALLOW_ALL:
          return true;

        case Auth::ALLOW_AUTHORIZED:
          return $this->auth->isAuthenticated();

        case Auth::ALLOW_ADMIN:
          return $this->auth->isAdmin();

        case Auth::ALLOW_OWNER:
          return $this->isResourceOwner();
      }
    }

    return false;
  }

  private function isResourceOwner(): bool {
    if (str_contains($this->route->getPath(), '/api/users/{id}')) {
      if ($this->auth->isAuthenticated() && $this->auth->user->id === $this->request->getParams()['id']) {
        return true;
      }
    }
    elseif (str_contains($this->route->getPath(), '/api/tracks/{id}')) {
      $trackId = $this->request->getParams()['id'];
      $track = new Track($trackId);
      if ($this->auth->isAuthenticated() && $track->isValid && $this->auth->user->id === $track->userId) {
        return true;
      }
    }
    elseif (str_contains($this->route->getPath(), '/api/positions/{id}')) {
      $positionId = $this->request->getParams()['id'];
      $position = new Position($positionId);
      if ($this->auth->isAuthenticated() && $position->isValid && $this->auth->user->id === $position->userId) {
        return true;
      }
    }

    return false;
  }

}
