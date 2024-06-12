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
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;

/**
 * Track handling
 */
class Track {
  public int $id;
  public int $userId;
  public string $name;
  public ?string $comment = null;

  public bool $isValid = false;

  /**
   * Constructor
   *
   * @param int|null $trackId Track id
   */
  public function __construct(?int $trackId = null) {

    if (!empty($trackId)) {
      try {
        $query = "SELECT id, user_id, name, comment FROM " . self::db()->table('tracks') . " WHERE id = ? LIMIT 1";
        $stmt = self::db()->prepare($query);
        $stmt->execute([$trackId]);
        $stmt->bindColumn('id', $id, PDO::PARAM_INT);
        $stmt->bindColumn('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindColumn('name', $name);
        $stmt->bindColumn('comment', $comment);
        if ($stmt->fetch(PDO::FETCH_BOUND)) {
          $this->id = $id;
          $this->userId = $userId;
          $this->name = $name;
          $this->comment = $comment;
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
   * Add new track
   *
   * @param int $userId User id
   * @param string $name Name
   * @param string|null $comment Optional comment
   * @return int|bool New track id, false on error
   */
  public static function add(int $userId, string $name, ?string $comment = null) {
    $trackId = false;
    if (!empty($userId) && !empty($name)) {
      try {
        $table = self::db()->table('tracks');
        $query = "INSERT INTO $table (user_id, name, comment) VALUES (?, ?, ?)";
        $stmt = self::db()->prepare($query);
        $params = [ $userId, $name, $comment ];
        $stmt->execute($params);
        $trackId = (int) self::db()->lastInsertId("{$table}_id_seq");
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $trackId;
  }

  /**
   * Add new position to track
   *
   * @param int $userId
   * @param int $timestamp Unix time stamp
   * @param float $lat
   * @param float $lon
   * @param float|null $altitude Optional
   * @param float|null $speed Optional
   * @param float|null $bearing Optional
   * @param int|null $accuracy Optional
   * @param string|null $provider Optional
   * @param string|null $comment Optional
   * @param string|null $imageId Optional
   * @return int|bool New position id in database, false on error
   */
  public function addPosition(int $userId, int $timestamp, float $lat, float $lon,
                              ?float $altitude = null, ?float $speed = null, ?float $bearing = null, ?int $accuracy = null,
                              ?string $provider = null, ?string $comment = null, ?string $imageId = null) {
    if ($this->id) {
      return Position::add($userId, $this->id, $timestamp, $lat, $lon,
        $altitude, $speed, $bearing, $accuracy, $provider, $comment, $imageId);
    }

    return false;
  }

 /**
  * Delete track with all positions
  *
  * @return bool True if success, false otherwise
  */
  public function delete(): bool {
    $ret = false;
    if ($this->isValid) {
      // delete positions
      if (Position::deleteAll($this->userId, $this->id) === false) {
        return false;
      }
      // delete track metadata
      try {
        $query = "DELETE FROM " . self::db()->table('tracks') . " WHERE id = ?";
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
   * Update track
   *
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function update(): void {
    if (empty($this->name)) {
      throw new InvalidInputException("Empty track name");
    }
    if ($this->comment === "") { $this->comment = null; }
    try {
      $query = "UPDATE " . self::db()->table('tracks') . " SET name = ?, comment = ? WHERE id = ?";
      $stmt = self::db()->prepare($query);
      $params = [ $this->name, $this->comment, $this->id ];
      $stmt->execute($params);
      if ($stmt->rowCount() !== 1) {
        throw new NotFoundException();
      }
    } catch (PDOException $e) {
      throw new DatabaseException($e->getMessage());
    }

  }

  /**
   * Delete all user's tracks
   *
   * @param int $userId User id
   * @return bool True if success, false otherwise
   */
  public static function deleteAll(int $userId): bool {
    $ret = false;
    if (!empty($userId) && Position::deleteAll($userId) === true) {
      // remove all tracks
      try {
        $query = "DELETE FROM " . self::db()->table('tracks') . " WHERE user_id = ?";
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $userId ]);
        $ret = true;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Get all tracks
   *
   * @param int|null $userId Optional limit to user id
   * @return array|bool Array of Track tracks, false on error
   */
  public static function getAll(?int $userId = null) {
    if (!empty($userId)) {
      $where = "WHERE user_id=" . self::db()->quote((string) $userId);
    } else {
      $where = "";
    }
    $query = "SELECT id, user_id, name, comment FROM " . self::db()->table('tracks') . " $where ORDER BY id DESC";
    try {
      $result = self::db()->query($query);
      $trackArr = [];
      while ($row = $result->fetch()) {
        $trackArr[] = self::rowToObject($row);
      }
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
      $trackArr = false;
    }
    return $trackArr;
  }

  /**
   * Convert database row to Track
   *
   * @param array $row Row
   * @return Track Track
   */
  private static function rowToObject(array $row): Track {
    $track = new Track();
    $track->id = (int) $row['id'];
    $track->userId = (int) $row['user_id'];
    $track->name = $row['name'];
    $track->comment = $row['comment'];
    $track->isValid = true;
    return $track;
  }
}

?>
