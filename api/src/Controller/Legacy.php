<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Controller;

use ReflectionException;
use uLogger\Attribute\Route;
use uLogger\Component;
use uLogger\Component\Request;
use uLogger\Component\Response;
use uLogger\Component\Session;
use uLogger\Entity;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Utils;
use uLogger\Mapper\MapperFactory;

class Legacy extends AbstractController {
  private Component\Router $router;

  public function __construct(MapperFactory $mapperFactory, Session $session, Entity\Config $config, Component\Router $router) {
    $this->router = $router;
    parent::__construct($mapperFactory, $session, $config);
  }

  /**
   * @param string $action
   * @return Response
   * @noinspection PhpUnused
   */
  #[Route(Request::METHOD_POST, '/client/index.php', [ Component\Session::ACCESS_ALL => [ Component\Session::ALLOW_ALL ] ])]
  public function client(string $action): Response {
    try {
      $response = match ($action) {
        'auth' => $this->session(),
        'addtrack' => $this->track(),
        'addpos' => $this->position(),
        default => throw new NotFoundException(),
      };
    } catch (NotFoundException|ServerException|ReflectionException|InvalidInputException $e) {
      $response = Response::success([ 'error' => true, 'message' => $e->getMessage() ]);
    }
    return $response;
  }

  /**
   * @return Response
   * @throws InvalidInputException
   * @throws ReflectionException
   * @throws ServerException
   */
  private function session(): Response {
    $request = new Request(
      path: '/api/client/session',
      method: Request::METHOD_POST,
      payload: [
        'login' => Utils::postString('user'),
        'password' => Utils::postPass('pass'),
      ]
    );
    return $this->rewriteResponse($this->router->dispatch($request));
  }

  /**
   * @return Response
   * @throws InvalidInputException
   * @throws ReflectionException
   * @throws ServerException
   */
  private function track(): Response {
    $request = new Request(
      path: '/api/client/tracks',
      method: Request::METHOD_POST,
      payload: [
        'name' => Utils::postString('track'),
        'userId' => $this->session->user->id ?? null,
      ]
    );
    $response = $this->router->dispatch($request);

    if ($response->getCode() === 201) {
      $payload = $response->getPayload();
      $trackId = $payload->id ?? null;
      return Response::success([ 'error' => false, 'trackid' => $trackId ]);
    }
    return $this->rewriteResponse($response);
  }

  /**
   * @return Response
   * @throws InvalidInputException
   * @throws ReflectionException
   * @throws ServerException
   */
  private function position(): Response {
    $request = new Request(
      path: '/api/client/positions',
      method: Request::METHOD_POST,
      payload: [
        'latitude' => Utils::postFloat('lat'),
        'longitude' => Utils::postFloat('lon'),
        'timestamp' => Utils::postInt('time'),
        'altitude' => Utils::postFloat('altitude'),
        'speed' => Utils::postFloat('speed'),
        'bearing' => Utils::postFloat('bearing'),
        'accuracy' => Utils::postInt('accuracy'),
        'provider' => Utils::postString('provider'),
        'comment' => Utils::postString('comment'),
        'trackId' => Utils::postInt('trackid'),
        'userId' => $this->session->user->id ?? null,
      ]
    );
    return $this->rewriteResponse($this->router->dispatch($request));
  }

  private function rewriteResponse(Response $response): Response {
    if ((int) ($response->getCode() / 100) === 2) {
      return Response::success([ 'error' => false ]);
    } elseif ($response->getCode() === Response::CODE_4_UNAUTHORIZED) {
      return $response;
    } else {
      return Response::success($response->getPayload());
    }
  }

}
