<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use PDOException;
use uLogger\Component\FileUpload;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;

class Position extends AbstractMapper {

  /**
   * @throws DatabaseException
   * @throws NotFoundException
   * @throws ServerException
   */
  public function fetch(int $positionId): Entity\Position {
    $positions = $this->get(positionId: $positionId, singleRow: true);
    if (empty($positions)) {
      throw new NotFoundException();
    }
    return $positions[0];
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function fetchLast(int $userId): Entity\Position {
    $positions = $this->get(userId: $userId, orderBy: 'p.time DESC, p.id DESC', singleRow: true);
    if (empty($positions)) {
      throw new NotFoundException();
    }
    return $positions[0];
  }

  /**
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function fetchLastAllUsers(): Entity\Position {
    $rules['p.id'] = '(
    SELECT p2.id FROM ' . $this->db->table('positions') . ' p2
          WHERE p2.user_id = p.user_id
          ORDER BY p2.time DESC, p2.id DESC
          LIMIT 1
    )';
    $positions = $this->get(rules: $rules);
    if (empty($positions)) {
      throw new NotFoundException();
    }
    return $positions[0];
  }

  /**
   * @throws ServerException
   * @throws DatabaseException
   */
  public function findAll(int $trackId, ?int $afterId = null): array {
    return $this->get(trackId: $trackId, afterId: $afterId);
  }

  /**
   * Add position
   *
   * @param Entity\Position $position
   * @throws DatabaseException
   */
  public function create(Entity\Position $position): void {

    try {
      $table = $this->db->table('positions');
      $query = "INSERT INTO $table
                  (user_id, track_id,
                  time, latitude, longitude, altitude, speed, bearing, accuracy, provider, comment, image)
                  VALUES (?, ?, " . $this->db->from_unixtime('?') . ', ?, ?, ?, ?, ?, ?, ?, ?, ?)';
      $stmt = $this->db->prepare($query);
      $params = [
        $position->userId, $position->trackId,
        $position->timestamp, $position->latitude, $position->longitude,
        $position->altitude, $position->speed, $position->bearing, $position->accuracy,
        $position->provider, $position->comment, $position->image
      ];
      $stmt->execute($params);
      $position->id = (int) $this->db->lastInsertId("{$table}_id_seq");
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }

  }

  /**
   * Save position to database
   *
   * @param Entity\Position $position
   * @return void True if success, false otherwise
   * @throws DatabaseException
   */
  public function update(Entity\Position $position): void {
    try {
      $query = 'UPDATE ' . $this->db->table('positions') . ' SET 
                time = ' . $this->db->from_unixtime('?') . ', user_id = ?, track_id = ?, latitude = ?, longitude = ?, altitude = ?, 
                speed = ?, bearing = ?, accuracy = ?, provider = ?, comment = ?, image = ? WHERE id = ?';
      $stmt = $this->db->prepare($query);
      $params = [
        $position->timestamp,
        $position->userId,
        $position->trackId,
        $position->latitude,
        $position->longitude,
        $position->altitude,
        $position->speed,
        $position->bearing,
        $position->accuracy,
        $position->provider,
        $position->comment,
        $position->image,
        $position->id
      ];
      $stmt->execute($params);
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }


  /**
   * Delete positions
   *
   * @param Entity\Position $position
   * @return void True if success, false otherwise
   * @throws DatabaseException
   * @throws ServerException
   */
  public function delete(Entity\Position $position): void {

    try {
      $query = 'DELETE FROM ' . $this->db->table('positions') . ' WHERE id = ?';
      $stmt = $this->db->prepare($query);
      $stmt->execute([ $position->id ]);
      $this->removeImage($position);
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Add uploaded image
   * @param Entity\Position $position
   * @param FileUpload $imageMeta File metadata
   * @return void
   * @throws InvalidInputException
   * @throws ServerException
   */
  public function setImage(Entity\Position $position, FileUpload $imageMeta): void {

    if ($position->hasImage()) {
      $this->removeImage($position);
    }
    $position->image = $imageMeta->add($position->trackId);
    $query = 'UPDATE ' . $this->db->table('positions') . '
              SET image = ? WHERE id = ?';
    $stmt = $this->db->prepare($query);
    $stmt->execute([ $position->image, $position->id ]);
  }

  /**
   * Delete image
   * @throws ServerException
   */
  public function removeImage(Entity\Position $position): void {

    if ($position->hasImage()) {
      $query = 'UPDATE ' . $this->db->table('positions') . '
                SET image = NULL WHERE id = ?';
      $stmt = $this->db->prepare($query);
      $stmt->execute([ $position->id ]);

      if (FileUpload::delete($position->image) === false) {
        throw new ServerException('Unable to delete image from filesystem');
      }
      $position->image = null;
    }
  }

  /**
   * Delete all user's positions, optionally limit to given track
   *
   * @param int $userId User id
   * @param int|null $trackId Optional track id
   * @return bool True if success, false otherwise
   * @throws DatabaseException
   * @throws ServerException
   */
  public function deleteAll(int $userId, ?int $trackId = null): bool {
    $ret = false;
    if (!empty($userId)) {
      $args = [];
      $where = 'WHERE user_id = ?';
      $args[] = $userId;
      if (!empty($trackId)) {
        $where .= ' AND track_id = ?';
        $args[] = $trackId;
      }
      self::removeImages($userId, $trackId);
      try {
        $query = 'DELETE FROM ' . $this->db->table('positions') . " $where";
        $stmt = $this->db->prepare($query);
        $stmt->execute($args);
        $ret = true;
      } catch (PDOException $e) {
        syslog(LOG_ERR, $e->getMessage());
        throw new DatabaseException($e->getMessage());
      }
    }
    return $ret;
  }

  /**
   * Get array of all positions with image
   *
   * @param int|null $userId Optional limit to given user id
   * @param int|null $trackId Optional limit to given track id
   * @return Entity\Position[] Array of Position positions, false on error
   * @throws DatabaseException
   * @throws ServerException
   */
  public function getAllWithImage(?int $userId = null, ?int $trackId = null): array {
    $rules[] = 'p.image IS NOT NULL';
    return $this->get(userId: $userId, trackId: $trackId, rules: $rules);
  }

  /**
   * Delete all user's uploads, optionally limit to given track
   *
   * @param int $userId User id
   * @param int|null $trackId Optional track id
   * @return void True if success, false otherwise
   * @throws DatabaseException
   * @throws ServerException
   */
  public function removeImages(int $userId, ?int $trackId = null): void {
    $positions = $this->getAllWithImage($userId, $trackId);
    foreach ($positions as $position) {
      try {
        $this->removeImage($position);
      } catch (PDOException $e) {
        syslog(LOG_ERR, $e->getMessage());
        throw new DatabaseException($e->getMessage());
      }
    }
  }

  /**
   * Get array of all positions
   *
   * @param int|null $positionId
   * @param int|null $userId Optional limit to given user id
   * @param int|null $trackId Optional limit to given track id
   * @param int|null $afterId Optional limit to positions with id greater than given id
   * @param string|null $orderBy
   * @param bool|null $singleRow
   * @param array|null $rules
   * @return Entity\Position[] Array of Position positions, false on error
   * @throws DatabaseException
   * @throws ServerException
   */
  private function get(
    ?int    $positionId = null,
    ?int    $userId = null,
    ?int    $trackId = null,
    ?int    $afterId = null,
    ?string $orderBy = null,
    ?bool   $singleRow = false,
    ?array  $rules = []
  ): array {
    if (!empty($positionId)) {
      $rules[] = 'p.id = ' . $this->db->quote((string) $positionId);
    }
    if (!empty($userId)) {
      $rules[] = 'p.user_id = ' . $this->db->quote((string) $userId);
    }
    if (!empty($trackId)) {
      $rules[] = 'p.track_id = ' . $this->db->quote((string) $trackId);
    }
    if (!empty($afterId)) {
      $rules[] = 'p.id > ' . $this->db->quote((string) $afterId);
    }
    if (!empty($rules)) {
      $where = 'WHERE ' . implode(' AND ', $rules);
    } else {
      $where = '';
    }
    if (!empty($orderBy)) {
      $orderBy = "ORDER BY $orderBy";
    } else {
      $orderBy = 'ORDER BY p.time, p.id';
    }
    $limit = $singleRow ? 'LIMIT 1' : '';
    $query = 'SELECT p.id, ' . $this->db->unix_timestamp('p.time') . ' AS tstamp, p.user_id, p.track_id,
              p.latitude, p.longitude, p.altitude, p.speed, p.bearing, p.accuracy, p.provider,
              p.comment, p.image, u.login, t.name,
              CASE 
                  WHEN p.image IS NOT NULL THEN 1 
                  ELSE 0 
              END AS has_image
              FROM ' . $this->db->table('positions') . ' p
              LEFT JOIN ' . $this->db->table('users') . ' u ON (p.user_id = u.id)
              LEFT JOIN ' . $this->db->table('tracks') . " t ON (p.track_id = t.id)
              $where $orderBy $limit";
    $positions = [];
    try {
      $result = $this->db->query($query);
      while ($row = $result->fetch()) {
        $positions[] = Entity\Position::fromDatabaseRow($row);

      }
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }

    return $positions;
  }


}
