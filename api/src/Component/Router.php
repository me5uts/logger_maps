<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

use Exception;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use uLogger\Component;
use uLogger\Controller;
use uLogger\Entity;
use uLogger\Entity\AbstractEntity;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;
use uLogger\Helper\Reflection;
use uLogger\Helper\Utils;
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
 * ✓ PUT /api/tracks/{id} (update track metadata; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/tracks/{id}/positions[?after={positionId}] (track positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ GET /api/tracks/{id}/export?format={gpx|kml} (download exported file; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER|ADMIN)
 * ✓ POST /api/tracks/import (import uploaded file; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ DELETE /api/tracks/{id} (delete track; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 *
 * /api/positions
 * ✓ PUT /api/positions/{id} (update position; access: OPEN-OWNER|ADMIN, PUBLIC-OWNER|ADMIN, PRIVATE-OWNER|ADMIN)
 * ✓ DELETE /api/positions/{id} (delete position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ POST /api/positions/{id}/image (add image to position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 * ✓ DELETE /api/positions/{id}/image (delete image from position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
 *
 * /api/locale
 * ✓ GET /api/locales (list of languages, translated strings for current language; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
 *
 */
class Router {
  private array $routes = [];
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
  }

  private function addRoute(Route $route): void {
    $this->routes[$route->getMethod()][$route->getPath()] = $route;
  }

  public function addMiddleware(MiddlewareInterface $middleware): void {
    $this->middlewares[] = $middleware;
  }

  /**
   * @throws Exception
   */
  public function dispatch(Request $request): Response {
    $this->request = $request;
    if ($request->getUriSegments()[1] !== 'api' || empty($request->getUriSegments()[2])) {
      return Response::notFound();
    }
    if (isset($this->routes[$this->request->getMethod()])) {

      foreach ($this->routes[$this->request->getMethod()] as $routePath => $route) {
        if ($this->request->matchPath($routePath)) {
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
   * @throws InvalidInputException
   * @throws NotFoundException
   * @throws ReflectionException
   * @throws ServerException
   */
  private function callHandler(callable|array $handler): Response {
    if (is_callable($handler)) {
      $arguments = $this->getSanitizedArguments($handler);
      return call_user_func_array($handler, $arguments);
    } else {
      throw new InvalidArgumentException('Invalid route handler');
    }
  }

  /**
   * @param callable $handler
   * @return array
   * @throws ReflectionException
   * @throws NotFoundException
   * @throws InvalidInputException
   * @throws ServerException
   */
  private function getSanitizedArguments(callable $handler): array {
    $requestParams = array_merge($this->request->getParams(), $this->request->getFilters());
    $requestPayload = $this->request->getPayload();
    $preparedArguments = [];

    $f = new ReflectionMethod($handler[0], $handler[1]);
    foreach ($f->getParameters() as $routeParam) {
      if (!$routeParam->hasType()) {
        throw new ServerException("Parameter $routeParam missing type");
      }
      $routeParamName = $routeParam->getName();
      $routeParamType = $routeParam->getType();
      if (!$routeParamType instanceof ReflectionNamedType) {
        throw new ServerException("Parameter $routeParam is not named type");
      }
      $routeParamTypeName = $routeParamType->getName();

      if ($routeParamTypeName === FileUpload::class) {
        $preparedArguments[] = $this->handleUpload($routeParamName);
      } elseif (array_key_exists($routeParamName, $requestParams)) {
        // params, filters
        $preparedArguments[] = Reflection::castArgument($requestParams[$routeParamName], $routeParamType);
      } elseif ($this->request->hasPayload() && is_subclass_of($routeParamTypeName, AbstractEntity::class)) {
        // payload (map params to entity)
        $preparedArguments[] = $routeParamTypeName::fromPayload($requestPayload);
      } elseif ($this->request->hasPayload() && array_key_exists($routeParamName, $requestPayload)) {
        // payload (map param to argument)
        $preparedArguments[] = Reflection::castArgument($requestPayload[$routeParamName], $routeParamType);
      } elseif (!$routeParam->isOptional()) {
        throw new NotFoundException("Missing parameter $routeParamName type $routeParamTypeName");
      }
    }
    return $preparedArguments;
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

  /**
   * @throws InvalidInputException
   */
  private function handleUpload(string $name): FileUpload {
    try {
      return Utils::requireFile($name);
    } catch (InvalidInputException $e) {
      $message = "iuploadfailure"; // $lang["iuploadfailure"];
      $message .= ": {$e->getMessage()}";
      throw new InvalidInputException($message);
    }
  }

}
