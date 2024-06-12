<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Entity;

use PDO;
use PDOException;
use uLogger\Component\Db;

/**
 * User handling routines
 */
class User {
  public ?int $id;
  public ?string $login;
  public ?string $hash;
  public ?string $password;
  public bool $isAdmin = false;
  public bool $isValid = false;

  /**
   * Constructor
   *
   * @param string|int|null $key Login or ID
   */
  public function __construct(string|int|null $key = null) {
    if (!empty($key)) {
      try {
        $query = "SELECT id, login, password, admin FROM " . self::db()->table('users');
        if (is_int($key)) {
           $query .= " WHERE id = ? LIMIT 1";
        } else {
          $query .= " WHERE login = ? LIMIT 1";
        }
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $key ]);
        $stmt->bindColumn('id', $this->id, PDO::PARAM_INT);
        $stmt->bindColumn('login', $this->login);
        $stmt->bindColumn('password', $this->hash);
        $stmt->bindColumn('admin', $this->isAdmin, PDO::PARAM_BOOL);
        if ($stmt->fetch(PDO::FETCH_BOUND)) {
          $this->isValid = true;
        }
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
  }

  /**
   * Get db instance
   *
   * @return Db instance
   */
  private static function db(): Db {
    return Db::getInstance();
  }

  /**
   * Add new user
   *
   * @param string $login Login
   * @param string $pass Password
   * @param bool $isAdmin Is admin
   * @return int|bool New user id, false on error
   */
  public static function add(string $login, string $pass, bool $isAdmin = false): bool|int {
    $userid = false;
    if (!empty($login) && !empty($pass)) {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $table = self::db()->table('users');
      try {
        $query = "INSERT INTO $table (login, password, admin) VALUES (?, ?, ?)";
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $login, $hash, (int) $isAdmin ]);
        $userid = (int) self::db()->lastInsertId("{$table}_id_seq");
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $userid;
  }

  /**
   * Delete user
   * This will also delete all user's positions and tracks
   *
   * @return bool True if success, false otherwise
   */
  public function delete(): bool {
    $ret = false;
    if ($this->isValid) {
      // remove tracks and positions
      if (Track::deleteAll($this->id) === false) {
        return false;
      }
      // remove user
      try {
        $query = "DELETE FROM " . self::db()->table('users') . " WHERE id = ?";
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $this->id ]);
        $ret = true;
        $this->isValid = false;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

 /**
  * Set user admin status
  *
  * @param bool $isAdmin True if is admin
  * @return bool True on success, false otherwise
  */
  public function setAdmin(bool $isAdmin): bool {
    $ret = false;
    try {
      $query = "UPDATE " . self::db()->table('users') . " SET admin = ? WHERE login = ?";
      $stmt = self::db()->prepare($query);
      $stmt->execute([ (int) $isAdmin, $this->login ]);
      $ret = true;
      $this->isAdmin = $isAdmin;
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
    }
    return $ret;
  }

 /**
  * Set user password
  *
  * @param string $pass Password
  * @return bool True on success, false otherwise
  */
  public function setPass(string $pass): bool {
    $ret = false;
    if (!empty($this->login) && !empty($pass)) {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      try {
        $query = "UPDATE " . self::db()->table('users') . " SET password = ? WHERE login = ?";
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $hash, $this->login ]);
        $ret = true;
        $this->hash = $hash;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Check if given password matches user's one
   *
   * @param string $password Password
   * @return bool True if matches, false otherwise
   */
  public function validPassword(string $password): bool {
    return password_verify($password, $this->hash);
  }

  /**
   * Store User object in session
   */
  public function storeInSession(): void {
    $_SESSION['user'] = $this;
  }

  /**
   * Fill User object properties from session data
   * @return User
   */
  public static function getFromSession(): User {
    $user = new User();
    if (isset($_SESSION['user'])) {
      $sessionUser = $_SESSION['user'];
      $user->id = $sessionUser->id;
      $user->login = $sessionUser->login;
      $user->hash = $sessionUser->hash;
      $user->isAdmin = $sessionUser->isAdmin;
      $user->isValid = $sessionUser->isValid;
    }
    return $user;
  }

  /**
   * Get all users
   *
   * @return User[]|bool Array of User users, false on error
   */
  public static function getAll() {
    try {
      $query = "SELECT id, login, password, admin FROM " . self::db()->table('users') . " ORDER BY login";
      $result = self::db()->query($query);
      $userArr = [];
      while ($row = $result->fetch()) {
        $userArr[] = self::rowToObject($row);
      }
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
      $userArr = false;
    }
    return $userArr;
  }

  /**
   * Convert database row to User
   *
   * @param array $row Row
   * @return User User
   */
  private static function rowToObject(array $row): User {
    $user = new User();
    $user->id = (int) $row['id'];
    $user->login = $row['login'];
    $user->hash = $row['password'];
    $user->isAdmin = (bool) $row['admin'];
    $user->isValid = true;
    return $user;
  }
}
?>
