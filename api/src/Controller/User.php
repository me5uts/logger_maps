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
use uLogger\Entity;

class User {
  private Auth $auth;
  private Entity\Config $config;

  public function __construct(Auth $auth, Entity\Config $config) {
    $this->auth = $auth;
    $this->config = $config;
  }

  /**
   * GET /api/users (get all users; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_ADMIN ]
  ])]
  public function getAll(): Response {
    $result = [];
    $users = Entity\User::getAll();
    if ($users === false) {
      $result = [ "error" => true ];
    } elseif (!empty($users)) {
      foreach ($users as $user) {
        // only load admin status on admin user request
        $isAdmin = $this->auth->isAdmin() ? $user->isAdmin : null;
        $result[] = [ "id" => $user->id, "login" => $user->login, "isAdmin" => $isAdmin ];
      }
    }
    return Response::success($result);
  }

  /**
   * GET /users/{id}/tracks (get user tracks; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/{userId}/tracks', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
  ])]
  public function getTracks(int $userId): Response {
    $tracks = Entity\Track::getAll($userId);

    $result = [];
    if ($tracks === false) {
      $result = [ "error" => true ];
    } elseif (!empty($tracks)) {
      foreach ($tracks as $track) {
        $result[] = [ "id" => $track->id, "name" => $track->name ];
      }
    }
    return Response::success($result);
  }

  /**
   * GET /users/{id}/position (get user last position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/{userId}/position', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
  ])]
  public function getPosition(int $userId): Response {
    $position = Entity\Position::getLast($userId);
    if ($position->isValid) {
      $result = Entity\Position::getArray($position);
    } else {
      $result = [ "error" => true ];
    }

    return Response::success($result);
  }

  /**
   * GET /users/position (get all users last positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
   * @return Response
   */
  #[Route(Request::METHOD_GET, '/api/users/position', [
    Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
    Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
    Auth::ACCESS_PRIVATE => [ Auth::ALLOW_ADMIN ]
  ])]
  public function getAllPosition(): Response {
    $positions = Entity\Position::getLastAllUsers();
    $result = [];
    if ($positions === false) {
      $result = [ "error" => true ];
    } elseif (!empty($positions)) {

      foreach ($positions as $position) {
        $result[] = Entity\Position::getArray($position);
      }
    }
    return Response::success($result);
  }

  /**
   * PUT /api/users/{id} (for admin to edit other users; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param int $userId
   * @param Entity\User $user
   * @return Response
   */
  #[Route(Request::METHOD_PUT, '/api/users/{userId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_ADMIN ] ])]
  public function update(int $userId, Entity\User $user): Response {
    if ($userId !== $user->id) {
      return Response::unprocessableError("Wrong user id");
    }

    $currentUser = new Entity\User($userId);
    if (!$currentUser->isValid) {
      return Response::notFound();
    }

    if ($userId === $this->auth->user->id) {
      return Response::unprocessableError("selfeditwarn");
    }
    if ($currentUser->setAdmin($user->isAdmin) === false) {
      return Response::internalServerError("Setting admin failed");
    }
    if (!empty($user->password) && (!$this->config->validPassStrength($user->password) || $currentUser->setPass($user->password) === false)) {
      return Response::internalServerError("Setting pass failed");
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
  #[Route(Request::METHOD_PUT, '/api/users/{userId}/password', [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER ] ])]
  public function updatePassword(int $userId, string $password, string $oldPassword): Response {

    if ($this->auth->user->id !== $userId) {
      return Response::notAuthorized();
    }

    if (!$this->config->validPassStrength($password)) {
      return Response::unprocessableError("passstrengthwarn");
    }
    if (!$this->auth->user->validPassword($oldPassword)) {
      return Response::unprocessableError("oldpassinvalid");
    }

    if ($this->auth->user->setPass($password) === false) {
      return Response::internalServerError("Setting pass failed");
    }
    $this->auth->updateSession();

    return Response::success();
  }

  /**
   * POST /api/users (new user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param Entity\User $user
   * @return Response
   */
  #[Route(Request::METHOD_POST, '/api/users', [ Auth::ACCESS_ALL => [ Auth::ALLOW_ADMIN ] ])]
  public function add(Entity\User $user): Response {

    if ((new Entity\User($user->login))->isValid) {
      return Response::unprocessableError("userexists");
    }

    if (!$this->config->validPassStrength($user->password)) {
      return Response::unprocessableError("passstrengthwarn");
    }

    $userId = Entity\User::add($user->login, $user->password, $user->isAdmin);

    if ($userId === false) {
      return Response::internalServerError("User add failed");
    }

    return Response::success([ 'id' => $userId ]);
  }

  /**
   * DELETE /api/users/{id} (delete user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
   * @param int $userId
   * @return Response
   */
  #[Route(Request::METHOD_DELETE, '/api/users/{userId}', [ Auth::ACCESS_ALL => [ Auth::ALLOW_ADMIN ] ])]
  public function delete(int $userId): Response {

    if ($userId === $this->auth->user->id) {
      return Response::unprocessableError("selfeditwarn");
    }

    $user = new Entity\User($userId);
    if (!$user->isValid) {
      return Response::notFound();
    }

    if ($user->delete() === false) {
      return Response::internalServerError("Delete failed");
    }

    return Response::success();

  }


}
