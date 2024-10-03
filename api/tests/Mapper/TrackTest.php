<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

use PHPUnit\Framework\MockObject\Exception;
use uLogger\Component\Db;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;
use uLogger\Mapper\MapperFactory;
use uLogger\Tests\Mapper\AbstractMapperTestCase;
use uLogger\Tests\Mapper\Fixtures;

class TrackTest extends AbstractMapperTestCase {

  protected Mapper\Track $mapper;

  /**
   * @throws ServerException
   * @throws DatabaseException
   */
  protected function setUp(): void {
    AbstractMapperTestCase::setUp();

    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);
  }

  /**
   * @throws ServerException
   * @throws DatabaseException
   * @throws NotFoundException
   */
  public function testFetchSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $trackId = 1;

    $track = $this->mapper->fetch($trackId);

    $record = $this->getRecordById(Fixtures\Tracks::class, $trackId);
    $this->assertTrackEquals($record, $track);
  }

  /**
   * @throws ServerException
   * @throws DatabaseException
   * @throws NotFoundException
   */
  public function testFetchNotFound() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $trackId = 111; // non existing

    $this->expectException(NotFoundException::class);

    $this->mapper->fetch($trackId);
  }

  /**
   * @throws ServerException
   * @throws DatabaseException
   * @throws NotFoundException
   * @throws Exception
   */
  public function testFetchDatabaseException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $trackId = 1;

    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('query')
      ->willThrowException(new PDOException());
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);

    $this->expectException(DatabaseException::class);

    $this->mapper->fetch($trackId);
  }

  /**
   * @throws ServerException
   * @throws DatabaseException
   */
  public function testFetchByUserSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $userId = 1;

    $tracks = $this->mapper->fetchByUser($userId);

    $record1 = $this->getRecordById(Fixtures\Tracks::class, 3);
    $record2 = $this->getRecordById(Fixtures\Tracks::class, 1);
    $this->assertTrackEquals($record1, $tracks[0]);
    $this->assertTrackEquals($record2, $tracks[1]);
  }

  /**
   * @throws DatabaseException
   */
  public function testCreateSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $recordCount = count((new Fixtures\Tracks())->records);
    $this->assertTableRowCount($recordCount, 'tracks');

    $this->mapper->create($track);

    $this->assertTableRowCount($recordCount + 1, 'tracks');
    $this->assertTableRowEquals([
      'id' => $track->id,
      'user_id' => $track->userId,
      'name' => $track->name,
      'comment' => $track->comment
    ], 'tracks', $recordCount + 1);
  }

  /**
   * @throws DatabaseException
   * @throws Exception
   * @throws ServerException
   */
  public function testCreateDatabaseException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('prepare')
      ->willThrowException(new PDOException());
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);

    $this->expectException(DatabaseException::class);

    $this->mapper->create($track);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function testUpdateSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = 1;
    $recordCount = count((new Fixtures\Tracks())->records);
    $this->assertTableRowCount($recordCount, 'tracks');

    $this->mapper->update($track);

    $this->assertTableRowCount($recordCount, 'tracks');
    $this->assertTableRowEquals([
      'id' => $track->id,
      'user_id' => $track->userId,
      'name' => $track->name,
      'comment' => $track->comment
    ], 'tracks', $track->id);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function testUpdateInvalidInputException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = null; // missing id

    $this->expectException(InvalidInputException::class);

    $this->mapper->update($track);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function testUpdateEmptyCommentSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', '');
    $track->id = 1;
    $recordCount = count((new Fixtures\Tracks())->records);
    $this->assertTableRowCount($recordCount, 'tracks');

    $this->mapper->update($track);

    $this->assertTableRowCount($recordCount, 'tracks');
    $this->assertNull($track->comment);
    $this->assertTableRowEquals([
      'id' => $track->id,
      'user_id' => $track->userId,
      'name' => $track->name,
      'comment' => $track->comment
    ], 'tracks', $track->id);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function testUpdateNotFoundException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = 111; // not existing id

    $this->expectException(NotFoundException::class);

    $this->mapper->update($track);
  }

  /**
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   * @throws Exception
   * @throws ServerException
   */
  public function testUpdateDatabaseException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = 1;
    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('prepare')
      ->willThrowException(new PDOException());
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);

    $this->expectException(DatabaseException::class);

    $this->mapper->update($track);
  }

  /**
   * @throws DatabaseException
   */
  public function testDeleteSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = 1;
    $recordCount = count((new Fixtures\Tracks())->records);
    $this->assertTableRowCount($recordCount, 'tracks');

    $this->mapper->delete($track);

    $this->assertTableRowCount($recordCount - 1, 'tracks');
    $this->assertTableRowNotExists('tracks', $track->id);
  }

  /**
   * @throws DatabaseException
   * @throws Exception
   * @throws ServerException
   */
  public function testDeleteDatabaseException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $track = new Entity\Track(1, 'test track', 'comment');
    $track->id = 1;
    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('prepare')
      ->willThrowException(new PDOException());
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);

    $this->expectException(DatabaseException::class);

    $this->mapper->delete($track);
  }

  /**
   * @throws DatabaseException
   */
  public function testDeleteAllSuccess() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class ]);

    $userId = 1;
    $recordCount = count((new Fixtures\Tracks())->records);
    $this->assertTableRowCount($recordCount, 'tracks');

    $this->mapper->deleteAll($userId);

    $this->assertTableRowCount(1, 'tracks');
    $this->assertTableRowExists('tracks', 2);
  }

  /**
   * @throws DatabaseException
   * @throws Exception
   * @throws ServerException
   */
  public function testDeleteAllDatabaseException() {
    $this->insertFixtures([ Fixtures\Users::class, Fixtures\Tracks::class, Fixtures\Positions::class ]);

    $userId = 1;
    $this->db = $this->createMock(Db::class);
    $this->db->expects($this->once())
      ->method('prepare')
      ->willThrowException(new PDOException());
    $this->mapperFactory = new MapperFactory($this->db);
    $this->mapper = $this->mapperFactory->getMapper(Mapper\Track::class);

    $this->expectException(DatabaseException::class);

    $this->mapper->deleteAll($userId);
  }

  /**
   * @param array|null $record
   * @param Entity\Track $track
   * @return void
   */
  private function assertTrackEquals(?array $record, Entity\Track $track): void {
    $this->assertEquals($record['id'], $track->id);
    $this->assertEquals($record['user_id'], $track->userId);
    $this->assertEquals($record['name'], $track->name);
    $this->assertEquals($record['comment'], $track->comment);
  }

}
