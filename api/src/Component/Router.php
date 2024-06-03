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
use ReflectionFunction;
use uLogger\Api\Routes;
use uLogger\Middleware\Middleware;

class Router {
  private array $routes = [];
  private array $middlewares = [];
  private Request $request;

  use Routes;

  private function addRoute(string $method, string $path, callable $handler, array $authRules = []): void {
    $this->routes[$method][$path] = new Route($method, $path, $handler, $authRules);
  }

  public function addMiddleware(Middleware $middleware): void {
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
            if (!$this->executeMiddleware($middleware, $route)) {
              return Response::notAuthorized();
            }
          }
          return $this->callHandler($route->getHandler());
        }
      }
    }
    return Response::notFound();
  }

  private function executeMiddleware(Middleware $middleware, Route $route): bool {
    return $middleware->run($this->request, $route);
  }


  /**
   * Call the controller method or closure
   * @param callable $handler
   * @return Response
   * @throws ReflectionException
   */
  private function callHandler(callable $handler): Response {
    if (is_callable($handler)) {
      $arguments = $this->getSanitizedArguments($handler);
      return call_user_func_array($handler, $arguments);
    } else {
      throw new InvalidArgumentException('Invalid route handler');
    }
  }

  public function put(string $path, $handler, $middlewares = []): void {
    $this->addRoute('PUT', $path, $handler, $middlewares);
  }

  public function get(string $path, $handler, $middlewares = []): void {
    $this->addRoute('GET', $path, $handler, $middlewares);
  }

  public function post(string $path, $handler, $middlewares = []): void {
    $this->addRoute('POST', $path, $handler, $middlewares);
  }

  public function delete(string $path, $handler, $middlewares = []): void {
    $this->addRoute('DELETE', $path, $handler, $middlewares);
  }

  /**
   * @param callable $handler
   * @return array
   * @throws ReflectionException
   * @throws InvalidArgumentException
   */
  private function getSanitizedArguments(callable $handler): array {
    $arguments = array_merge($this->request->getParams(), $this->request->getFilters(), $this->request->getPayload());
    $preparedArguments = [];

    $f = new ReflectionFunction($handler);
    foreach ($f->getParameters() as $param) {
      if (!$param->hasType()) {
        throw new InvalidArgumentException("Parameter $param missing type");
      }
      $name = $param->name;
      $type = $param->getType()->getName();

      if (array_key_exists($name, $arguments)) {
        $preparedArguments[] = match ($type) {
          'int' => (int) $arguments[$name],
          default => $arguments[$name]
        };
      } elseif (!$param->isOptional()) {
        throw new InvalidArgumentException("Missing parameter $param type $type");
      }
    }
    return $preparedArguments;
  }

}
