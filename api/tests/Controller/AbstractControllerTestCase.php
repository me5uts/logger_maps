<?php
declare(strict_types = 1);
/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Exception\ServerException;
use uLogger\Mapper\MapperFactory;


abstract class AbstractControllerTestCase extends TestCase {

  protected MockObject|MapperFactory $mapperFactory;
  protected MockObject|Session $session;
  protected MockObject|Entity\Config $config;

  protected array $mappers = [];

  /**
   * @throws Exception
   */
  protected function setUp(): void {
    $this->mapperFactory = $this->createMock(MapperFactory::class);
    $this->mapperFactory
      ->method('getMapper')
      ->willReturnCallback(function ($name) {
        if (!isset($this->mappers[$name])) {
          $this->mappers[$name] = $this->createMock($name);
        }
        return $this->mappers[$name];
      });
    $this->session = $this->createMock(Session::class);
    $this->config = $this->createMock(Entity\Config::class);
  }


  /**
   * @throws ServerException
   */
  protected function mapperMock(string $class): MockObject {
    return $this->mapperFactory->getMapper($class);
  }

  /**
   * @param Response $actualResponse
   * @return void
   */
  protected static function assertResponseSuccessNoPayload(Response $actualResponse): void {
    self::assertResponse(Response::success(), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param mixed $expectedPayload
   * @return void
   */
  protected static function assertResponseSuccessWithPayload(Response $actualResponse, mixed $expectedPayload): void {
    self::assertResponse(Response::success($expectedPayload), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @return void
   */
  protected static function assertResponseSuccessWithAnyPayload(Response $actualResponse): void {
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals(Response::success([])->getCode(), $actualResponse->getCode());
  }

  /**
   * @param Response $actualResponse
   * @param mixed $expectedPayload
   * @return void
   */
  protected static function assertResponseCreatedWithPayload(Response $actualResponse, mixed $expectedPayload): void {
    self::assertResponse(Response::created($expectedPayload), $actualResponse);
  }

  protected static function assertResponseException(Response $actualResponse, \Exception $expectedException): void {
    self::assertResponse(Response::exception($expectedException), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param string $expectedMessage
   * @return void
   */
  protected static function assertResponseUnprocessableError(Response $actualResponse, string $expectedMessage): void {
    self::assertResponse(Response::unprocessableError($expectedMessage), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param string $expectedMessage
   * @return void
   */
  protected static function assertResponseInternalServerError(Response $actualResponse, string $expectedMessage): void {
    self::assertResponse(Response::internalServerError($expectedMessage), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param string $expectedMessage
   * @return void
   */
  protected static function assertResponseConflictError(Response $actualResponse, string $expectedMessage): void {
    self::assertResponse(Response::conflictError($expectedMessage), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @return void
   */
  protected static function assertResponseNotAuthorized(Response $actualResponse): void {
    self::assertResponse(Response::notAuthorized(), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param Response $expectedResponse
   * @return void
   */
  protected static function assertResponse(Response $expectedResponse, Response $actualResponse): void {
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals($expectedResponse, $actualResponse);
  }
}
