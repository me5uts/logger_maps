<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

use InvalidArgumentException;
use ReflectionException;
use uLogger\Attribute\Route;
use uLogger\Component;
use uLogger\Controller;
use uLogger\Entity;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Reflection;
use uLogger\Mapper\MapperFactory;
use uLogger\Middleware\MiddlewareInterface;

/**
 * Routes
 *
 * config
 * - R/A (require authorization)
 * - P/A (implies R/A, public access)
 *
 * access types
 * - OPEN (!R/A)
 * - PUBLIC (R/A && P/A)
 * - PRIVATE (R/A && !P/A)
 *
 * access levels
 * - ALL (no restrictions)
 * - AUTHORIZED (authorized users only)
 * - OWNER (resource owner only)
 * - ADMIN (admin only)
 *
 * /api/config
 * ✓ GET /api/config (get configuration; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
 * ✓ PUT /api/config (save configuration; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
 *
 * /api/session
 * ✓ GET /api/session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
 * ✓ POST /api/session (log in; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
 * ✓ DELETE /api/session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
 *
 * /api/users
 * ✓ GET /api/users (get all users; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
 * ✓ GET /api/users/{id}/tracks (get user tracks; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/users/{id}/position (get user last position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/users/position (get all users last positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
 * ✓ PUT /api/users/{id} (for admin to edit other users; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
 * ✓ PUT /api/users/{id}/password (password update; access: OPEN-OWNER, PUBLIC-OWNER, PRIVATE-OWNER)
 * ✓ POST /api/users (new user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
 * ✓ DELETE /api/users/{id} (delete user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
 *
 * /api/tracks
 * ✓ GET /api/tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ POST /api/tracks (add track; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ PUT /api/tracks/{id} (update track metadata; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/tracks/{id}/positions[?after={positionId}] (track positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/tracks/{id}/export?format={gpx|kml} (download exported file; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ POST /api/tracks/import (import uploaded file; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ DELETE /api/tracks/{id} (delete track; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 *
 * /api/positions
 * ✓ POST /api/tracks/{id}/positions (add position; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ PUT /api/positions/{id} (update position; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ DELETE /api/positions/{id} (delete position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ POST /api/positions/{id}/image (add image to position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ DELETE /api/positions/{id}/image (delete image from position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ GET /api/positions/{id}/image (get image from position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 *
 * /api/locale
 * ✓ GET /api/locales (list of languages, translated strings for current language; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
 *
 * /api/client/
 * ✓ POST /api/client/session (log in; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
 * ✓ POST /api/client/tracks (add track; access: OPEN-OWNER, PUBLIC-OWNER, PRIVATE-OWNER)
 * ✓ POST /api/client/positions (add position; access: OPEN-OWNER, PUBLIC-OWNER, PRIVATE-OWNER)
 */
class Router {
  /** @var array<string, array<string, Route>> $routes [method => [path => route]] */
  private array $routes = [];
  /** @var MiddlewareInterface[] $middlewares */
  private array $middlewares = [];
  private Request $request;

  /**
   * @throws ServerException
   */
  public function setupRoutes(Component\Session $session, Entity\Config $config, MapperFactory $mapperFactory): void {

    $controllerClasses = [
      Controller\Config::class,
      Controller\Locale::class,
      Controller\Position::class,
      Controller\Session::class,
      Controller\Track::class,
      Controller\User::class
    ];

    foreach ($controllerClasses as $controllerClass) {
      /** @var Controller\AbstractController $controller */
      $controller = new $controllerClass($mapperFactory, $session, $config);
      $this->setupRoute($controller);
    }
    $this->setupRoute(new Controller\Legacy($mapperFactory, $session, $config, $this));
  }

  private function addRoute(Route $route): void {
    $this->routes[$route->getMethod()][$route->getPath()] = $route;
  }

  public function addMiddleware(MiddlewareInterface $middleware): void {
    $this->middlewares[] = $middleware;
  }

  /**
   * @param Request $request
   * @return Response
   * @throws InvalidInputException
   * @throws ReflectionException
   * @throws ServerException
   */
  public function dispatch(Request $request): Response {
    $this->request = $request;
    if (($request->getUriSegments()[1] !== 'api' && $request->getUriSegments()[1] !== 'client') || empty($request->getUriSegments()[2])) {
      return Response::notFound();
    }
    if (isset($this->routes[$this->request->getMethod()])) {

      foreach ($this->routes[$this->request->getMethod()] as $routePath => $route) {
        if ($this->request->matchPath($routePath)) {
          $this->request->parseHandlerArguments($route->getHandler());
          foreach ($this->middlewares as $middleware) {
            $middlewareResponse = $this->executeMiddleware($middleware, $route);
            if ($middlewareResponse->getCode() != Response::CODE_1_CONTINUE) {
              return $middlewareResponse;
            }
          }
          return $this->callHandler($route->getHandler());
        }
      }
    }
    return Response::notFound();
  }

  private function executeMiddleware(MiddlewareInterface $middleware, Route $route): Response {
    return $middleware->run($this->request, $route);
  }

  /**
   * Call the controller method or closure
   * @param callable|array $handler
   * @return Response
   */
  private function callHandler(callable|array $handler): Response {
    if (is_callable($handler)) {
      $arguments = $this->request->getPreparedArguments();
      return call_user_func_array($handler, $arguments);
    } else {
      throw new InvalidArgumentException('Invalid route handler');
    }
  }

  /**
   * @throws ServerException
   */
  private function setupRoute(Controller\AbstractController $controller): void {

    foreach (Reflection::methodGenerator($controller, Route::class) as $route => $method) {

      /** @var Route $route  */
      $handler = [$controller, $method->getName()];
      $route->setHandler($handler);
      $this->addRoute($route);
    }
  }


}
