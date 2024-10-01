<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Controller;

use Exception;
use PHPUnit\Framework\MockObject\Exception as MockException;
use uLogger\Component\FileUpload;
use uLogger\Component\Response;
use uLogger\Controller;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Mapper;

class PositionTest extends AbstractControllerTestCase
{
  private Controller\Position $controller;

  protected function setUp(): void {
    parent::setUp();

    $this->controller = new Controller\Position($this->mapperFactory, $this->session, $this->config);
  }

  // getAll

  /**
   * @throws ServerException
   */
  public function testGetAllSuccessWithAfterIdAndPrevPosition() {
    $trackId = 1;
    $afterId = 1;
    $positions = [
      new Entity\Position(1000, 1, $trackId, 0, 0),
      new Entity\Position(1001, 1, $trackId, 0, 0)
    ];

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('findAll')
      ->with($trackId, $afterId)
      ->willReturn($positions);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($afterId)
      ->willReturn(new Entity\Position(999, 1, $trackId, 0, 0));

    $response = $this->controller->getAll($trackId, $afterId);

    $this->assertResponseSuccessWithPayload($response, $positions);
  }

  /**
   * @throws ServerException
   */
  public function testGetAllSuccessWithAfterIdAndNoPrevPosition() {
    $trackId = 1;
    $afterId = 1;
    $positions = [
      new Entity\Position(1000, 1, $trackId, 0, 0),
      new Entity\Position(1001, 1, $trackId, 0, 0)
    ];

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('findAll')
      ->with($trackId, $afterId)
      ->willReturn($positions);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($afterId)
      ->willThrowException(new NotFoundException());

    $response = $this->controller->getAll($trackId, $afterId);

    $this->assertResponseSuccessWithPayload($response, $positions);
  }

  /**
   * @throws ServerException
   */
  public function testGetAllSuccessWithoutAfterId() {
    $trackId = 1;
    $afterId = null;
    $positions = [
      new Entity\Position(1000, 1, $trackId, 0, 0),
      new Entity\Position(1001, 1, $trackId, 0, 0)
    ];

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('findAll')
      ->with($trackId, $afterId)
      ->willReturn($positions);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->never())
      ->method('fetch')
      ->with($afterId);

    $response = $this->controller->getAll($trackId, $afterId);

