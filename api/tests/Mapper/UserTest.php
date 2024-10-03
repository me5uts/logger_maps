<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Mapper;

use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\Exception;
use uLogger\Component\Db;
use uLogger\Entity\User;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;

class UserTest extends AbstractMapperTestCase {

  protected Mapper\User $mapper;

  /**
   * @throws ServerException
   * @throws DatabaseException
   */
  protected function setUp(): void {
    parent::setUp();
    unset($_SESSION['user_id']);

    $this->mapper = $this->mapperFactory->getMapper(Mapper\User::class);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function testFetchSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $userId = 1;
    $user = $this->mapper->fetch($userId);

    $record = $this->getRecordById(Fixtures\Users::class, $userId);

    $this->assertEquals($userId, $user->id);
    $this->assertEquals($record['id'], $user->id);
    $this->assertEquals($record['password'], $user->hash);
    $this->assertEquals($record['login'], $user->login);
    $this->assertEquals($record['admin'], $user->isAdmin);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function testFetchNotFound(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $this->expectException(NotFoundException::class);

    $userId = 111; // not existing in fixture
    $this->mapper->fetch($userId);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   * @throws Exception
   */
  public function testFetchDatabaseException(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $userId = 1;

    $this->setUpMockMapperThatThrowsOnExecute();

    $this->expectException(DatabaseException::class);

    $this->mapper->fetch($userId);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function testFetchByLoginSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $login = 'user1';
    $user = $this->mapper->fetchByLogin($login);

    $record = $this->getRecordByKey(Fixtures\Users::class, 'login', $login);

    $this->assertEquals($login, $user->login);
    $this->assertEquals($record['id'], $user->id);
    $this->assertEquals($record['password'], $user->hash);
    $this->assertEquals($record['login'], $user->login);
    $this->assertEquals($record['admin'], $user->isAdmin);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function testFetchByLoginNotFound(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $this->expectException(NotFoundException::class);

    $login = 'user111'; // not existing in fixture
    $this->mapper->fetchByLogin($login);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   */
  public function testFetchAllSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $user = $this->mapper->fetchAll();

    $this->assertTableRowCount(2, 'users');
    $this->assertCount(2, $user);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function testFetchAllNotFound(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $this->expectException(NotFoundException::class);

    $login = 'user111'; // not existing in fixture
    $this->mapper->fetchByLogin($login);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   */
  public function testCreateSuccess(): void {

    $login = 'user1';
    $isAdmin = true;
    $user = new User($login);
    $user->password = 'password';
    $user->isAdmin = $isAdmin;

    $this->assertTableRowCount(0, 'users');

    $this->mapper->create($user);

    $this->assertTableRowCount(1, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $login, 'password' => $user->hash, 'admin' => (int) $isAdmin ], 'users', $user->id);
  }

  /**
   * @throws DatabaseException
   */
  public function testCreateEmptyPassword(): void {
    $login = 'user1';
    $isAdmin = true;
    $user = new User($login);
    $user->isAdmin = $isAdmin;

    $this->expectException(InvalidInputException::class);
    $this->assertTableRowCount(0, 'users');

    $this->mapper->create($user);

    $this->assertTableRowCount(0, 'users');
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws Exception
   * @throws InvalidInputException
   */
  public function testCreateDatabaseException(): void {

    $user = new User('user1');
    $user->password = 'password';

    $this->setUpMockMapperThatThrowsOnExecute();

    $this->expectException(DatabaseException::class);

    $this->mapper->create($user);
  }

  /**
   * @throws DatabaseException
   */
  public function testUpdateIsAdminEnableSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $user = new User('user2');
    $user->id = 2;
    $user->isAdmin = true;

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'admin' => (int) !$user->isAdmin ], 'users', $user->id);

    $this->mapper->updateIsAdmin($user);

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'admin' => (int) $user->isAdmin ], 'users', $user->id);
  }

