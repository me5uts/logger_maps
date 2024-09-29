<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Controller;

use uLogger\Controller;
use uLogger\Entity;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;

class UserTest extends AbstractControllerTest
{
  private Controller\User $controller;

  protected function setUp(): void {
    parent::setUp();

    $this->controller = new Controller\User($this->mapperFactory, $this->session, $this->config);
  }

  // getAll

  public function testGetAllSuccess() {
    $users = [
      new Entity\User('test'),
      new Entity\User('test2')
    ];

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchAll')
      ->willReturn($users);

    $response = $this->controller->getAll();

    $this->assertResponseSuccessWithPayload($response, $users);
  }

  public function testGetAllException() {

    $exception = new ServerException('Database error');

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchAll')
      ->willThrowException($exception);

    $response = $this->controller->getAll();

    $this->assertResponseException($response, $exception);
  }

  // getTracks

  public function testGetTracksSuccess() {
    $userId = 1;
    $tracks = [
      new Entity\Track($userId, 'track 1'),
      new Entity\Track($userId, 'track 2'),
    ];

    $this->mapperMock(Mapper\Track::class)
      ->expects($this->once())
      ->method('fetchByUser')
      ->with($userId)
      ->willReturn($tracks);

    $response = $this->controller->getTracks($userId);

    $this->assertResponseSuccessWithPayload($response, $tracks);
  }

  public function testGetTracksException() {
    $userId = 1;
    $exception = new ServerException('Database error');

    $this->mapperMock(Mapper\Track::class)
      ->expects($this->once())
      ->method('fetchByUser')
      ->with($userId)
      ->willThrowException($exception);

    $response = $this->controller->getTracks($userId);

    $this->assertResponseException($response, $exception);
  }

  // getPosition

  public function testGetPositionSuccess() {
    $userId = 1;
    $position = new Entity\Position(1000, $userId, 1, 0, 0);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetchLast')
      ->with($userId)
      ->willReturn($position);

    $response = $this->controller->getPosition($userId);

    $this->assertResponseSuccessWithPayload($response, $position);
  }

  public function testGetPositionException() {
    $userId = 1;
    $exception = new ServerException('Database error');

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetchLast')
      ->with($userId)
      ->willThrowException($exception);

    $response = $this->controller->getPosition($userId);

    $this->assertResponseException($response, $exception);
  }

  // getAllPosition

  public function testGetAllPositionSuccess() {
    $positions = [
      new Entity\Position(1000, 1, 1, 0, 0),
      new Entity\Position(1000, 2, 1, 0, 0)
    ];

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetchLastAllUsers')
      ->willReturn($positions);

    $response = $this->controller->getAllPosition();

    $this->assertResponseSuccessWithPayload($response, $positions);
  }

  public function testGetAllPositionNotFoundException() {

    // Should return success with empty positions array
    $positions = [];

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetchLastAllUsers')
      ->willThrowException(new NotFoundException());

    $response = $this->controller->getAllPosition();

    $this->assertResponseSuccessWithPayload($response, $positions);
  }

  public function testGetAllPositionGenericException() {

    $exception = new ServerException('Some server error');

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetchLastAllUsers')
      ->willThrowException($exception);

    $response = $this->controller->getAllPosition();

    $this->assertResponseException($response, $exception);
  }

  // update

  public function testUpdateSuccess() {
    $userId = 2;
    $user = new Entity\User('test');
    $user->id = $userId;
    $user->password = 'new_password';
    $user->isAdmin = true;

    $this->session->user = new Entity\User('test');
    // current session user id is different from $userId
    $this->session->user->id = 1;

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($userId)
      ->willReturn($user);

    // Simulate valid password strength
    $this->config
      ->expects($this->once())
      ->method('validPassStrength')
      ->with($user->password)
      ->willReturn(true);

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('updateIsAdmin')
      ->with($user);

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('updatePassword')
      ->with($user);

    $response = $this->controller->update($userId, $user);

    $this->assertResponseSuccessNoPayload($response);
  }

  public function testUpdateUserIdMismatch() {
    $userId = 2;
    $user = new Entity\User('test');
    // mismatch between $userId and $user->id
    $user->id = 3;

    $response = $this->controller->update($userId, $user);

    $this->assertResponseUnprocessableError($response, 'Wrong user id');

  }

  public function testUpdateSelfEdit() {
    $userId = 1;
    $user = new Entity\User('test');
    $user->id = $userId;
    $user->isAdmin = true;

    // current session user id is same as $user
    $this->session->user = $user;

    $response = $this->controller->update($userId, $user);

    $this->assertResponseUnprocessableError($response, 'selfeditwarn');
  }