    $this->assertResponseSuccessWithPayload($response, $positions);
  }

  /**
   * @throws ServerException
   */
  public function testGetAllException() {
    $trackId = 1;
    $afterId = null;

    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('findAll')
      ->with($trackId, $afterId)
      ->willThrowException($exception);

    $response = $this->controller->getAll($trackId, $afterId);

    $this->assertResponseException($response, $exception);
  }

  // update

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testUpdateSuccess() {
    $newPosition = new Entity\Position(1000, 1, 1, 0, 0);
    $newPosition->id = 1;
    $newPosition->comment = 'new comment';
    $currentPosition = new Entity\Position(1000, 1, 1, 0, 0);
    $currentPosition->id = 1;
    $currentPosition->comment = 'old comment';

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($newPosition->id)
      ->willReturn($currentPosition);
    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('update')
      ->with($currentPosition);

    $response = $this->controller->update($newPosition->id, $newPosition);

    $this->assertResponseSuccessNoPayload($response);
    $this->assertEquals($currentPosition->comment, $newPosition->comment);
  }

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testUpdateException() {
    $newPosition = new Entity\Position(1000, 1, 1, 0, 0);
    $newPosition->id = 1;
    $newPosition->comment = 'new comment';
    $currentPosition = new Entity\Position(1000, 1, 1, 0, 0);
    $currentPosition->id = 1;
    $currentPosition->comment = 'old comment';
    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($newPosition->id)
      ->willReturn($currentPosition);
    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('update')
      ->with($currentPosition)
      ->willThrowException($exception);

    $response = $this->controller->update($newPosition->id, $newPosition);

    $this->assertResponseException($response, $exception);
  }

  public function testUpdateWrongPositionId() {
    $newPosition = new Entity\Position(1000, 1, 1, 0, 0);
    $newPosition->id = 1;
    $wrongPositionId = 2;

    $response = $this->controller->update($wrongPositionId, $newPosition);

    $this->assertResponseUnprocessableError($response, 'Wrong position id');
  }

  // add

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testAddPositionWithImageSuccess() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $image = $this->createMock(FileUpload::class);

    // Simulate the image upload
    $image->expects($this->once())
      ->method('add')
      ->with($position->trackId)
      ->willReturn('path/to/image.jpg');

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('create')
      ->with($position);

    $response = $this->controller->addPosition($position, $image);

    $this->assertResponseCreatedWithPayload($response, $position);
    $this->assertEquals('path/to/image.jpg', $position->image);
  }

  /**
   * @throws ServerException
   */
  public function testAddPositionWithoutImageSuccess() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('create')
      ->with($position);

    $response = $this->controller->addPosition($position);

    $this->assertResponseCreatedWithPayload($response, $position);
    $this->assertNull($position->image);
  }

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testAddPositionException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $image = $this->createMock(FileUpload::class);
    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('create')
      ->with($position)
      ->willThrowException($exception);

    $response = $this->controller->addPosition($position, $image);

    $this->assertResponseException($response, $exception);
  }

  // delete

  /**
   * @throws ServerException
   */
  public function testDeletePositionSuccess() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('delete')
      ->with($position);

    $response = $this->controller->delete($position->id);

    $this->assertResponseSuccessNoPayload($response);
  }

  /**
   * @throws ServerException
   */
  public function testDeletePositionNotFoundException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $exception = new NotFoundException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willThrowException($exception);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->never())
      ->method('delete');

    $response = $this->controller->delete($position->id);

    $this->assertResponseException($response, $exception);
  }

  /**
   * @throws ServerException
   */
  public function testDeletePositionDatabaseException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('delete')
      ->with($position)
      ->willThrowException($exception);

    $response = $this->controller->delete($position->id);

    $this->assertResponseException($response, $exception);
  }

  // addImage

  /**
   * @throws MockException
   * @throws ServerException
   * @throws ServerException
   */
  public function testAddImagePositionSuccess() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $imageUpload = $this->createMock(FileUpload::class);
    $image = 'test_image';

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('setImage')
      ->with($position, $imageUpload)
      ->willReturnCallback(function(Entity\Position $position) use ($image) {
        $position->image = $image;
      });

    $response = $this->controller->addImage($position->id, $imageUpload);

    $this->assertResponseCreatedWithPayload($response, [ 'image' => $position->image ]);
  }


  /**
   * @throws MockException
   * @throws ServerException
   * @throws ServerException
   */
  public function testAddImagePositionNotFoundException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $imageUpload = $this->createMock(FileUpload::class);
    $exception = new NotFoundException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willThrowException($exception);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->never())
      ->method('setImage');

    $response = $this->controller->addImage($position->id, $imageUpload);

    $this->assertResponseException($response, $exception);
  }

  /**
   * @throws MockException
   * @throws ServerException
   * @throws ServerException
   */
  public function testAddImagePositionDatabaseException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $imageUpload = $this->createMock(FileUpload::class);
    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('setImage')
      ->with($position, $imageUpload)
      ->willThrowException($exception);

    $response = $this->controller->addImage($position->id, $imageUpload);

    $this->assertResponseException($response, $exception);
  }

  // deleteImage

  /**
   * @throws Exception
   */
  public function testDeleteImagePositionSuccess() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('removeImage')
      ->with($position);

    $response = $this->controller->deleteImage($position->id);

    $this->assertResponseSuccessNoPayload($response);
  }

  /**
   * @throws MockException
   * @throws ServerException
   * @throws ServerException
   */
  public function testDeleteImagePositionNotFoundException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $exception = new NotFoundException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willThrowException($exception);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->never())
      ->method('removeImage');

    $response = $this->controller->deleteImage($position->id);

    $this->assertResponseException($response, $exception);
  }

  /**
   * @throws MockException
   * @throws ServerException
   * @throws ServerException
   */
  public function testDeleteImagePositionDatabaseException() {
    $trackId = 1;
    $position = new Entity\Position(1000, 1, $trackId, 0, 0);
    $position->id = 1;
    $exception = new DatabaseException();

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('removeImage')
      ->with($position)
      ->willThrowException($exception);

    $response = $this->controller->deleteImage($position->id);

    $this->assertResponseException($response, $exception);
  }

  // getImage

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testGetImageSuccess() {
    $position = new Entity\Position(1000, 1, 1, 0, 0);
    $position->id = 1;
    $position->image = 'image.jpg';

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $fileMock = $this->createMock(Entity\File::class);
    $fileMock->setContent('content');
    $fileMock->setMimeType('image/jpeg');
    $fileMock->setFileName($position->image);

    $this->controller = $this->getMockBuilder(Controller\Position::class)
      ->setConstructorArgs([$this->mapperFactory, $this->session, $this->config])
      ->onlyMethods([ 'getFile' ])
      ->getMock();

    // Return the mocked File object when creating a File instance.
    $this->controller->method('getFile')
      ->with($position->image)
      ->willReturn($fileMock);

    $response = $this->controller->getImage($position->id);

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(Response::file($fileMock), $response);
  }

  /**
   * @throws MockException
   * @throws ServerException
   */
  public function testGetImageNotFound() {

    $position = new Entity\Position(1000, 1, 1, 0, 0);
    $position->id = 1;
    $position->image = null;

    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($position->id)
      ->willReturn($position);

    $response = $this->controller->getImage($position->id);

    $this->assertResponseException($response, new NotFoundException());
  }

  /**
   * @throws ServerException
   */
  public function testGetImageException() {
    $positionId = 123;
    $exception = new ServerException('Database error');

    // Simulate an exception in the mapper.
    $this->mapperMock(Mapper\Position::class)
      ->expects($this->once())
      ->method('fetch')
      ->with($positionId)
      ->willThrowException($exception);

    $response = $this->controller->getImage($positionId);

    $this->assertResponseException($response, $exception);
  }

}
