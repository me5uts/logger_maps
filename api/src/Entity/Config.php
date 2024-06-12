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
use uLogger\Entity\Layer;
use uLogger\Helper\Utils;

/**
 * Handles config values
 */
class Config {
  /**
   * Singleton instance
   *
   * @var Config|null Object instance
   */
  private static ?Config $instance = null;
  /**
   * @var string Version number
   */
  public string $version = "2.0-beta";

  /**
   * @var string Default map drawing framework
   */
  public string $mapApi = "openlayers";

  /**
   * @var string|null Google maps key
   */
  public ?string $googleKey;

  /**
   * @var Layer[] Openlayers extra map layers
   */
  public array $olLayers = [];

  /**
   * @var float Default latitude for initial map
   */
  public float $initLatitude = 52.23;
  /**
   * @var float Default longitude for initial map
   */
  public float $initLongitude = 21.01;

  /**
   * @var bool Require login/password authentication
   */
  public bool $requireAuthentication = true;

  /**
   * @var bool All users tracks are visible to authenticated user
   */
  public bool $publicTracks = false;

  /**
   * @var int Minimum required length of user password
   */
  public int $passLenMin = 10;

  /**
   * @var int Required strength of user password
   * 0 = no requirements,
   * 1 = require mixed case letters (lower and upper),
   * 2 = require mixed case and numbers
   * 3 = require mixed case, numbers and non-alphanumeric characters
   */
  public int $passStrength = 2;

  /**
   * @var int Default interval in seconds for live auto reload
   */
  public int $interval = 10;

  /**
   * @var string Default language code
   */
  public string $lang = "en";

  /**
   * @var string Default units
   */
  public string $units = "metric";

  /**
   * @var int Stroke weight
   */
  public int $strokeWeight = 2;
  /**
   * @var string Stroke color
   */
  public string $strokeColor = "#ff0000";
  /**
   * @var float Stroke opacity
   */
  public float $strokeOpacity = 1.0;
  /**
   * @var string Stroke color
   */
  public string $colorNormal = "#ffffff";
  /**
   * @var string Stroke color
   */
  public string $colorStart = "#55b500";
  /**
   * @var string Stroke color
   */
  public string $colorStop = "#ff6a00";
  /**
   * @var string Stroke color
   */
  public string $colorExtra = "#cccccc";
  /**
   * @var string Stroke color
   */
  public string $colorHilite = "#feff6a";
  /**
   * @var int Maximum size of uploaded files in bytes.
   * Will be adjusted to system maximum upload size
   */
  public int $uploadMaxSize = 5242880;

  public function __construct(bool $useDatabase = true) {
    if ($useDatabase) {
      $this->setFromDatabase();
    }
    $this->setFromCookies();
  }