  public function testUpdateWeakPassword() {
    $userId = 2;
    $user = new Entity\User('test');
    $user->id = $userId;
    $user->password = 'weak_pass';

    $this->session->user = new Entity\User('test');
    // current session user id is different from $userId
    $this->session->user->id = 1;

    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($userId)
      ->willReturn($user);

    // Mock invalid password strength
    $this->config
      ->method('validPassStrength')
      ->with($user->password)
      ->willReturn(false);

    $response = $this->controller->update($userId, $user);

    $this->assertResponseInternalServerError($response, 'Setting pass failed');
  }

  public function testUpdateException() {
    $userId = 2;
    $user = new Entity\User('test');
    $user->id = $userId;
    $user->password = 'strong_password';

    $this->session->user = new Entity\User('test');
    // current session user id is different from $userId
    $this->session->user->id = 1;
    $exception = new ServerException('Database error');

    // Mock the fetch method to throw an exception
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($userId)
      ->willThrowException($exception);

    $response = $this->controller->update($userId, $user);

    $this->assertResponseException($response, $exception);
  }

  // add

  public function testAddSuccess() {
    // Arrange
    $user = new Entity\User('new_user');
    $user->password = 'StrongPassword123';

    // Mocking the mapper to simulate user not found
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchByLogin')
      ->with($user->login)
      ->willThrowException(new NotFoundException());

    // Simulate valid password strength
    $this->config
      ->method('validPassStrength')
      ->with($user->password)
      ->willReturn(true);

    // Expect create to be called with the user entity
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('create')
      ->with($user);

    $response = $this->controller->add($user);

    $this->assertResponseCreatedWithPayload($response, $user);
  }

  public function testAddUserAlreadyExists() {
    $user = new Entity\User('existing_user');
    $user->password = 'StrongPassword123';

    // Mocking the mapper to simulate user already exists
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchByLogin')
      ->with($user->login)
      ->willReturn($user);

    $response = $this->controller->add($user);

    $this->assertResponseConflictError($response, 'userexists');
  }

  public function testAddWeakPassword() {
    $user = new Entity\User('new_user');
    $user->password = 'weak_pass';

    // Mocking the mapper to simulate user not found
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchByLogin')
      ->with($user->login)
      ->willThrowException(new NotFoundException());

    // Simulate invalid password strength
    $this->config
      ->method('validPassStrength')
      ->with($user->password)
      ->willReturn(false);

    $response = $this->controller->add($user);

    $this->assertResponseUnprocessableError($response, 'passstrengthwarn');
  }

  public function testAddException() {
    $user = new Entity\User('new_user');
    $user->password = 'StrongPassword123';
    $exception = new ServerException('Database error');

    // Mocking the mapper to simulate user not found
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('fetchByLogin')
      ->with($user->login)
      ->willThrowException(new NotFoundException());

    // Simulate valid password strength
    $this->config
      ->method('validPassStrength')
      ->with($user->password)
      ->willReturn(true);

    // Simulate an exception during user creation
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('create')
      ->with($user)
      ->willThrowException($exception);

    $response = $this->controller->add($user);

    $this->assertResponseException($response, $exception);
  }

  // delete

  public function testDeleteSuccess() {
    $userId = 5; // Some user to delete
    $currentUserId = 10; // Admin user ID performing the deletion

    // Set the session to mock the current user (admin)
    $this->session->user = new Entity\User('test');
    $this->session->user->id = $currentUserId;
    $this->session->user->isAdmin = true;

    // Expect the delete operations to be called
    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('deleteAll')
      ->with($userId);
    $this->mapperMock(Mapper\Track::class)
      ->expects($this->once())
      ->method('deleteAll')
      ->with($userId);
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('delete')
      ->with($userId);

    $response = $this->controller->delete($userId);

    $this->assertResponseSuccessNoPayload($response);
  }

  public function testDeleteSelfEditException() {
    $userId = 10; // User trying to delete themselves
    $this->session->user = new Entity\User('test');
    $this->session->user->id = $userId; // Current user matches the ID

    $response = $this->controller->delete($userId);

    $this->assertResponseUnprocessableError($response, 'selfeditwarn');
  }

  public function testDeleteMapperException() {
    $userId = 5; // Some user to delete
    $currentUserId = 10; // Admin user ID performing the deletion
    $exception = new ServerException('Database error');

    // Set the session to mock the current user (admin)
    $this->session->user = new Entity\User('admin');
    $this->session->user->id = $currentUserId;
    $this->session->user->isAdmin = true;

    // Simulate an exception during the deletion process
    $this->mapperMock(Mapper\User::class)
      ->expects($this->once())
      ->method('delete')
      ->with($userId)
      ->willThrowException($exception);

    $response = $this->controller->delete($userId);

    $this->assertResponseException($response, $exception);
  }
}
