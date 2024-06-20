<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Middleware;

use Exception;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Component\Session;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class AccessControl implements MiddlewareInterface {

  private Session $session;
  private Request $request;
  private Route $route;
  private MapperFactory $mapperFactory;

  public function __construct(MapperFactory $mapperFactory, Session $session) {
    $this->session = $session;
    $this->mapperFactory = $mapperFactory;
  }

  public function run(Request $request, Route $route): Response {
    $this->request = $request;
    $this->route = $route;
    $accessType = $this->session->getAccessType();
    $routeAuth = $route->getAuth();
    $policies = null;
    if (isset($routeAuth[$accessType])) {
      $policies = $routeAuth[$accessType];
    } elseif (isset($routeAuth[Session::ACCESS_ALL])) {
      $policies = $routeAuth[Session::ACCESS_ALL];
    }

    if ($policies === null) {
      return Response::internalServerError('No policies found for route');
    }

    foreach ($policies as $policy) {
      switch ($policy) {
        case Session::ALLOW_ALL:
          return Response::continue();

        case Session::ALLOW_AUTHORIZED:
          if ($this->session->isAuthenticated()) {
            return Response::continue();
          }
          break;

        case Session::ALLOW_ADMIN:
          if ($this->session->isAdmin()) {
            return Response::continue();
          }
          break;

        case Session::ALLOW_OWNER:
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
   * @return bool
   * @throws DatabaseException
   * @throws ServerException
   */
  private function isResourceOwner(): bool {
    if (!$this->session->isAuthenticated()) {
      return false;
    }
    $checks = 0;
    if (str_contains($this->route->getPath(), '{userId}')) {
      $checks++;
      if ($this->session->user->id !== (int) $this->request->getParams()['userId']) {
        return false;
      }
    }
    if (str_contains($this->route->getPath(), '{trackId}')) {
      $checks++;
      $trackId = (int) $this->request->getParams()['trackId'];
      /** @var Mapper\Track $trackMapper */
      $trackMapper = $this->mapperFactory->getMapper(Mapper\Track::class);
      try {
        $track = $trackMapper->fetch($trackId);
        if ($this->session->user->id !== $track->userId) {
          return false;
        }
      } catch (NotFoundException) {
        return false;
      }
    }
    if (str_contains($this->route->getPath(), '{positionId}')) {
      $checks++;
      $positionId = (int) $this->request->getParams()['positionId'];
      /** @var Mapper\Position $positionMapper */
      $positionMapper = $this->mapperFactory->getMapper(Mapper\Position::class);
      try {
        $position = $positionMapper->fetch($positionId);
        if ($this->session->user->id !== $position->userId) {
          return false;
        }
      } catch (NotFoundException) {
        return false;
      }
    }
    if ($checks === 0) {
      throw new ServerException('Route misconfigured: no private resource found');
    }
    return true;
  }

}
