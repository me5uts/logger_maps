<?php
declare(strict_types = 1);
/* μlogger
 *
 * Copyright(C) 2017 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

namespace uLogger\Entity;

use PDOException;
use uLogger\Controller\Db;
use uLogger\Helper\Upload;

/**
 * Positions handling
 */
class Position {
  /** @param int Position id */
  public $id;
  /** @param int Unix time stamp */
  public $timestamp;
  /** @param int User id */
  public $userId;
  /** @param string User login */
  public $userLogin;
  /** @param int Track id */
  public $trackId;
  /** @param string Track name */
  public $trackName;
  /** @param float Latitude */
  public $latitude;
  /** @param float Longitude */
  public $longitude;
  /** @param float Altitude */
  public $altitude;
  /** @param float Speed */
  public $speed;
  /** @param float Bearing */
  public $bearing;
  /** @param int Accuracy */
  public $accuracy;
  /** @param string Provider */
  public $provider;
  /** @param string Comment */
  public $comment;
  /** @param string Image path */
  public $image;

  public $isValid = false;

  /**
   * Constructor
   * @param int|null $positionId Position id
   */
  public function __construct(?int $positionId = null) {

    if (!empty($positionId)) {
      $query = "SELECT p.id, " . self::db()->unix_timestamp('p.time') . " AS tstamp, p.user_id, p.track_id,
                p.latitude, p.longitude, p.altitude, p.speed, p.bearing, p.accuracy, p.provider,
                p.comment, p.image, u.login, t.name
                FROM " . self::db()->table('positions') . " p
                LEFT JOIN " . self::db()->table('users') . " u ON (p.user_id = u.id)
                LEFT JOIN " . self::db()->table('tracks') . " t ON (p.track_id = t.id)
                WHERE p.id = ? LIMIT 1";
      $params = [ $positionId ];
      try {
        $this->loadWithQuery($query, $params);
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
   * Has image
   *
   * @return bool True if has image
   */
  public function hasImage(): bool {
    return !empty($this->image);
  }

 /**
  * Add position
  *
  * @param int $userId
  * @param int $trackId
  * @param int $timestamp Unix time stamp
  * @param float $lat
  * @param float $lon
  * @param float|null $altitude Optional
  * @param float|null $speed Optional
  * @param float|null $bearing Optional
  * @param int|null $accuracy Optional
  * @param string|null $provider Optional
  * @param string|null $comment Optional
  * @param string|null $image Optional
  * @return int|bool New position id in database, false on error
  */
  public static function add(int $userId, int $trackId, int $timestamp, float $lat, float $lon,
                             ?float $altitude = null, ?float $speed = null, ?float $bearing = null, ?int $accuracy = null,
                             ?string $provider = null, ?string $comment = null, ?string $image = null) {
    $positionId = false;
    if ($userId && $trackId) {
      $track = new Track($trackId);
      if ($track->isValid && $track->userId === $userId) {
        try {
          $table = self::db()->table('positions');
          $query = "INSERT INTO $table
                    (user_id, track_id,
                    time, latitude, longitude, altitude, speed, bearing, accuracy, provider, comment, image)
                    VALUES (?, ?, " . self::db()->from_unixtime('?') . ", ?, ?, ?, ?, ?, ?, ?, ?, ?)";
          $stmt = self::db()->prepare($query);
          $params = [ $userId, $trackId,
                  $timestamp, $lat, $lon, $altitude, $speed, $bearing, $accuracy, $provider, $comment, $image ];
          $stmt->execute($params);
          $positionId = (int) self::db()->lastInsertId("${table}_id_seq");
        } catch (PDOException $e) {
          // TODO: handle error
          syslog(LOG_ERR, $e->getMessage());
        }
      }
    }
    return $positionId;
  }

  /**
   * Save position to database
   *
   * @return bool True if success, false otherwise
   */
  public function update(): bool {
    $ret = false;
    if ($this->isValid) {
      try {
        $query = "UPDATE " . self::db()->table('positions') . " SET 
                  time = " . self::db()->from_unixtime('?') . ", user_id = ?, track_id = ?, latitude = ?, longitude = ?, altitude = ?, 
                  speed = ?, bearing = ?, accuracy = ?, provider = ?, comment = ?, image = ? WHERE id = ?";
        $stmt = self::db()->prepare($query);
        $params = [
          $this->timestamp,
          $this->userId,
          $this->trackId,
          $this->latitude,
          $this->longitude,
          $this->altitude,
          $this->speed,
          $this->bearing,
          $this->accuracy,
          $this->provider,
          $this->comment,
          $this->image,
          $this->id
        ];
        $stmt->execute($params);
        $ret = true;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Delete positions
   *
   * @return bool True if success, false otherwise
   */
  public function delete(): bool {
    $ret = false;
    if ($this->isValid) {
      try {
        $query = "DELETE FROM " . self::db()->table('positions') . " WHERE id = ?";
        $stmt = self::db()->prepare($query);
        $stmt->execute([ $this->id ]);
        $this->removeImage();
        $ret = true;
        $this->id = null;
        $this->isValid = false;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Delete all user's positions, optionally limit to given track
   *
   * @param int $userId User id
   * @param int|null $trackId Optional track id
   * @return bool True if success, false otherwise
   */
  public static function deleteAll(int $userId, ?int $trackId = null): bool {
    $ret = false;
    if (!empty($userId)) {
      $args = [];
      $where = "WHERE user_id = ?";
      $args[] = $userId;
      if (!empty($trackId)) {
        $where .= " AND track_id = ?";
        $args[] = $trackId;
      }
      self::removeImages($userId, $trackId);
      try {
        $query = "DELETE FROM " . self::db()->table('positions') . " $where";
        $stmt = self::db()->prepare($query);
        $stmt->execute($args);
        $ret = true;
      } catch (PDOException $e) {
        // TODO: handle exception
        syslog(LOG_ERR, $e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Get last position data from database
   * (for given user if specified)
   *
   * @param int|null $userId Optional user id
   * @return Position Position
   */
  public static function getLast(?int $userId = null): Position {
    if (!empty($userId)) {
      $where = "WHERE p.user_id = ?";
      $params = [ $userId ];
    } else {
      $where = "";
      $params = null;
    }
    $query = "SELECT p.id, " . self::db()->unix_timestamp('p.time') . " AS tstamp, p.user_id, p.track_id,
              p.latitude, p.longitude, p.altitude, p.speed, p.bearing, p.accuracy, p.provider,
              p.comment, p.image, u.login, t.name
              FROM " . self::db()->table('positions') . " p
              LEFT JOIN " . self::db()->table('users') . " u ON (p.user_id = u.id)
              LEFT JOIN " . self::db()->table('tracks') . " t ON (p.track_id = t.id)
              $where
              ORDER BY p.time DESC, p.id DESC LIMIT 1";
    $position = new Position();
    try {
      $position->loadWithQuery($query, $params);
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
    }
    return $position;
  }

  /**
   * Get last positions for all users
   *
   * @return array|bool Array of Position positions, false on error
   */
  public static function getLastAllUsers() {
    $query = "SELECT p.id, " . self::db()->unix_timestamp('p.time') . " AS tstamp, p.user_id, p.track_id,
              p.latitude, p.longitude, p.altitude, p.speed, p.bearing, p.accuracy, p.provider,
              p.comment, p.image, u.login, t.name
              FROM " . self::db()->table('positions') . " p
              LEFT JOIN " . self::db()->table('users') . " u ON (p.user_id = u.id)
              LEFT JOIN " . self::db()->table('tracks') . " t ON (p.track_id = t.id)
              WHERE  p.id = (
                SELECT p2.id FROM " . self::db()->table('positions') . " p2
                WHERE p2.user_id = p.user_id
                ORDER BY p2.time DESC, p2.id DESC
                LIMIT 1
              )";
    $positionsArr = [];
    try {
      $result = self::db()->query($query);
      while ($row = $result->fetch()) {
        $positionsArr[] = self::rowToObject($row);
      }
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
      $positionsArr = false;
    }
    return $positionsArr;
  }

  /**
   * Get array of all positions
   *
   * @param int|null $userId Optional limit to given user id
   * @param int|null $trackId Optional limit to given track id
   * @param int|null $afterId Optional limit to positions with id greater than given id
   * @param array $rules Optional rules
   * @return Position[]|bool Array of Position positions, false on error
   */
  public static function getAll(?int $userId = null, ?int $trackId = null, ?int $afterId = null, array $rules = []) {
    if (!empty($userId)) {
      $rules[] = "p.user_id = " . self::db()->quote((string) $userId);
    }
    if (!empty($trackId)) {
      $rules[] = "p.track_id = " . self::db()->quote((string) $trackId);
    }
    if (!empty($afterId)) {
      $rules[] = "p.id > " . self::db()->quote((string) $afterId);
    }
    if (!empty($rules)) {
      $where = "WHERE " . implode(" AND ", $rules);
    } else {
      $where = "";
    }
    $query = "SELECT p.id, " . self::db()->unix_timestamp('p.time') . " AS tstamp, p.user_id, p.track_id,
              p.latitude, p.longitude, p.altitude, p.speed, p.bearing, p.accuracy, p.provider,
              p.comment, p.image, u.login, t.name
              FROM " . self::db()->table('positions') . " p
              LEFT JOIN " . self::db()->table('users') . " u ON (p.user_id = u.id)
              LEFT JOIN " . self::db()->table('tracks') . " t ON (p.track_id = t.id)
              $where
              ORDER BY p.time, p.id";
    $positionsArr = [];
    try {
      $result = self::db()->query($query);
      while ($row = $result->fetch()) {
        $positionsArr[] = self::rowToObject($row);
      }
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
      $positionsArr = false;
    }
    return $positionsArr;
  }

  /**
   * Get array of all positions with image
   *
   * @param int|null $userId Optional limit to given user id
   * @param int|null $trackId Optional limit to given track id
   * @param int|null $afterId Optional limit to positions with id greater than given id
   * @param array $rules Optional rules
   * @return Position[]|bool Array of Position positions, false on error
   */
  public static function getAllWithImage(?int $userId = null, ?int $trackId = null, ?int $afterId = null, array $rules = []) {
    $rules[] = "p.image IS NOT NULL";
    return self::getAll($userId, $trackId, $afterId, $rules);
  }

  /**
   * Delete all user's uploads, optionally limit to given track
   *
   * @param int $userId User id
   * @param int|null $trackId Optional track id
   * @return bool True if success, false otherwise
   */
  public static function removeImages(int $userId, ?int $trackId = null): bool {
    if (($positions = self::getAllWithImage($userId, $trackId)) !== false) {
      foreach ($positions as $position) {
        try {
          $position->removeImage();
        } catch (PDOException $e) {
          // TODO: handle exception
          syslog(LOG_ERR, $e->getMessage());
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Add uploaded image
   * @param array $imageMeta File metadata array
   * @return bool
   */
  public function setImage(array $imageMeta): bool {
    $result = false;
    if (!empty($imageMeta)) {
      if ($this->hasImage()) {
        $this->removeImage();
      }
      $this->image = Upload::add($imageMeta, $this->trackId);
      $query = "UPDATE " . self::db()->table('positions') . "
            SET image = ? WHERE id = ?";
      $stmt = self::db()->prepare($query);
      $result = $stmt->execute([ $this->image, $this->id ]);
    }
    return $result;
  }

  /**
   * Delete image
   */
  public function removeImage(): bool {
    $result = true;
    if ($this->hasImage()) {
      $query = "UPDATE " . self::db()->table('positions') . "
            SET image = NULL WHERE id = ?";
      $stmt = self::db()->prepare($query);
      $result = $stmt->execute([ $this->id ]);
      // ignore unlink errors
      Upload::delete($this->image);
      $this->image = null;
    }
    return $result;
  }

 /**
  * Calculate distance to target point using haversine formula
  *
  * @param Position $target Target position
  * @return int Distance in meters
  */
  public function distanceTo(Position $target): int {
    $lat1 = deg2rad($this->latitude);
    $lon1 = deg2rad($this->longitude);
    $lat2 = deg2rad($target->latitude);
    $lon2 = deg2rad($target->longitude);
    $latD = $lat2 - $lat1;
    $lonD = $lon2 - $lon1;
    $bearing = 2 * asin(sqrt((sin($latD / 2) ** 2) + cos($lat1) * cos($lat2) * (sin($lonD / 2) ** 2)));
    return (int) round($bearing * 6371000);
  }

 /**
  * Calculate time elapsed since target point
  *
  * @param Position $target Target position
  * @return int Number of seconds
  */
  public function secondsTo(Position $target): int {
    return $this->timestamp - $target->timestamp;
  }

 /**
  * Convert database row to Position
  *
  * @param array $row Row
  * @return Position Position
  */
  private static function rowToObject(array $row): Position {
    $position = new Position();
    $position->setFromArray($row);
    return $position;
  }

 /**
  * Fill class properties with database query result
  *
  * @param string $query Query
  * @param array|null $params Optional array of bind parameters
  * @throws PDOException
  */
  private function loadWithQuery(string $query, ?array $params = null): void {
    $stmt = self::db()->prepare($query);
    $stmt->execute($params);

    $row = $stmt->fetch();
    if ($row) {
      $this->setFromArray($row);
    }
  }

  /**
   * Set position from array. Array should not be empty
   * @param array $row
   */
  private function setFromArray(array $row): void {
    $this->id = (int) $row['id'];
    $this->timestamp = (int) $row['tstamp'];
    $this->userId = (int) $row['user_id'];
    $this->userLogin = $row['login'];
    $this->trackId = (int) $row['track_id'];
    $this->trackName = $row['name'];
    $this->latitude = (float) $row['latitude'];
    $this->longitude = (float) $row['longitude'];
    $this->altitude = is_null($row['longitude']) ? null : (float) $row['altitude'];
    $this->speed = is_null($row['speed']) ? null : (float) $row['speed'];
    $this->bearing = is_null($row['bearing']) ? null : (float) $row['bearing'];
    $this->accuracy = is_null($row['accuracy']) ? null : (int) $row['accuracy'];
    $this->provider = $row['provider'];
    $this->comment = $row['comment'];
    $this->image = $row['image'];
    $this->isValid = true;
  }
}

?>
