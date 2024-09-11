<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use PDOException;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\InvalidInputException;
use uLogger\Exception\NotFoundException;
use uLogger\Exception\ServerException;

class Track extends AbstractMapper {

  /**
   * @param int $trackId
   * @return Entity\Track
   * @throws DatabaseException
   * @throws ServerException
   * @throws NotFoundException
   */
  public function fetch(int $trackId): Entity\Track {
    $tracks = $this->get(trackId: $trackId);
    if (empty($tracks)) {
      throw new NotFoundException();
    }
    return $tracks[0];
  }

  /**
   * @param int $userId
   * @return Entity\Track[]
   * @throws DatabaseException
   * @throws ServerException
   */
  public function fetchByUser(int $userId): array {
    return $this->get(userId: $userId);
  }

  /**
   * Add new track
   *
   * @param Entity\Track $track
   * @throws DatabaseException
   */
  public function create(Entity\Track $track): void {
    try {
      $table = $this->db->table('tracks');
      $query = "INSERT INTO $table (user_id, name, comment) VALUES (?, ?, ?)";
      $stmt = $this->db->prepare($query);
      $params = [ $track->userId, $track->name, $track->comment ];
      $stmt->execute($params);
      $track->id = (int) $this->db->lastInsertId("{$table}_id_seq");
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException();
    }
  }

  /**
   * Get all tracks
   *
   * @param int|null $userId Optional limit to user id
   * @param int|null $trackId
   * @return array Array of Track tracks, false on error
   * @throws ServerException
   * @throws DatabaseException
   */
  public function get(?int $userId = null, ?int $trackId = null): array {

    $where = '';

    if (!empty($userId)) {
      $where = 'WHERE user_id = ' . $this->db->quote((string) $userId);
    } elseif (!empty($trackId)) {
      $where = 'WHERE id = ' . $this->db->quote((string) $trackId);
    }
    $query = 'SELECT id, user_id, name, comment FROM ' . $this->db->table('tracks') . " $where ORDER BY id DESC";
    try {
      $result = $this->db->query($query);
      $tracks = [];
      while ($row = $result->fetch()) {
        $tracks[] = Entity\Track::fromDatabaseRow($row);
      }
      return $tracks;
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Update track
   *
   * @throws DatabaseException
   * @throws InvalidInputException
   * @throws NotFoundException
   */
  public function update(Entity\Track $track): void {
    if (empty($track->name)) {
      throw new InvalidInputException('Empty track name');
    }
    if ($track->comment === '') {
      $track->comment = null;
    }
    try {
      $query = 'UPDATE ' . $this->db->table('tracks') . ' SET name = ?, comment = ? WHERE id = ?';
      $stmt = $this->db->prepare($query);
      $params = [ $track->name, $track->comment, $track->id ];
      $stmt->execute($params);
      if ($stmt->rowCount() !== 1) {
        throw new NotFoundException();
      }
    } catch (PDOException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Delete track metadata
   *
   * @param Entity\Track $track
   * @return void
   * @throws DatabaseException
   */
  public function delete(Entity\Track $track): void {

    try {
      $query = 'DELETE FROM ' . $this->db->table('tracks') . ' WHERE id = ?';
      $stmt = $this->db->prepare($query);
      $stmt->execute([ $track->id ]);

    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Delete all user's tracks metadata
   *
   * @param int $userId User id
   * @throws DatabaseException
   */
  public function deleteAll(int $userId): void {

    try {
      $query = 'DELETE FROM ' . $this->db->table('tracks') . ' WHERE user_id = ?';
      $stmt = $this->db->prepare($query);
      $stmt->execute([ $userId ]);
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

}
