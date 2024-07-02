<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use uLogger\Attribute\JsonField;

class Layer extends AbstractEntity {
  #[JsonField]
  public int $id;
  #[JsonField]
  public string $name;
  #[JsonField]
  public string $url;
  #[JsonField]
  public int $priority;

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
