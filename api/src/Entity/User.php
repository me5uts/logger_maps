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
 * User handling routines
 */
class User {
  #[Column]
  public ?int $id = null;
  #[Column]
  public string $login;
  #[Column(name: 'password')]
  public ?string $hash = null;
  public ?string $password = null;
  #[Column(name: 'admin')]
  public bool $isAdmin = false;

  /**
   * @param string $login
   */
  public function __construct(string $login) { $this->login = $login; }


  /**
   * Check if given password matches user's one
   *
   * @param string $password Password
   * @return bool True if matches, false otherwise
   */
  public function validPassword(string $password): bool {
    return password_verify($password, $this->hash);
  }

}
?>
