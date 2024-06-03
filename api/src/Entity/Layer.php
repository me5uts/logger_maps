<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

class Layer {
  public $id;
  public $name;
  public $url;
  public $priority;

  /**
   * Layer constructor.
   * @param int $id
   * @param string $name
   * @param string $url
   * @param int $priority
   */
  public function __construct(int $id, string $name, string $url, int $priority) {
    $this->id = $id;
    $this->name = $name;
    $this->url = $url;
    $this->priority = $priority;
  }
}
