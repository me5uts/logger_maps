<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use InvalidArgumentException;
use uLogger\Component\Db;

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
   * @param string $className
   * @param mixed ...$arguments
   * @return mixed
   */
  public function getMapper(string $className, ...$arguments): mixed {
    if (array_key_exists($className, $this->cache)) {
      return $this->cache[$className];
    }

    if (!class_exists($className)) {
      throw new InvalidArgumentException('Unknown class');
    }

    $instance = new $className($this->db, ...$arguments);
    $this->cache[$className] = $instance;
    return $instance;
  }

}