  /**
   * @throws DatabaseException
   */
  public function testUpdateIsAdminDisableSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $user = new User('user1');
    $user->id = 1;
    $user->isAdmin = false;

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'admin' => (int) !$user->isAdmin ], 'users', $user->id);

    $this->mapper->updateIsAdmin($user);

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'admin' => (int) $user->isAdmin ], 'users', $user->id);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws Exception
   */
  public function testUpdateIsAdminDatabaseException(): void {

    $user = new User('user1');
    $user->password = 'password';

    $this->setUpMockMapperThatThrowsOnExecute();

    $this->expectException(DatabaseException::class);

    $this->mapper->updateIsAdmin($user);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   */
  public function testUpdatePasswordSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $user = new User('user2');
    $user->id = 2;
    $user->isAdmin = false;
    $user->password = 'password';
    $record = $this->getRecordById(Fixtures\Users::class, $user->id);

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'password' => $record['password'], 'admin' => (int) $user->isAdmin ], 'users', $user->id);

    $this->mapper->updatePassword($user);

    $newPassword = $this->getTableRowById('users', $user->id, [ 'password' ]);

    $this->assertTableRowCount(2, 'users');
    $this->assertTableRow([ 'id' => $user->id, 'login' => $user->login, 'admin' => (int) $user->isAdmin ], 'users', $user->id, [ 'id', 'login', 'admin' ]);
    $this->assertNotEquals($newPassword, $record['password']);
  }

  /**
   * @throws DatabaseException
   */
  public function testUpdatePasswordEmptyPassword(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $user = new User('user1');
    $user->id = 1;
    $user->isAdmin = false;

    $this->expectException(InvalidInputException::class);

    $this->mapper->updatePassword($user);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws Exception
   * @throws InvalidInputException
   */
  public function testUpdatePasswordDatabaseException(): void {

    $user = new User('user1');
    $user->password = 'password';

    $this->setUpMockMapperThatThrowsOnExecute();

    $this->expectException(DatabaseException::class);

    $this->mapper->updatePassword($user);
  }

  /**
   * @throws DatabaseException
   */
  public function testDeleteSuccess(): void {
    $this->insertFixtures([ Fixtures\Users::class ]);

    $userId = 1;

    $this->assertTableRowCount(2, 'users');

    $this->mapper->delete($userId);

    $this->assertTableRowCount(1, 'users');
    $this->assertTableRowNotExists('users', $userId);
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws Exception
   */
  public function testDeleteDatabaseException(): void {

    $userId = 1;

    $this->setUpMockMapperThatThrowsOnExecute();

    $this->expectException(DatabaseException::class);

    $this->mapper->delete($userId);
  }

  /**
   * @throws InvalidInputException
   */
  public function testStoreInSessionSuccess(): void {

    $user = new User('user1');
    $user->id = 1;

    $this->assertFalse(isset($_SESSION['user_id']));

    $this->mapper->storeInSession($user);

    $this->assertEquals($user->id, $_SESSION['user_id']);
  }

  /**
   * @throws InvalidInputException
   */
  public function testStoreInSessionException(): void {

    $user = new User('user1');
    $user->id = null;

    $this->expectException(InvalidInputException::class);

    $this->mapper->storeInSession($user);
  }

  /**
   * @throws NotFoundException
   */
  public function testGetFromSessionSuccess(): void {

    $expectedUserId = 123;
    $_SESSION['user_id'] = $expectedUserId;

    $userId = $this->mapper->getFromSession();

    $this->assertEquals($expectedUserId, $userId);
  }

  public function testGetFromSessionException(): void {

    $this->assertFalse(isset($_SESSION['user_id']));

    $this->expectException(NotFoundException::class);

    $this->mapper->getFromSession();
  }




  /**
   * @return void
   * @throws Exception
   * @throws ServerException
   */
  private function setUpMockMapperThatThrowsOnExecute(): void {
    $stmt = $this->createMock(PDOStatement::class);
    $stmt->expects($this->once())
      ->method('execute')
      ->willThrowException(new PDOException());
    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('prepare')
      ->willReturn($stmt);
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\User::class);
  }
}
