<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use uLogger\Component\Session;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Route;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class User {
  /** @var Mapper\User */
  private Mapper\User $mapperUser;
  /** @var Mapper\Position */
  private Mapper\Position $mapperPosition;
  /** @var Mapper\Track */
  private Mapper\Track $mapperTrack;

  public function __construct(
    private MapperFactory $mapperFactory,
    private Session       $session,
    private Entity\Config $config
  ) {
    $this->mapperUser = $this->mapperFactory->getMapper(Mapper\User::class);
    $this->mapperPosition = $this->mapperFactory->getMapper(Mapper\Position::class);
    $this->mapperTrack = $this->mapperFactory->getMapper(Mapper\Track::class);
  }

  /**
   * GET /api/users (get all users; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_ADMIN ]
  ])]
  public function getAll(): Response {
    try {
      $users = $this->mapperUser->fetchAll();
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success($users);
  }

  /**
   * GET /users/{id}/tracks (get user tracks; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/{userId}/tracks', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function getTracks(int $userId): Response {

    try {
      $tracks = $this->mapperTrack->fetchByUser($userId);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success($tracks);
  }

  /**
   * GET /users/{id}/position (get user last position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/{userId}/position', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_OWNER, Session::ALLOW_ADMIN ]
  ])]
  public function getPosition(int $userId): Response {
    try {
      $position = $this->mapperPosition->fetchLast($userId);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success($position);
  }

  /**
   * GET /users/position (get all users last positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/position', [
    Session::ACCESS_OPEN => [ Session::ALLOW_ALL ],
    Session::ACCESS_PUBLIC => [ Session::ALLOW_AUTHORIZED ],
    Session::ACCESS_PRIVATE => [ Session::ALLOW_ADMIN ]
  ])]
  public function getAllPosition(): Response {
    $positions = [];
    try {
      $positions = $this->mapperPosition->fetchLastAllUsers();
    } catch (NotFoundException) {
      /* ignored */
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success($positions);
  }

  /**
   * PUT /api/users/{id} (for admin to edit other users; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param int $userId
   * @param Entity\User $user
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/users/{userId}', [ Session::ACCESS_ALL => [ Session::ALLOW_ADMIN ] ])]
  public function update(int $userId, Entity\User $user): Response {
    $password = $user->password;
    $isAdmin = $user->isAdmin;

    if ($userId !== $user->id) {
      return Response::unprocessableError("Wrong user id");
    }

    try {
      $currentUser = $this->mapperUser->fetch($userId);
      if ($userId === $this->session->user->id) {
        return Response::unprocessableError("selfeditwarn");
      }
      $currentUser->isAdmin = $isAdmin;
      $this->mapperUser->updateIsAdmin($currentUser);

      if (!empty($password)) {
        if (!$this->config->validPassStrength($password)) {
          return Response::internalServerError("Setting pass failed");
        }
        $currentUser->password = $password;
        $this->mapperUser->updatePassword($currentUser);

      }
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (NotFoundException) {
      return Response::notFound();
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    }

    return Response::success();
  }

  /**
   * PUT /api/users/{id}/password (password update; access: OPEN-OWNER, PUBLIC-OWNER, PRIVATE-OWNER)
   * @param int $userId
   * @param string $password
   * @param string $oldPassword
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/users/{userId}/password', [ Session::ACCESS_ALL => [ Session::ALLOW_OWNER ] ])]
  public function updatePassword(int $userId, string $password, string $oldPassword): Response {

    if ($this->session->user->id !== $userId) {
      return Response::notAuthorized();
    }

    if (!$this->config->validPassStrength($password)) {
      return Response::unprocessableError("passstrengthwarn");
    }
    if (!$this->session->user->validPassword($oldPassword)) {
      return Response::unprocessableError("oldpassinvalid");
    }

    $this->session->user->password = $password;
    try {
      $this->mapperUser->updatePassword($this->session->user);
      $this->session->updateSession();
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    }

    return Response::success();
  }

  /**
   * POST /api/users (new user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param Entity\User $user
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/users', [ Session::ACCESS_ALL => [ Session::ALLOW_ADMIN ] ])]
  public function add(Entity\User $user): Response {

    try {
      try {
        $this->mapperUser->fetchByLogin($user->login);
        return Response::conflictError("userexists");
      } catch (NotFoundException) { /* ignored */ }

      if (!$this->config->validPassStrength($user->password)) {
        return Response::unprocessableError("passstrengthwarn");
      }
      $this->mapperUser->create($user);

    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    } catch (InvalidInputException $e) {
      return Response::unprocessableError($e->getMessage());
    }

    return Response::created($user);
  }

  /**
   * DELETE /api/users/{id} (delete user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/users/{userId}', [ Session::ACCESS_ALL => [ Session::ALLOW_ADMIN ] ])]
  public function delete(int $userId): Response {

    if ($userId === $this->session->user->id) {
      return Response::unprocessableError("selfeditwarn");
    }

    try {
      $this->mapperPosition->deleteAll($userId);
      $this->mapperTrack->deleteAll($userId);
      $this->mapperUser->delete($userId);
    } catch (DatabaseException $e) {
      return Response::databaseError($e->getMessage());
    } catch (ServerException $e) {
      return Response::internalServerError($e->getMessage());
    }

    return Response::success();
  }

}
