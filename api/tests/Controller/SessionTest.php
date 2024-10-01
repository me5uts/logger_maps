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

class SessionTest extends AbstractControllerTestCase
{
  private Controller\Session $controller;

  protected function setUp(): void {
    parent::setUp();
    $this->controller = new Controller\Session($this->mapperFactory, $this->session, $this->config);
  }

  // log out

  public function testLogOutSuccess() {

    $this->session
      ->expects($this->once())
      ->method('logOut');

    $response = $this->controller->logOut();

    $this->assertResponseSuccessNoPayload($response);
  }

  // log in

  public function testLogInSuccess() {

    $login = 'login';
    $password = 'password';
    $this->session->user = new Entity\User($login);

    $this->session
      ->expects($this->once())
      ->method('setAuthenticatedIfValid')
      ->with($login, $password);
    $this->session
      ->expects($this->exactly(2))
      ->method('isAuthenticated')
      ->willReturn(true);

    $response = $this->controller->logIn($login, $password);

    $this->assertResponseCreatedWithPayload($response, [
      'isAuthenticated' => true,
      'user' => $this->session->user
    ]);
  }

  public function testLogInNotAuthorized() {

    $login = 'login';
    $password = 'password';
    $this->session->user = new Entity\User($login);

    $this->session
      ->expects($this->once())
      ->method('setAuthenticatedIfValid')
      ->with($login, $password)
      ->willThrowException(new NotFoundException());

    $response = $this->controller->logIn($login, $password);

    $this->assertResponseNotAuthorized($response);
  }

  public function testLogInException() {

    $login = 'login';
    $password = 'password';
    $exception = new ServerException();
    $this->session->user = new Entity\User($login);

    $this->session
      ->expects($this->once())
      ->method('setAuthenticatedIfValid')
      ->with($login, $password)
      ->willThrowException($exception);

    $response = $this->controller->logIn($login, $password);

    $this->assertResponseException($response, $exception);
  }

// check

  public function testCheckWithRequireAuthenticationSuccess() {

    $login = 'login';
    $this->session->user = new Entity\User($login);
    $this->config->requireAuthentication = true;

    $this->session
      ->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(true);

    $response = $this->controller->check();

    $this->assertResponseSuccessWithPayload($response, [
      'isAuthenticated' => true,
      'user' => $this->session->user
    ]);
  }

  public function testCheckWithValidSessionAndWithoutRequireAuthenticationSuccess() {

    $login = 'login';
    $this->session->user = new Entity\User($login);
    $this->config->requireAuthentication = false;

    $this->session
      ->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(true);

    $response = $this->controller->check();

    $this->assertResponseSuccessWithPayload($response, [
      'isAuthenticated' => true,
      'user' => $this->session->user
    ]);
  }

  public function testCheckWithoutValidSessionAndWithoutRequireAuthenticationSuccess() {

    $this->config->requireAuthentication = false;
    $this->session->user = null;

    $this->session
    ->expects($this->any())
    ->method('isAuthenticated')
    ->willReturn(false);

    $response = $this->controller->check();

    $this->assertResponseSuccessWithPayload($response, [
      'isAuthenticated' => false
    ]);
  }

  public function testCheckNotAuthorized() {

    $login = 'login';
    $this->session->user = new Entity\User($login);
    $this->config->requireAuthentication = true;

    $this->session
      ->expects($this->any())
      ->method('isAuthenticated')
      ->willReturn(false);

    $response = $this->controller->check();

    $this->assertResponseNotAuthorized($response);
  }
}