  /**
   * Returns singleton instance
   *
   * @return Config Singleton instance
   */
  public static function getInstance(): Config {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Returns singleton instance
   *
   * @return Config Singleton instance
   */
  public static function getOfflineInstance(): Config {
    if (!self::$instance) {
      self::$instance = new self(false);
    }
    return self::$instance;
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
   * Read config values from database
   */
  public function setFromDatabase(): void {
    try {
      $query = "SELECT name, value FROM " . self::db()->table("config");
      $result = self::db()->query($query);
      $arr = $result->fetchAll(PDO::FETCH_KEY_PAIR);
      $this->setFromArray(array_map([ $this, "unserialize" ], $arr));
      $this->setLayersFromDatabase();
      if (!$this->requireAuthentication) {
        // tracks must be public if we don't require authentication
        $this->publicTracks = true;
      }
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
    }
  }

  /**
   * Unserialize data from database
   * @param object|string $data Resource returned by pgsql, string otherwise
   * @return mixed
   */
  private function unserialize($data) {
    if (is_resource($data)) {
      $data = stream_get_contents($data);
    }
    return unserialize($data, ['allowed_classes' => false]);
  }

  /**
   * Save config values to database
   * @return bool True on success, false otherwise
   */
  public function save(): bool {
    $ret = false;
    try {
      // PDO::PARAM_LOB doesn't work here with pgsql, why?
      $placeholder = self::db()->lobPlaceholder();
      $values = [
        ["'color_extra'", $placeholder],
        ["'color_hilite'", $placeholder],
        ["'color_normal'", $placeholder],
        ["'color_start'", $placeholder],
        ["'color_stop'", $placeholder],
        ["'google_key'", $placeholder],
        ["'latitude'", $placeholder],
        ["'longitude'", $placeholder],
        ["'interval_seconds'", $placeholder],
        ["'lang'", $placeholder],
        ["'map_api'", $placeholder],
        ["'pass_lenmin'", $placeholder],
        ["'pass_strength'", $placeholder],
        ["'public_tracks'", $placeholder],
        ["'require_auth'", $placeholder],
        ["'stroke_color'", $placeholder],
        ["'stroke_opacity'", $placeholder],
        ["'stroke_weight'", $placeholder],
        ["'units'", $placeholder],
        ["'upload_maxsize'", $placeholder]
      ];
      $query = self::db()->insertOrReplace("config", [ "name", "value" ], $values, "name", "value");
      $stmt = self::db()->prepare($query);
      $params = [
        $this->colorExtra,
        $this->colorHilite,
        $this->colorNormal,
        $this->colorStart,
        $this->colorStop,
        $this->googleKey,
        $this->initLatitude,
        $this->initLongitude,
        $this->interval,
        $this->lang,
        $this->mapApi,
        $this->passLenMin,
        $this->passStrength,
        $this->publicTracks,
        $this->requireAuthentication,
        $this->strokeColor,
        $this->strokeOpacity,
        $this->strokeWeight,
        $this->units,
        $this->uploadMaxSize
      ];

      $stmt->execute(array_map("serialize", $params));
      $this->saveLayers();
      $ret = true;
    } catch (PDOException $e) {
      // TODO: handle exception
      syslog(LOG_ERR, $e->getMessage());
    }
    return $ret;
  }

  /**
   * Truncate ol_layers table
   * @throws PDOException
   */
  private function deleteLayers(): void {
    $query = "DELETE FROM " . self::db()->table("ol_layers");
    self::db()->exec($query);
  }

  /**
   * Save layers to database
   * @throws PDOException
   */
  private function saveLayers(): void {
    $this->deleteLayers();
    if (!empty($this->olLayers)) {
      $query = "INSERT INTO " . self::db()->table("ol_layers") . " (id, name, url, priority) VALUES (?, ?, ?, ?)";
      $stmt = self::db()->prepare($query);
      foreach ($this->olLayers as $layer) {
        $stmt->execute([ $layer->id, $layer->name, $layer->url, $layer->priority]);
      }
    }
  }

  /**
   * Read config values from database
   * @throws PDOException
   */
  private function setLayersFromDatabase(): void {
    $this->olLayers = [];
    $query = "SELECT id, name, url, priority FROM " . self::db()->table('ol_layers');
    $result = self::db()->query($query);
    while ($row = $result->fetch()) {
      $this->olLayers[] = new Layer((int) $row["id"], $row["name"], $row["url"], (int) $row["priority"]);
    }
  }

  /**
   * Read config values stored in cookies
   */
  private function setFromCookies(): void {
    if (isset($_COOKIE["ulogger_api"])) { $this->mapApi = $_COOKIE["ulogger_api"]; }
    if (isset($_COOKIE["ulogger_lang"])) { $this->lang = $_COOKIE["ulogger_lang"]; }
    if (isset($_COOKIE["ulogger_units"])) { $this->units = $_COOKIE["ulogger_units"]; }
    if (isset($_COOKIE["ulogger_interval"])) { $this->interval = $_COOKIE["ulogger_interval"]; }
  }


  /**
   * Check if given password matches user's one
   *
   * @param string $password Password
   * @return bool True if matches, false otherwise
   */
  public function validPassStrength(string $password): bool {
    return preg_match($this->passRegex(), $password) === 1;
  }

  /**
   * Regex to test if password matches strength and length requirements.
   * Valid for both php and javascript
   * @return string
   */
  public function passRegex(): string {
    $regex = "";
    if ($this->passStrength > 0) {
      // lower and upper case
      $regex .= "(?=.*[a-z])(?=.*[A-Z])";
    }
    if ($this->passStrength > 1) {
      // digits
      $regex .= "(?=.*[0-9])";
    }
    if ($this->passStrength > 2) {
      // not latin, not digits
      $regex .= "(?=.*[^a-zA-Z0-9])";
    }
    if ($this->passLenMin > 0) {
      $regex .= "(?=.{" . $this->passLenMin . ",})";
    }
    if (empty($regex)) {
      $regex = ".*";
    }
    return "/" . $regex . "/";
  }

  /**
   * Set config values from array
   * @param array $arr
   */
  public function setFromArray(array $arr): void {

    if (isset($arr['map_api']) && !empty($arr['map_api'])) {
      $this->mapApi = $arr['map_api'];
    }
    if (isset($arr['latitude']) && is_numeric($arr['latitude'])) {
      $this->initLatitude = (float) $arr['latitude'];
    }
    if (isset($arr['longitude']) && is_numeric($arr['longitude'])) {
      $this->initLongitude = (float) $arr['longitude'];
    }
    if (isset($arr['google_key'])) {
      $this->googleKey = $arr['google_key'];
    }
    if (isset($arr['require_auth']) && (is_numeric($arr['require_auth']) || is_bool($arr['require_auth']))) {
      $this->requireAuthentication = (bool) $arr['require_auth'];
    }
    if (isset($arr['public_tracks']) && (is_numeric($arr['public_tracks']) || is_bool($arr['public_tracks']))) {
      $this->publicTracks = (bool) $arr['public_tracks'];
    }
    if (isset($arr['pass_lenmin']) && is_numeric($arr['pass_lenmin'])) {
      $this->passLenMin = (int) $arr['pass_lenmin'];
    }
    if (isset($arr['pass_strength']) && is_numeric($arr['pass_strength'])) {
      $this->passStrength = (int) $arr['pass_strength'];
    }
    if (isset($arr['interval_seconds']) && is_numeric($arr['interval_seconds'])) {
      $this->interval = (int) $arr['interval_seconds'];
    }
    if (isset($arr['lang']) && !empty($arr['lang'])) {
      $this->lang = $arr['lang'];
    }
    if (isset($arr['units']) && !empty($arr['units'])) {
      $this->units = $arr['units'];
    }
    if (isset($arr['stroke_weight']) && is_numeric($arr['stroke_weight'])) {
      $this->strokeWeight = (int) $arr['stroke_weight'];
    }
    if (isset($arr['stroke_color']) && !empty($arr['stroke_color'])) {
      $this->strokeColor = $arr['stroke_color'];
    }
    if (isset($arr['stroke_opacity']) && is_numeric($arr['stroke_opacity'])) {
      $this->strokeOpacity = (float) $arr['stroke_opacity'];
    }
    if (isset($arr['color_normal']) && !empty($arr['color_normal'])) {
      $this->colorNormal = $arr['color_normal'];
    }
    if (isset($arr['color_start']) && !empty($arr['color_start'])) {
      $this->colorStart = $arr['color_start'];
    }
    if (isset($arr['color_stop']) && !empty($arr['color_stop'])) {
      $this->colorStop = $arr['color_stop'];
    }
    if (isset($arr['color_extra']) && !empty($arr['color_extra'])) {
      $this->colorExtra = $arr['color_extra'];
    }
    if (isset($arr['color_hilite']) && !empty($arr['color_hilite'])) {
      $this->colorHilite = $arr['color_hilite'];
    }
    if (isset($arr['upload_maxsize']) && is_numeric($arr['upload_maxsize'])) {
      $this->uploadMaxSize = (int) $arr['upload_maxsize'];
      $this->setUploadLimit();
    }
  }
  public function setFromConfig(Config $config) {
    $this->mapApi = $config->mapApi;
    $this->initLatitude = $config->initLatitude;
    $this->initLongitude = $config->initLongitude;
    $this->googleKey = $config->googleKey;
    $this->requireAuthentication = $config->requireAuthentication;
    $this->publicTracks = $config->publicTracks;
    $this->passLenMin = $config->passLenMin;
    $this->passStrength = $config->passStrength;
    $this->interval = $config->interval;
    $this->lang = $config->lang;
    $this->units = $config->units;
    $this->strokeWeight = $config->strokeWeight;
    $this->strokeColor = $config->strokeColor;
    $this->strokeOpacity = $config->strokeOpacity;
    $this->colorNormal = $config->colorNormal;
    $this->colorStart = $config->colorStart;
    $this->colorStop = $config->colorStop;
    $this->colorExtra = $config->colorExtra;
    $this->colorHilite = $config->colorHilite;
    $this->uploadMaxSize = $config->uploadMaxSize;
  }

  /**
   * Adjust uploadMaxSize to system limits
   */
  private function setUploadLimit(): void {
    $limit = Utils::getSystemUploadLimit();
    if ($this->uploadMaxSize <= 0 || $this->uploadMaxSize > $limit) {
      $this->uploadMaxSize = $limit;
    }
  }
}

?>
