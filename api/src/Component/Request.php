<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

use JsonException;

class Request {

  /** @var string */
  private string $path;
  /** @var string[] */
  private array $uriSegments;
  /** @var string */
  private string $method;
  /** @var array */
  private array $params;
  private array $payload;
  /** @var array */
  private array $filters;

  public const METHOD_GET = 'GET';
  public const METHOD_PUT = 'PUT';
  public const METHOD_DELETE = 'DELETE';
  public const METHOD_POST = 'POST';

  /**
   * @param string $path
   * @param string $method
   * @param array $payload
   * @param array $filters
   */
  public function __construct(string $path = '', string $method = '', array $payload = [], array $filters = []) {
    $this->path = $path;
    $this->method = $method;
    $this->filters = $filters;
    $this->payload = $payload;
    $this->setUriSegments();
  }


  public function getUriSegments(): array {
    return $this->uriSegments;
  }

  public function getMethod(): string {
    return $this->method;
  }

  public function getParams(): array {
    return $this->params;
  }

  public function getPayload(): array {
    return $this->payload;
  }

  public function getFilters(): array {
    return $this->filters;
  }

  public function loadFromServer(): void {
    $this->path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $this->method = $_SERVER['REQUEST_METHOD'];
    $this->filters = is_array($_GET) ? $_GET : [];
    $this->loadPayload();
    $this->setUriSegments();
  }

  /**
   * Set URI path segments array
   */
  private function setUriSegments(): void {
    $this->uriSegments = [];
    $uri = explode('/', $this->path);
    if (is_array($uri)) {
      $this->uriSegments = $uri;
    }
  }

  /**
   * Check if route path matches request path
   * @param string $routePath
   * @return bool
   */
  public function matchPath(string $routePath): bool {
    // Perform pattern matching (e.g., "/api/users/{id}" matches "/api/users/123")
    $pattern = str_replace('\{id\}', '(\d+)', preg_quote($routePath));
    if (preg_match("#^$pattern$#", $this->path) === 1) {
      $this->extractParams($routePath);
      return true;
    }
    return false;
  }

  /**
   * Extract route parameters from path
   * @param $routePath
   * @return void
   */
  private function extractParams($routePath): void {
    $this->params = [];
    // Extract parameter values from the request path
    preg_match_all("/{([a-zA-Z0-9]+)}/", $routePath, $matches);
    $keys = $matches[1];
    $quoted = preg_quote($routePath);
    $pattern = preg_replace('/\\\{[a-zA-Z0-9]+\\\}/', '(\\w+)', $quoted);
    preg_match("#^$pattern$#", $this->path, $values);
    array_shift($values); // remove the first match (the full match)
    foreach ($keys as $index => $key) {
      $this->params[$key] = $values[$index];
    }
  }

  /**
   * @return void
   */
  private function loadPayload(): void {
    $this->payload = [];
    $input = file_get_contents('php://input');
    if (!empty($input)) {
      try {
        $this->payload = (array) json_decode($input, true, 512, JSON_THROW_ON_ERROR);
      } catch (JsonException $e) {
        syslog(LOG_ERR, "Payload parsing failed: \"$input\" [{$e->getMessage()}]");
      }
    }
  }

}
