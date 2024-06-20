<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use uLogger\Mapper\Column;

/**
 * Track handling
 */
class Track {
  #[Column]
  public ?int $id = null;
  #[Column(name: 'user_id')]
  public int $userId;
  #[Column]
  public string $name;
  #[Column]
  public ?string $comment = null;

  /**
   * @param int $userId
   * @param string $name
   * @param string|null $comment
   */
  public function __construct(int $userId, string $name, ?string $comment = null) {
    $this->userId = $userId;
    $this->name = $name;
    $this->comment = $comment;
  }

}

?>
