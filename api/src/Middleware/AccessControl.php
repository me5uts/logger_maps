<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Middleware;

use Exception;
use uLogger\Attribute\Route;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
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

  /**
   * @param MapperFactory $mapperFactory
   * @param Session $session
   */
  public function __construct(MapperFactory $mapperFactory, Session $session) {
    $this->session = $session;
    $this->mapperFactory = $mapperFactory;
  }

  /**
   * @param Request $request
   * @param Route $route
   * @return Response
   */
  public function run(Request $request, Route $route): Response {
    $this->request = $request;
    $this->route = $route;
    $accessType = $this->session->getAccessType();
    $routeAuth = $route->getAuth();
    $policies = null;
    if (isset($routeAuth[$accessType])) {
      $policies = $routeAuth[$accessType];
    } else if (isset($routeAuth[Session::ACCESS_ALL])) {
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
    if ($this->request->hasPayload()) {
      // operation on new track
      if ($this->request->hasPreparedArgument(Entity\Track::class)) {
        $checks++;
        $track = $this->request->getPreparedArgument(Entity\Track::class);
        if (!$this->session->isSessionUser($track->userId)) {
          return false;
        }
      }
      // operation on new position
      if ($this->request->hasPreparedArgument(Entity\Position::class)) {
        $checks++;
        $position = $this->request->getPreparedArgument(Entity\Position::class);
        if (!$this->session->isSessionUser($position->userId)) {
          return false;
        }
        if (!$this->isFeatureOwner($position->trackId, Mapper\Track::class)) {
          return false;
        }
      }
    }
    // operation on existing user
    if (str_contains($this->route->getPath(), '{userId}')) {
      $checks++;
      if (!$this->session->isSessionUser((int) $this->request->getParams()['userId'])) {
        return false;
      }
    }
    // operation on existing track
    if (str_contains($this->route->getPath(), '{trackId}')) {
      $checks++;
      $trackId = (int) $this->request->getParams()['trackId'];
      if (!$this->isFeatureOwner($trackId, Mapper\Track::class)) {
        return false;
      }
    }
    // operation on existing position
    if (str_contains($this->route->getPath(), '{positionId}')) {
      $checks++;
      $positionId = (int) $this->request->getParams()['positionId'];
      if (!$this->isFeatureOwner($positionId, Mapper\Position::class)) {
        return false;
      }
    }

    if ($checks === 0) {
      throw new ServerException('Route misconfigured: no private resource found');
    }
    return true;
  }

  /**
   * @param int $id
   * @param class-string $className
   * @return bool
   * @throws ServerException
   * @throws DatabaseException
   */
  private function isFeatureOwner(int $id, string $className): bool {
    if ($className !== Mapper\Position::class && $className !== Mapper\Track::class) {
      throw new ServerException("Wrong argument $className in feature owner check");
    }
    /** @var Mapper\Position|Mapper\Track $mapper */
    $mapper = $this->mapperFactory->getMapper($className);
    try {
      $feature = $mapper->fetch($id);
      if ($this->session->user->id !== $feature->userId) {
        return false;
      }
    } catch (NotFoundException) {
      return false;
    }
    return true;
  }

}
