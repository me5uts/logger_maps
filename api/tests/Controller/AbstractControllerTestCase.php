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


  protected function mapperMock(string $class): MockObject {
    return $this->mapperFactory->getMapper($class);
  }

  /**
   * @param Response $actualResponse
   * @return void
   */
  protected static function assertResponseSuccessNoPayload(Response $actualResponse): void {
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals(Response::success(), $actualResponse);
  }

  /**
   * @param Response $actualResponse
   * @param mixed $expectedPayload
   * @return void
   */
  protected static function assertResponseSuccessWithPayload(Response $actualResponse, mixed $expectedPayload): void {
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals(Response::success($expectedPayload), $actualResponse);
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
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals(Response::created($expectedPayload), $actualResponse);
  }

  protected static function assertResponseException(Response $actualResponse, \Exception $expectedException): void {
    self::assertInstanceOf(Response::class, $actualResponse);
    self::assertEquals(Response::exception($expectedException), $actualResponse);
  }

  /**
   * @param Response $response
   * @param string $message
   * @return void
   */
  protected static function assertResponseUnprocessableError(Response $response, string $message): void {
    self::assertInstanceOf(Response::class, $response);
    self::assertEquals(Response::unprocessableError($message), $response);
  }

  /**
   * @param Response $response
   * @param string $message
   * @return void
   */
  protected static function assertResponseInternalServerError(Response $response, string $message): void {
    self::assertInstanceOf(Response::class, $response);
    self::assertEquals(Response::internalServerError($message), $response);
  }

  /**
   * @param Response $response
   * @param string $message
   * @return void
   */
  protected static function assertResponseConflictError(Response $response, string $message): void {
    self::assertInstanceOf(Response::class, $response);
    self::assertEquals(Response::conflictError($message), $response);
  }
}
