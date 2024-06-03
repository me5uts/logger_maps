<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Component;

class Route {
  private string $method;
  private string $path;
  private $handler;
  private array $auth;
  public function __construct(string $method, string $path, callable $handler, array $auth) {
    $this->method = $method;
    $this->path = $path;
    $this->handler = $handler;
    $this->auth = $auth;
  }

  public function getMethod(): string {
    return $this->method;
  }

  public function getPath(): string {
    return $this->path;
  }

  public function getHandler(): callable {
    return $this->handler;
  }

  public function getAuth(): array {
    return $this->auth;
  }

}
