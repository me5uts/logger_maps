<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Mapper;

use PDO;
use PDOException;
use uLogger\Entity;
use uLogger\Exception\DatabaseException;
use uLogger\Exception\ServerException;

class Config extends AbstractMapper {

  /**
   * Read config values from database
   * @return Entity\Config
   * @throws DatabaseException
   * @throws ServerException
   */
  public function fetch(): Entity\Config {
    try {
      $query = "SELECT name, value FROM " . $this->db->table("config");
      $result = $this->db->query($query);
      $configArray = $result->fetchAll(PDO::FETCH_KEY_PAIR);
      $config = $this->mapRowToObject($configArray);
      $this->setLayersFromDatabase($config);
      return $config;

    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Save config values to database
   * @throws DatabaseException
   */
  public function update(Entity\Config $config): void {
    // PDO::PARAM_LOB doesn't work here with pgsql, why?
    $placeholder = $this->db->lobPlaceholder();
    $values = [
      [ "'color_extra'", $placeholder ],
      [ "'color_hilite'", $placeholder ],
      [ "'color_normal'", $placeholder ],
      [ "'color_start'", $placeholder ],
      [ "'color_stop'", $placeholder ],
      [ "'google_key'", $placeholder ],
      [ "'latitude'", $placeholder ],
      [ "'longitude'", $placeholder ],
      [ "'interval_seconds'", $placeholder ],
      [ "'lang'", $placeholder ],
      [ "'map_api'", $placeholder ],
      [ "'pass_lenmin'", $placeholder ],
      [ "'pass_strength'", $placeholder ],
      [ "'public_tracks'", $placeholder ],
      [ "'require_auth'", $placeholder ],
      [ "'stroke_color'", $placeholder ],
      [ "'stroke_opacity'", $placeholder ],
      [ "'stroke_weight'", $placeholder ],
      [ "'units'", $placeholder ],
      [ "'upload_maxsize'", $placeholder ]
    ];

    $params = [
      $config->colorExtra,
      $config->colorHilite,
      $config->colorNormal,
      $config->colorStart,
      $config->colorStop,
      $config->googleKey,
      $config->initLatitude,
      $config->initLongitude,
      $config->interval,
      $config->lang,
      $config->mapApi,
      $config->passLenMin,
      $config->passStrength,
      $config->publicTracks,
      $config->requireAuthentication,
      $config->strokeColor,
      $config->strokeOpacity,
      $config->strokeWeight,
      $config->units,
      $config->uploadMaxSize
    ];

    try {
      $query = $this->db->insertOrReplace("config", [ "name", "value" ], $values, "name", "value");
      $stmt = $this->db->prepare($query);
      $stmt->execute(array_map("serialize", $params));
      $this->saveLayers($config);
    } catch (PDOException $e) {
      syslog(LOG_ERR, $e->getMessage());
      throw new DatabaseException($e->getMessage());
    }
  }

  /**
   * Truncate ol_layers table
   * @throws PDOException
   */
  private function deleteLayers(): void {
    $query = "DELETE FROM " . $this->db->table("ol_layers");
    $this->db->exec($query);
  }

  /**
   * Save layers to database
   * @throws PDOException
   */
  private function saveLayers(Entity\Config $config): void {
    $this->deleteLayers();
    if (!empty($config->olLayers)) {
      $query = "INSERT INTO " . $this->db->table("ol_layers") . " (id, name, url, priority) VALUES (?, ?, ?, ?)";
      $stmt = $this->db->prepare($query);
      foreach ($config->olLayers as $layer) {
        $stmt->execute([ $layer->id, $layer->name, $layer->url, $layer->priority ]);
      }
    }
  }

  /**
   * Read config values from database
   * @throws PDOException
   */
  private function setLayersFromDatabase(Entity\Config $config): void {
    $config->olLayers = [];
    $query = "SELECT id, name, url, priority FROM " . $this->db->table('ol_layers');
    $result = $this->db->query($query);
    while ($row = $result->fetch()) {
      $config->olLayers[] = new Entity\Layer((int) $row["id"], $row["name"], $row["url"], (int) $row["priority"]);
    }
  }

  /**
   * @param array $row Database row
   * @return Entity\Config
   */
//  private function mapRowToObject(array $row): Entity\Config {
//    $config = new Entity\Config();
//    $row = array_map([ $this, "unserialize" ], $row);
//
//    if (!empty($row['map_api'])) {
//      $config->mapApi = $row['map_api'];
//    }
//    if (isset($row['latitude']) && is_numeric($row['latitude'])) {
//      $config->initLatitude = (float) $row['latitude'];
//    }
//    if (isset($row['longitude']) && is_numeric($row['longitude'])) {
//      $config->initLongitude = (float) $row['longitude'];
//    }
//    if (isset($row['google_key'])) {
//      $config->googleKey = $row['google_key'];
//    }
//    if (isset($row['require_auth']) && (is_numeric($row['require_auth']) || is_bool($row['require_auth']))) {
//      $config->requireAuthentication = (bool) $row['require_auth'];
//    }
//    if (isset($row['public_tracks']) && (is_numeric($row['public_tracks']) || is_bool($row['public_tracks']))) {
//      $config->publicTracks = (bool) $row['public_tracks'];
//    }
//    if (isset($row['pass_lenmin']) && is_numeric($row['pass_lenmin'])) {
//      $config->passLenMin = (int) $row['pass_lenmin'];
//    }
//    if (isset($row['pass_strength']) && is_numeric($row['pass_strength'])) {
//      $config->passStrength = (int) $row['pass_strength'];
//    }
//    if (isset($row['interval_seconds']) && is_numeric($row['interval_seconds'])) {
//      $config->interval = (int) $row['interval_seconds'];
//    }
//    if (!empty($row['lang'])) {
//      $config->lang = $row['lang'];
//    }
//    if (!empty($row['units'])) {
//      $config->units = $row['units'];
//    }
//    if (isset($row['stroke_weight']) && is_numeric($row['stroke_weight'])) {
//      $config->strokeWeight = (int) $row['stroke_weight'];
//    }
//    if (!empty($row['stroke_color'])) {
//      $config->strokeColor = $row['stroke_color'];
//    }
//    if (isset($row['stroke_opacity']) && is_numeric($row['stroke_opacity'])) {
//      $config->strokeOpacity = (float) $row['stroke_opacity'];
//    }
//    if (!empty($row['color_normal'])) {
//      $config->colorNormal = $row['color_normal'];
//    }
//    if (!empty($row['color_start'])) {
//      $config->colorStart = $row['color_start'];
//    }
//    if (!empty($row['color_stop'])) {
//      $config->colorStop = $row['color_stop'];
//    }
//    if (!empty($row['color_extra'])) {
//      $config->colorExtra = $row['color_extra'];
//    }
//    if (!empty($row['color_hilite'])) {
//      $config->colorHilite = $row['color_hilite'];
//    }
//    if (isset($row['upload_maxsize']) && is_numeric($row['upload_maxsize'])) {
//      $config->uploadMaxSize = (int) $row['upload_maxsize'];
//      $config->setUploadLimit();
//    }
//    if (!$config->requireAuthentication) {
//      // tracks must be public if we don't require authentication
//      $config->publicTracks = true;
//    }
//    return $config;
//  }

  /**
   * Unserialize data from database
   * @param object|string $data Resource returned by pgsql, string otherwise
   * @return mixed
   */
  private function unserialize(object|string $data): mixed {
    if (is_resource($data)) {
      $data = stream_get_contents($data);
    }
    return unserialize($data, [ 'allowed_classes' => false ]);
  }

  /**
   * @throws ServerException
   */
  public function mapRowToObject(array $row): Entity\Config {
    $row = array_map([ $this, "unserialize" ], $row);
    return Entity\Config::fromDatabaseRow($row);
  }


}


