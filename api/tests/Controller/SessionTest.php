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
use uLogger\Exception\ServerException;
use uLogger\Mapper;

class SessionTest extends AbstractControllerTestCase
{
  private Controller\Config $controller;

  protected function setUp(): void {
    parent::setUp();
    $this->controller = new Controller\Config($this->mapperFactory, $this->session, $this->config);
  }

  public function testGetConfigSuccess() {
    $response = $this->controller->get();

    $this->assertResponseSuccessWithPayload($response, $this->config);
  }

  public function testUpdateConfigSuccess() {
    $newConfig = new Entity\Config();

    $this->mapperMock(Mapper\Config::class)
      ->expects($this->once())
      ->method('update')
      ->with($newConfig);

    $this->config
      ->expects($this->once())
      ->method('setFromConfig')
      ->with($newConfig);

    // Call the `update` method.
    $response = $this->controller->update($newConfig);

    // Assert: Ensure the response is successful.
    $this->assertResponseSuccessNoPayload($response);

  }

  public function testUpdateConfigWithNoAuthSetsPublicTracks() {
    $newConfig = new Entity\Config();
    $newConfig->requireAuthentication = false;
    $newConfig->publicTracks = false;

    // Expect the update method to be called once and return true.
    $this->mapperMock(Mapper\Config::class)
      ->expects($this->once())
      ->method('update')
      ->with($newConfig);

    // Call the `update` method.
    $response = $this->controller->update($newConfig);

    // Assert: The response should be successful.
    $this->assertResponseSuccessNoPayload($response);

    // Assert: `publicTracks` should have been set to `true` due to `requireAuthentication` being `false`.
    $this->assertTrue($newConfig->publicTracks);
  }

  public function testUpdateConfigException() {
    $newConfig = new Entity\Config();
    $exception = new ServerException('server error');

    // Mock the mapper to return `false` (update failed).
    $this->mapperMock(Mapper\Config::class)
      ->expects($this->once())
      ->method('update')
      ->with($newConfig)
      ->willThrowException($exception);

    // Call the `update` method.
    $response = $this->controller->update($newConfig);

    // Assert: The response should be an internal server error.
    $this->assertResponseException($response, $exception);
  }
}
