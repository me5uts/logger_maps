<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Tests\Controller;

use PHPUnit\Framework\MockObject\Exception;
use uLogger\Component\FileUpload;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Router;
use uLogger\Controller;
use uLogger\Entity;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;

class LegacyTest extends AbstractControllerTestCase
{
  private Controller\Legacy $controller;

  private Router $router;

  protected function setUp(): void {
    parent::setUp();
    $this->router = $this->createMock(Router::class);
    $this->controller = new Controller\Legacy($this->mapperFactory, $this->session, $this->config, $this->router);
  }

  public function testClientUnknownAction() {

    $legacyResponse = $this->controller->client('unknown');

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => true, 'message' => 'Error ' . get_class(new NotFoundException()) ]);
  }

  // session

  /**
   * @throws Exception
   */
  public function testClientSessionSuccess() {

    $user = 'test_user';
    $pass = 'test_pass';

    $response = Response::created([
      'isAuthenticated' => true,
      'user' => 'test'
    ]);
    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($response, $pass, $user) {
        $this->assertEquals($user, $request->getPayload()['login']);
        $this->assertEquals($pass, $request->getPayload()['password']);
        return $response;
      });

    $legacyResponse = $this->controller->client('auth', ...[ 'user' => $user, 'pass' => $pass ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => false ]);
  }

  /**
   * @throws Exception
   */
  public function testClientSessionNotAuthorized() {

    $user = 'test_user';
    $pass = 'test_pass';

    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($pass, $user) {
        $this->assertEquals($user, $request->getPayload()['login']);
        $this->assertEquals($pass, $request->getPayload()['password']);
        return Response::notAuthorized();
      });

    $legacyResponse = $this->controller->client('auth', ...[ 'user' => $user, 'pass' => $pass ]);

    $this->assertResponseNotAuthorized($legacyResponse);
  }

  /**
   * @throws Exception
   */
  public function testClientSessionException() {

    $user = 'test_user';
    $pass = 'test_pass';

    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($pass, $user) {
        $this->assertEquals($user, $request->getPayload()['login']);
        $this->assertEquals($pass, $request->getPayload()['password']);
        throw new ServerException();
      });

    $legacyResponse = $this->controller->client('auth', ...[ 'user' => $user, 'pass' => $pass ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => true, 'message' => 'Error ' . get_class(new ServerException()) ]);
  }

  // track

  /**
   * @throws Exception
   */
  public function testClientTrackSuccess() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;

    $response = Response::created($track);
    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($response, $track, $user) {
        $this->assertEquals($track->name, $request->getPayload()['name']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        return $response;
      });

    $legacyResponse = $this->controller->client('addtrack', ...[ 'track' => $track->name ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => false, 'trackid' => 10 ]);
  }

  /**
   * @throws Exception
   */
  public function testClientTrackUnauthorized() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    // missing session user
    $this->session->user = null;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;

    $this->router
      ->expects($this->never())
      ->method('dispatch');

    $legacyResponse = $this->controller->client('addtrack', ...[ 'track' => $track->name ]);

    $this->assertResponseNotAuthorized($legacyResponse);
  }

  /**
   * @throws Exception
   */
  public function testClientTrackException() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $exception = new ServerException();

    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($track, $user, $exception) {
        $this->assertEquals($track->name, $request->getPayload()['name']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        throw $exception;
      });

    $legacyResponse = $this->controller->client('addtrack', ...[ 'track' => $track->name ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => true, 'message' => 'Error ' . get_class($exception) ]);
  }

  /**
   * @throws Exception
   */
  public function testClientTrackResponseNotFound() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $response = Response::notFound();

    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($track, $user, $response) {
        $this->assertEquals($track->name, $request->getPayload()['name']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        return $response;
      });

    $legacyResponse = $this->controller->client('addtrack', ...[ 'track' => $track->name ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => true, 'message' => 'Error 404' ]);
  }

  /**
   * @throws Exception
   */
  public function testClientTrackResponseNotAuthorized() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $response = Response::notAuthorized();

    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($track, $user, $response) {
        $this->assertEquals($track->name, $request->getPayload()['name']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        return $response;
      });

    $legacyResponse = $this->controller->client('addtrack', ...[ 'track' => $track->name ]);

    $this->assertResponseNotAuthorized($legacyResponse);
  }

  // position

  /**
   * @throws Exception
   */
  public function testClientPositionSuccess() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $position = new Entity\Position(1727783514, $user->id, $track->id, 10, 20);
    $position->id = 123;
    $imageFile = $this->createMock(FileUpload::class);

    $response = Response::created($position);
    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($response, $position, $user, $imageFile) {
        $this->assertEquals($position->latitude, $request->getPayload()['latitude']);
        $this->assertEquals($position->longitude, $request->getPayload()['longitude']);
        $this->assertEquals($position->timestamp, $request->getPayload()['timestamp']);
        $this->assertEquals($position->trackId, $request->getPayload()['trackId']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        $this->assertEquals($imageFile, $request->getUpload('image'));
        return $response;
      });

    $legacyResponse = $this->controller->client('addpos', ...[
      'lat' => $position->latitude,
      'lon' => $position->longitude,
      'time' => $position->timestamp,
      'trackid' => $track->id,
      'image' => $imageFile
    ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => false ]);
  }

  /**
   * @throws Exception
   */
  public function testClientPositionUnauthorized() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    // missing session user
    $this->session->user = null;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $position = new Entity\Position(1727783514, $user->id, $track->id, 10, 20);
    $position->id = 123;
    $imageFile = $this->createMock(FileUpload::class);

    $this->router
      ->expects($this->never())
      ->method('dispatch');

    $legacyResponse = $this->controller->client('addpos', ...[
      'lat' => $position->latitude,
      'lon' => $position->longitude,
      'time' => $position->timestamp,
      'trackid' => $track->id,
      'image' => $imageFile
    ]);

    $this->assertResponseNotAuthorized($legacyResponse);
  }

  /**
   * @throws Exception
   */
  public function testClientPositionException() {

    $user = new Entity\User('test_user');
    $user->id = 1;
    $this->session->user = $user;
    $track = new Entity\Track($user->id, 'track_name');
    $track->id = 10;
    $position = new Entity\Position(1727783514, $user->id, $track->id, 10, 20);
    $position->id = 123;
    $imageFile = $this->createMock(FileUpload::class);

    $exception = new ServerException();
    $this->router
      ->expects($this->once())
      ->method('dispatch')
      ->willReturnCallback(function (Request $request) use ($exception, $position, $user, $imageFile) {
        $this->assertEquals($position->latitude, $request->getPayload()['latitude']);
        $this->assertEquals($position->longitude, $request->getPayload()['longitude']);
        $this->assertEquals($position->timestamp, $request->getPayload()['timestamp']);
        $this->assertEquals($position->trackId, $request->getPayload()['trackId']);
        $this->assertEquals($user->id, $request->getPayload()['userId']);
        $this->assertEquals($imageFile, $request->getUpload('image'));
        throw $exception;
      });

    $legacyResponse = $this->controller->client('addpos', ...[
      'lat' => $position->latitude,
      'lon' => $position->longitude,
      'time' => $position->timestamp,
      'trackid' => $track->id,
      'image' => $imageFile
    ]);

    $this->assertResponseSuccessWithPayload($legacyResponse, [ 'error' => true, 'message' => 'Error ' . get_class($exception) ]);
  }

}
