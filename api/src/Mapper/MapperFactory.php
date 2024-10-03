<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use uLogger\Component\Db;
use uLogger\Exception\ServerException;

class MapperFactory {

  private Db $db;
  private array $cache = [];

  /**
   * Mapper constructor.
   * @param Db $db
   */
  public function __construct(Db $db) {
    $this->db = $db;
  }

  /**
   * @template T
   * @param class-string<T> $className
   * @param mixed ...$arguments
   * @throws ServerException
   * @return T
   */
  public function getMapper(string $className, ...$arguments): mixed {
    if (array_key_exists($className, $this->cache)) {
      return $this->cache[$className];
    }

    if (!class_exists($className) || !is_subclass_of($className, AbstractMapper::class)) {
      throw new ServerException("Unknown mapper class $className");
    }

    $instance = new $className($this->db, ...$arguments);
    $this->cache[$className] = $instance;
    return $instance;
  }

}
